<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Server;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ServerFixtures extends Fixture
{
    private string $dataPath;

    public function __construct()
    {
        $this->dataPath = __DIR__ . '/../../data/servers.json';
    }

    public function load(ObjectManager $manager): void
    {
        $serversData = $this->loadServersData();

        foreach ($serversData as $data) {
            $server = new Server();
            $server->setModel($data['model']);
            $server->setRam($data['ram']);
            $server->setRamSizeGb($this->parseRamSize($data['ram']));
            $server->setHdd($data['hdd']);
            $server->setStorageTotalGb($this->parseStorageSize($data['hdd']));
            $server->setHddType($this->parseHddType($data['hdd']));
            $server->setLocation($data['location']);

            $priceData = $this->parsePrice($data['price']);
            $server->setPrice($priceData['amount']);
            $server->setCurrency($priceData['currency']);

            $manager->persist($server);
        }

        $manager->flush();
    }

    /**
     * Load servers data from JSON file
     *
     * @return array<int, array{model: string, ram: string, hdd: string, location: string, price: string}>
     */
    private function loadServersData(): array
    {
        $json = file_get_contents($this->dataPath);
        if ($json === false) {
            throw new \RuntimeException('Could not read servers data file');
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid servers data format');
        }

        return $data;
    }

    /**
     * Parse RAM string to get size in GB
     * Examples: "16GBDDR3" -> 16, "128GBDDR4" -> 128
     */
    private function parseRamSize(string $ram): int
    {
        if (preg_match('/(\d+)GB/i', $ram, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    /**
     * Parse HDD string to get total storage in GB
     * Examples: 
     *   "2x2TBSATA2" -> 4000 (2 * 2TB = 4TB = 4000GB)
     *   "8x2TBSATA2" -> 16000 (8 * 2TB = 16TB = 16000GB)
     *   "4x480GBSSD" -> 1920 (4 * 480GB)
     *   "2x120GBSSD" -> 240 (2 * 120GB)
     *   "2x500GBSATA2" -> 1000 (2 * 500GB)
     */
    private function parseStorageSize(string $hdd): int
    {
        // Pattern: NxSIZE(GB|TB)TYPE
        if (preg_match('/(\d+)x(\d+)(GB|TB)/i', $hdd, $matches)) {
            $count = (int) $matches[1];
            $size = (int) $matches[2];
            $unit = strtoupper($matches[3]);

            if ($unit === 'TB') {
                $size *= 1000; // Convert TB to GB
            }

            return $count * $size;
        }

        return 0;
    }

    /**
     * Parse HDD string to get disk type
     * Examples:
     *   "2x2TBSATA2" -> "SATA"
     *   "4x480GBSSD" -> "SSD"
     *   "8x600GBSAS" -> "SAS"
     */
    private function parseHddType(string $hdd): string
    {
        $hddUpper = strtoupper($hdd);

        if (str_contains($hddUpper, 'SSD')) {
            return 'SSD';
        }

        if (str_contains($hddUpper, 'SAS')) {
            return 'SAS';
        }

        if (str_contains($hddUpper, 'SATA')) {
            return 'SATA';
        }

        return 'UNKNOWN';
    }

    /**
     * Parse price string to extract amount and currency
     * Examples:
     *   "49.99" -> ['amount' => '49.99', 'currency' => 'EUR']
     *   "$105.99" -> ['amount' => '105.99', 'currency' => 'USD']
     *   "S$565.99" -> ['amount' => '565.99', 'currency' => 'SGD']
     *
     * @return array{amount: string, currency: string}
     */
    private function parsePrice(string $price): array
    {
        $price = trim($price);

        // Check for Singapore Dollar (S$)
        if (str_starts_with($price, 'S$')) {
            return [
                'amount' => preg_replace('/[^0-9.]/', '', $price),
                'currency' => 'SGD',
            ];
        }

        // Check for US Dollar ($)
        if (str_starts_with($price, '$')) {
            return [
                'amount' => preg_replace('/[^0-9.]/', '', $price),
                'currency' => 'USD',
            ];
        }

        // Default to EUR (no symbol or € symbol)
        return [
            'amount' => preg_replace('/[^0-9.]/', '', $price),
            'currency' => 'EUR',
        ];
    }
}

