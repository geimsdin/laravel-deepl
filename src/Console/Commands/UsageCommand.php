<?php

namespace PavelZanek\LaravelDeepl\Console\Commands;

use DeepL\DeepLException;
use DeepL\UsageDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PavelZanek\LaravelDeepl\DeeplClient;

class UsageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deepl:usage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve usage information within the current billing period along with account limits';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private readonly DeeplClient $client
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Retrieve usage information using the inherited getUsage() method
            $usage = $this->client->getUsage();

            // Validate the structure of the usage data
            if (! is_object($usage)) {
                $this->error('Invalid usage data format received from DeepL.');
                Log::error('DeepL usage command failed: Invalid data format.', ['usage' => $usage]);

                return self::FAILURE;
            }

            if (
                ! ($usage->character instanceof UsageDetail)
                || ! property_exists($usage->character, 'count')
                || ! property_exists($usage->character, 'limit')
            ) {
                $this->error('Invalid usage data format received from DeepL.');
                Log::error('DeepL usage command failed: Invalid data format.', ['usage' => $usage]);

                return self::FAILURE;
            }

            // Check if any usage limit has been reached
            if ($usage->anyLimitReached()) {
                $this->error('Translation limit exceeded.');
                $this->info('Please consider upgrading your DeepL plan or review your current usage.');
            }

            // Define a threshold for warnings (e.g., 90% of the limit)
            $warningThreshold = 0.9;

            // Initialize table data with headers
            $tableData = [
                ['Usage Type', 'Count', 'Limit', 'Remaining', 'Percentage'],
            ];

            // Helper function to format rows with optional color coding
            $formatRow = function ($usageType, $count, $limit) use ($warningThreshold) {
                $remaining = $limit - $count;
                $percentage = $limit > 0 ? ($count / $limit) * 100 : 0;

                $formattedUsageType = $usageType;
                $formattedCount = number_format($count);
                $formattedLimit = number_format($limit);
                $formattedRemaining = number_format($remaining);
                $formattedPercentage = number_format($percentage, 2).'%';

                // Determine row color based on usage percentage
                if ($percentage >= 100) {
                    // Red for exceeded limits
                    $formattedUsageType = "<fg=red>{$formattedUsageType}</>";
                    $formattedCount = "<fg=red>{$formattedCount}</>";
                    $formattedLimit = "<fg=red>{$formattedLimit}</>";
                    $formattedRemaining = "<fg=red>{$formattedRemaining}</>";
                    $formattedPercentage = "<fg=red>{$formattedPercentage}</>";
                } elseif ($percentage >= ($warningThreshold * 100)) {
                    // Yellow for approaching limits
                    $formattedUsageType = "<fg=yellow>{$formattedUsageType}</>";
                    $formattedCount = "<fg=yellow>{$formattedCount}</>";
                    $formattedLimit = "<fg=yellow>{$formattedLimit}</>";
                    $formattedRemaining = "<fg=yellow>{$formattedRemaining}</>";
                    $formattedPercentage = "<fg=yellow>{$formattedPercentage}</>";
                }

                return [
                    $formattedUsageType,
                    $formattedCount,
                    $formattedLimit,
                    $formattedRemaining,
                    $formattedPercentage,
                ];
            };

            // Add Character Usage
            $tableData[] = $formatRow(
                'Translated Characters',
                $usage->character->count,
                $usage->character->limit
            );

            // Add Document Usage if available
            if (isset($usage->document)) {
                $tableData[] = $formatRow(
                    'Translated Documents',
                    $usage->document->count,
                    $usage->document->limit
                );
            }

            // Add Team Document Usage if available
            if (isset($usage->teamDocument)) {
                $tableData[] = $formatRow(
                    'Translated Team Documents',
                    $usage->teamDocument->count,
                    $usage->teamDocument->limit
                );
            }

            // Calculate total counts and limits (optional)
            $totalCount = $usage->character->count;
            $totalLimit = $usage->character->limit;

            if (isset($usage->document)) {
                $totalCount += $usage->document->count;
                $totalLimit += $usage->document->limit;
            }

            if (isset($usage->teamDocument)) {
                $totalCount += $usage->teamDocument->count;
                $totalLimit += $usage->teamDocument->limit;
            }

            $remainingTotal = $totalLimit - $totalCount;
            $percentageTotal = $totalLimit > 0 ? ($totalCount / $totalLimit) * 100 : 0;

            // Add Summary Row
            if ($totalLimit > 0) {
                $summaryRow = [
                    'Total',
                    number_format($totalCount),
                    number_format($totalLimit),
                    number_format($remainingTotal),
                    number_format($percentageTotal, 2).'%',
                ];

                // Apply color coding to summary row
                if ($percentageTotal >= 100) {
                    $summaryRow = [
                        '<fg=red>Summary</>',
                        '<fg=red>'.number_format($totalCount).'</>',
                        '<fg=red>'.number_format($totalLimit).'</>',
                        '<fg=red>'.number_format($remainingTotal).'</>',
                        '<fg=red>'.number_format($percentageTotal, 2).'%</>',
                    ];
                } elseif ($percentageTotal >= ($warningThreshold * 100)) {
                    $summaryRow = [
                        '<fg=yellow>Summary</>',
                        '<fg=yellow>'.number_format($totalCount).'</>',
                        '<fg=yellow>'.number_format($totalLimit).'</>',
                        '<fg=yellow>'.number_format($remainingTotal).'</>',
                        '<fg=yellow>'.number_format($percentageTotal, 2).'%</>',
                    ];
                }

                $tableData[] = $summaryRow;
            }

            // Display the usage information in a table
            $this->table(
                $tableData[0], // Headers
                array_slice($tableData, 1) // Data rows
            );

            return self::SUCCESS;

        } catch (DeepLException $e) {
            // Handle specific DeepL API exceptions
            $this->error('DeepL API Error: '.$e->getMessage());
            Log::error('DeepL API Error in UsageCommand.', ['exception' => $e]);

            return self::FAILURE;

        } catch (\Exception $e) {
            // Handle any other unexpected exceptions
            $this->error('An unexpected error occurred: '.$e->getMessage());
            Log::error('Unexpected Error in UsageCommand.', ['exception' => $e]);

            return self::FAILURE;
        }
    }
}
