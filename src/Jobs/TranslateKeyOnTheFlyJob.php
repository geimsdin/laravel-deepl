<?php

namespace PavelZanek\LaravelDeepl\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PavelZanek\LaravelDeepl\Services\TranslatorService;

class TranslateKeyOnTheFlyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $key;

    protected ?string $locale;

    /**
     * @var array<array-key, mixed>
     */
    protected array $replace;

    protected bool $fallback;

    /**
     * Create a new job instance.
     *
     * @param  array<array-key, mixed>  $replace
     */
    public function __construct(string $key, ?string $locale, array $replace, bool $fallback)
    {
        $this->key = $key;
        $this->locale = $locale;
        $this->replace = $replace;
        $this->fallback = $fallback;
    }

    /**
     * Execute the job.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(TranslatorService $translatorService): void
    {
        // Attempt to handle the missing translation in the background
        $translatorService->handleMissingTranslation($this->key, $this->locale, $this->replace, $this->fallback);
    }
}
