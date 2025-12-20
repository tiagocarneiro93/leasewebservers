<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ServerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
#[ORM\Table(name: 'servers')]
#[ORM\Index(columns: ['ram_size_gb'], name: 'idx_ram_size')]
#[ORM\Index(columns: ['storage_total_gb'], name: 'idx_storage')]
#[ORM\Index(columns: ['hdd_type'], name: 'idx_hdd_type')]
#[ORM\Index(columns: ['location'], name: 'idx_location')]
#[OA\Schema(
    schema: 'Server',
    description: 'Server entity',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'model', type: 'string', example: 'Dell R210Intel Xeon X3440'),
        new OA\Property(property: 'ram', type: 'string', example: '16GBDDR3'),
        new OA\Property(property: 'ramSizeGb', type: 'integer', example: 16),
        new OA\Property(property: 'hdd', type: 'string', example: '2x2TBSATA2'),
        new OA\Property(property: 'storageTotalGb', type: 'integer', example: 4000),
        new OA\Property(property: 'hddType', type: 'string', example: 'SATA'),
        new OA\Property(property: 'location', type: 'string', example: 'AmsterdamAMS-01'),
        new OA\Property(property: 'price', type: 'string', example: '49.99'),
        new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
        new OA\Property(property: 'formattedPrice', type: 'string', example: '€49.99'),
    ]
)]
class Server
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['server:list', 'server:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['server:list', 'server:detail'])]
    private string $model;

    #[ORM\Column(length: 100)]
    #[Groups(['server:list', 'server:detail'])]
    private string $ram;

    #[ORM\Column]
    #[Groups(['server:list', 'server:detail'])]
    private int $ramSizeGb;

    #[ORM\Column(length: 255)]
    #[Groups(['server:list', 'server:detail'])]
    private string $hdd;

    #[ORM\Column]
    #[Groups(['server:list', 'server:detail'])]
    private int $storageTotalGb;

    #[ORM\Column(length: 20)]
    #[Groups(['server:list', 'server:detail'])]
    private string $hddType;

    #[ORM\Column(length: 100)]
    #[Groups(['server:list', 'server:detail'])]
    private string $location;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['server:list', 'server:detail'])]
    private string $price;

    #[ORM\Column(length: 10)]
    #[Groups(['server:list', 'server:detail'])]
    private string $currency = 'EUR';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getRam(): string
    {
        return $this->ram;
    }

    public function setRam(string $ram): static
    {
        $this->ram = $ram;
        return $this;
    }

    public function getRamSizeGb(): int
    {
        return $this->ramSizeGb;
    }

    public function setRamSizeGb(int $ramSizeGb): static
    {
        $this->ramSizeGb = $ramSizeGb;
        return $this;
    }

    public function getHdd(): string
    {
        return $this->hdd;
    }

    public function setHdd(string $hdd): static
    {
        $this->hdd = $hdd;
        return $this;
    }

    public function getStorageTotalGb(): int
    {
        return $this->storageTotalGb;
    }

    public function setStorageTotalGb(int $storageTotalGb): static
    {
        $this->storageTotalGb = $storageTotalGb;
        return $this;
    }

    public function getHddType(): string
    {
        return $this->hddType;
    }

    public function setHddType(string $hddType): static
    {
        $this->hddType = $hddType;
        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    #[Groups(['server:list', 'server:detail'])]
    public function getFormattedPrice(): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'SGD' => 'S$',
            'EUR' => '€',
            default => $this->currency . ' ',
        };

        return $symbol . number_format((float) $this->price, 2);
    }
}

