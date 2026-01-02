<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ExcelImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to import servers from an Excel file.
 * 
 * Usage:
 *   php bin/console app:import-servers /path/to/servers.xlsx
 *   php bin/console app:import-servers servers.xlsx --batch-size=200
 *   php bin/console app:import-servers servers.xlsx --dry-run
 * 
 * Features:
 *   - Reads Excel files (.xlsx, .xls) and CSV
 *   - Updates existing records or creates new ones (upsert)
 *   - Processes in batches for memory efficiency
 *   - Progress bar for large imports
 *   - Dry-run mode to preview changes
 */
#[AsCommand(
    name: 'app:import-servers',
    description: 'Import servers from an Excel file (supports upsert - update existing, create new)',
)]
class ImportServersCommand extends Command
{
    public function __construct(
        private readonly ExcelImportService $importService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Path to the Excel file (.xlsx, .xls, or .csv)'
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_REQUIRED,
                'Number of records to process before flushing to database',
                100
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Run without persisting changes (preview mode)'
            )
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command imports server data from an Excel file.

<info>Basic usage:</info>
  <comment>php %command.full_name% /path/to/servers.xlsx</comment>

<info>With options:</info>
  <comment>php %command.full_name% servers.xlsx --batch-size=200</comment>
  <comment>php %command.full_name% servers.xlsx --dry-run</comment>

<info>Expected Excel columns:</info>
  - Model (or "Server Model")
  - RAM (or "Memory")
  - HDD (or "Storage", "Hard Disk")
  - Location (or "Datacenter")
  - Price (or "Cost")

<info>Upsert behavior:</info>
  Records are matched by: Model + Location + HDD combination.
  - If a match is found: the existing record is updated
  - If no match: a new record is created

<info>Large file handling:</info>
  The command processes files in chunks and flushes in batches
  to handle large Excel files without running out of memory.
  Adjust --batch-size for performance tuning.
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $filePath = $input->getArgument('file');
        $batchSize = (int) $input->getOption('batch-size');
        $dryRun = (bool) $input->getOption('dry-run');

        // Resolve relative path
        if (!str_starts_with($filePath, '/') && !preg_match('/^[A-Z]:/i', $filePath)) {
            $filePath = getcwd() . '/' . $filePath;
        }

        $io->title('Server Import from Excel');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be persisted');
        }

        $io->text([
            sprintf('File: <info>%s</info>', $filePath),
            sprintf('Batch size: <info>%d</info>', $batchSize),
        ]);

        try {
            // Get row count for progress bar
            $totalRows = $this->importService->getEstimatedRowCount($filePath);
            $io->text(sprintf('Rows to process: <info>%d</info>', $totalRows));
            $io->newLine();

            // Create progress bar
            $progressBar = new ProgressBar($output, $totalRows);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message%');
            $progressBar->setMessage('Starting...');
            $progressBar->start();

            // Progress callback
            $progressCallback = function (int $current, int $total, string $message) use ($progressBar): void {
                $progressBar->setProgress($current);
                $progressBar->setMessage($message);
            };

            // Run import
            $result = $this->importService->import(
                $filePath,
                $batchSize,
                $dryRun,
                $progressCallback
            );

            $progressBar->finish();
            $io->newLine(2);

            // Display results
            $io->success('Import completed!');

            $io->table(
                ['Metric', 'Count'],
                [
                    ['Created', $result['created']],
                    ['Updated', $result['updated']],
                    ['Skipped', $result['skipped']],
                    ['Errors', count($result['errors'])],
                ]
            );

            // Show errors if any
            if (!empty($result['errors'])) {
                $io->warning('The following errors occurred:');
                foreach (array_slice($result['errors'], 0, 10) as $error) {
                    $io->text("  • {$error}");
                }
                if (count($result['errors']) > 10) {
                    $io->text(sprintf('  ... and %d more errors', count($result['errors']) - 10));
                }
            }

            if ($dryRun) {
                $io->note('This was a dry run. Run without --dry-run to persist changes.');
            }

            return Command::SUCCESS;

        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error([
                'Import failed!',
                $e->getMessage(),
            ]);
            
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}

