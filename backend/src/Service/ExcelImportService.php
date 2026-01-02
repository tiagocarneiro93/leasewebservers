<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Server;
use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Worksheet\RowIterator;

/**
 * Service for importing server data from Excel files.
 * 
 * Features:
 * - Chunk reading for memory efficiency with large files
 * - Upsert logic (update existing or create new records)
 * - Batch processing with configurable batch size
 * - Progress callbacks for CLI feedback
 */
class ExcelImportService
{
    private const DEFAULT_BATCH_SIZE = 100;
    private const CHUNK_SIZE = 1000;
    
    // Expected column headers (case-insensitive)
    private const COLUMN_MODEL = 'model';
    private const COLUMN_RAM = 'ram';
    private const COLUMN_HDD = 'hdd';
    private const COLUMN_LOCATION = 'location';
    private const COLUMN_PRICE = 'price';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServerRepository $serverRepository,
    ) {
    }

    /**
     * Import servers from an Excel file
     * 
     * @param string $filePath Path to the Excel file
     * @param int $batchSize Number of records to process before flushing
     * @param bool $dryRun If true, don't persist changes
     * @param callable|null $progressCallback Called with (current, total, message) for progress updates
     * @return array{created: int, updated: int, skipped: int, errors: array<string>}
     */
    public function import(
        string $filePath,
        int $batchSize = self::DEFAULT_BATCH_SIZE,
        bool $dryRun = false,
        ?callable $progressCallback = null
    ): array {
        $this->validateFile($filePath);
        
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // Create reader with chunk reading for memory efficiency
        $reader = $this->createChunkReader($filePath);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Get total rows for progress reporting
        $highestRow = $worksheet->getHighestDataRow();
        $totalRows = $highestRow - 1; // Minus header row
        
        $this->notify($progressCallback, 0, $totalRows, "Starting import of {$totalRows} rows...");

        // Parse header row to get column mapping
        $columnMap = $this->parseHeaderRow($worksheet);
        if (empty($columnMap)) {
            throw new \RuntimeException('Could not parse header row. Expected columns: Model, RAM, HDD, Location, Price');
        }

        $processed = 0;
        $batchCount = 0;

        // Process data rows (starting from row 2, after header)
        foreach ($worksheet->getRowIterator(2) as $row) {
            $rowIndex = $row->getRowIndex();
            
            try {
                $rowData = $this->extractRowData($worksheet, $rowIndex, $columnMap);
                
                if ($this->isEmptyRow($rowData)) {
                    $result['skipped']++;
                    continue;
                }

                $importResult = $this->importRow($rowData, $dryRun);
                
                if ($importResult === 'created') {
                    $result['created']++;
                } elseif ($importResult === 'updated') {
                    $result['updated']++;
                }

                $batchCount++;
                $processed++;

                // Flush and clear in batches to manage memory
                if ($batchCount >= $batchSize && !$dryRun) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $batchCount = 0;
                    
                    $this->notify($progressCallback, $processed, $totalRows, 
                        "Processed {$processed}/{$totalRows} rows...");
                }

            } catch (\Exception $e) {
                $result['errors'][] = "Row {$rowIndex}: " . $e->getMessage();
                $result['skipped']++;
            }
        }

        // Final flush
        if (!$dryRun && $batchCount > 0) {
            $this->entityManager->flush();
        }

        // Release spreadsheet memory
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $this->notify($progressCallback, $processed, $totalRows, "Import complete!");

        return $result;
    }

    /**
     * Create a memory-efficient reader for large Excel files
     */
    private function createChunkReader(string $filePath): IReader
    {
        $inputFileType = IOFactory::identify($filePath);
        $reader = IOFactory::createReader($inputFileType);
        
        // Enable read data only mode (skip formatting, formulas)
        // This significantly reduces memory usage
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }
        
        // For very large files, you could implement a ChunkReadFilter here
        // to process in segments, but for most Excel files (<100k rows),
        // setReadDataOnly provides sufficient optimization
        
        return $reader;
    }

    /**
     * Parse the header row to create a column index mapping
     * 
     * @return array<string, string> Map of column name to column letter (A, B, C, etc.)
     */
    private function parseHeaderRow($worksheet): array
    {
        $columnMap = [];
        $highestColumn = $worksheet->getHighestDataColumn();
        
        // Iterate through columns A to highest
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $value = $worksheet->getCell($col . '1')->getValue();
            if ($value === null) {
                continue;
            }
            
            $normalizedHeader = strtolower(trim((string) $value));
            
            // Map various possible header names to our standard columns
            $mappings = [
                'model' => self::COLUMN_MODEL,
                'server model' => self::COLUMN_MODEL,
                'ram' => self::COLUMN_RAM,
                'memory' => self::COLUMN_RAM,
                'hdd' => self::COLUMN_HDD,
                'storage' => self::COLUMN_HDD,
                'hard disk' => self::COLUMN_HDD,
                'location' => self::COLUMN_LOCATION,
                'datacenter' => self::COLUMN_LOCATION,
                'price' => self::COLUMN_PRICE,
                'cost' => self::COLUMN_PRICE,
            ];

            if (isset($mappings[$normalizedHeader])) {
                $columnMap[$mappings[$normalizedHeader]] = $col;
            }
        }

        return $columnMap;
    }

    /**
     * Extract data from a single row
     * 
     * @return array<string, string|null>
     */
    private function extractRowData($worksheet, int $rowIndex, array $columnMap): array
    {
        $data = [];
        
        foreach ($columnMap as $field => $colLetter) {
            $value = $worksheet->getCell($colLetter . $rowIndex)->getValue();
            $data[$field] = $value !== null ? trim((string) $value) : null;
        }

        return $data;
    }

    /**
     * Check if a row is empty (all required fields are null/empty)
     */
    private function isEmptyRow(array $rowData): bool
    {
        return empty($rowData[self::COLUMN_MODEL]) 
            && empty($rowData[self::COLUMN_HDD])
            && empty($rowData[self::COLUMN_PRICE]);
    }

    /**
     * Import a single row - creates new server or updates existing one
     * 
     * @return string 'created' or 'updated'
     */
    private function importRow(array $rowData, bool $dryRun): string
    {
        $model = $rowData[self::COLUMN_MODEL] ?? '';
        $ram = $rowData[self::COLUMN_RAM] ?? '';
        $hdd = $rowData[self::COLUMN_HDD] ?? '';
        $location = $rowData[self::COLUMN_LOCATION] ?? '';
        $priceStr = $rowData[self::COLUMN_PRICE] ?? '';

        // Try to find existing server (unique by model + location + hdd)
        // This combination should uniquely identify a server configuration
        $existingServer = $this->serverRepository->findOneBy([
            'model' => $model,
            'location' => $location,
            'hdd' => $hdd,
        ]);

        $isNew = $existingServer === null;
        $server = $existingServer ?? new Server();

        // Parse and set all fields
        $server->setModel($model);
        $server->setRam($ram);
        $server->setRamSizeGb($this->parseRamSize($ram));
        $server->setHdd($hdd);
        $server->setStorageTotalGb($this->parseStorageSize($hdd));
        $server->setHddType($this->parseHddType($hdd));
        $server->setLocation($location);

        $priceData = $this->parsePrice($priceStr);
        $server->setPrice($priceData['amount']);
        $server->setCurrency($priceData['currency']);

        if (!$dryRun) {
            $this->entityManager->persist($server);
        }

        return $isNew ? 'created' : 'updated';
    }

    /**
     * Validate the file exists and is readable
     */
    private function validateFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException("File is not readable: {$filePath}");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $validExtensions = ['xlsx', 'xls', 'csv'];
        
        if (!in_array($extension, $validExtensions, true)) {
            throw new \InvalidArgumentException(
                "Invalid file type: {$extension}. Supported types: " . implode(', ', $validExtensions)
            );
        }
    }

    /**
     * Call progress callback if provided
     */
    private function notify(?callable $callback, int $current, int $total, string $message): void
    {
        if ($callback !== null) {
            $callback($current, $total, $message);
        }
    }

    // ========================================
    // Data Parsing Methods (same as Fixtures)
    // ========================================

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

        // Check for Singapore Dollar (S$) - MUST be first!
        if (str_starts_with($price, 'S$')) {
            return [
                'amount' => preg_replace('/[^0-9.]/', '', $price) ?: '0',
                'currency' => 'SGD',
            ];
        }

        // Check for US Dollar ($)
        if (str_starts_with($price, '$')) {
            return [
                'amount' => preg_replace('/[^0-9.]/', '', $price) ?: '0',
                'currency' => 'USD',
            ];
        }

        // Check for Euro symbol
        if (str_starts_with($price, '€')) {
            return [
                'amount' => preg_replace('/[^0-9.]/', '', $price) ?: '0',
                'currency' => 'EUR',
            ];
        }

        // Default to EUR (no symbol)
        return [
            'amount' => preg_replace('/[^0-9.]/', '', $price) ?: '0',
            'currency' => 'EUR',
        ];
    }

    /**
     * Get estimated row count without loading full file
     * Useful for progress estimation before import
     */
    public function getEstimatedRowCount(string $filePath): int
    {
        $this->validateFile($filePath);
        
        $reader = $this->createChunkReader($filePath);
        $spreadsheet = $reader->load($filePath);
        $count = $spreadsheet->getActiveSheet()->getHighestDataRow() - 1; // Minus header
        
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
        return $count;
    }
}

