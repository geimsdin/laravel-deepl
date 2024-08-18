<?php

namespace PavelZanek\LaravelDeepl\Console\Commands;

use Illuminate\Console\Command;
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
    protected $description = 'Retrieve usage information within the current billing period together with the corresponding account limits';

    public function __construct(
        private readonly DeeplClient $client
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(): int
    {
        /** @var array<string, int> $usage */
        $usage = $this->client->usage()->getUsage();

        $this->table(
            ['Usage & quota', 'Value'],
            [
                ['Translated characters in the current billing period', number_format($usage['character_count'])],
                ['Character limits in the current billing period', number_format($usage['character_limit'])],
            ]
        );

        return self::SUCCESS;
    }
}
