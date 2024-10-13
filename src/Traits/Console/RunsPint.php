<?php

namespace PavelZanek\LaravelDeepl\Traits\Console;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

trait RunsPint
{
    /**
     * Run Pint code formatter if conditions are met.
     */
    protected function maybeRunPint(): void
    {
        $withPint = $this->option('with-pint');

        if ($withPint && app()->environment('local')) {
            $this->info('Running Pint...');
            $this->runPint();
            $this->info('Pint has been executed.');
        } elseif ($withPint) {
            $this->warn('Pint was not run because the environment is not local.');
        }
    }

    /**
     * Run Pint code formatter.
     */
    protected function runPint(): void
    {
        $process = new Process(['./vendor/bin/pint']);
        $process->setTimeout(null);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->info($process->getOutput());
    }
}
