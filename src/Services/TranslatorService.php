<?php

namespace PavelZanek\LaravelDeepl\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Translation\Translator as BaseTranslator;
use PavelZanek\LaravelDeepl\Jobs\TranslateKeyOnTheFlyJob;

class TranslatorService extends BaseTranslator
{
    protected TranslationService $translationService;

    protected Application $app;

    public function __construct(Loader $loader, Application $app, TranslationService $translationService)
    {
        // Initialize the parent translator with the initial locale
        parent::__construct($loader, $app->getLocale());

        $this->translationService = $translationService;
        $this->app = $app;
    }

    /**
     * @param  string  $key
     * @param  array<array-key, mixed>  $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return array<array-key, mixed>|string|null
     *
     * @throws \DeepL\DeepLException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true): array|string|null
    {
        // Always use the current application locale, which may have changed at runtime
        $locale = $locale ?? $this->app->getLocale();

        $translation = parent::get($key, $replace, $locale, $fallback);

        // Check if on-the-fly translation is enabled in the configuration
        if (! config('laravel-deepl.enable_on_the_fly_translation', false)) {
            return $translation;
        }

        // Optionally check the environment (e.g., only allow in 'local' environment)
        if (
            ! config('laravel-deepl.on_the_fly_outside_local', false)
            && ! app()->environment('local')
        ) {
            return $translation;
        }

        // If the translation is missing, handle on-the-fly translation
        if ($translation === $key) {
            // Check if the translation should be queued
            if (config('laravel-deepl.on_the_fly_use_queue_for_translation', false)) {
                // Dispatch the translation job to the queue
                dispatch(new TranslateKeyOnTheFlyJob($key, $locale, $replace, $fallback));

                return $key; // return the key immediately, as translation will happen in the background
            }

            $translation = $this->handleMissingTranslation($key, $locale, $replace, $fallback);
        }

        return $translation;
    }

    /**
     * @param  array<array-key, mixed>  $replace
     *
     * @throws \DeepL\DeepLException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handleMissingTranslation(string $key, ?string $locale, array $replace, bool $fallback): string
    {
        // Check if it's a JSON translation (no dots in the key => PHP file)
        $isPhpOrStructuredJson = str_contains($key, '.');

        // Get the source and target languages from the configuration
        /** @var string $sourceLang */
        $sourceLang = config('laravel-deepl.on_the_fly_source_lang', 'en');
        $targetLang = $locale ?? $this->app->getLocale();

        // Translate the appropriate file based on whether it's a JSON or PHP translation
        if ($isPhpOrStructuredJson) {
            [$namespace, $group, $item] = $this->parseKey($key); // Parse the key for PHP/structured JSON translations
            $filePath = [
                lang_path("{$sourceLang}/{$group}.php"),
                lang_path("{$sourceLang}/{$group}.json"),
            ];
        } else {
            $filePath = lang_path("{$sourceLang}.json");
        }

        // Use the translation service to perform the translation
        if (is_array($filePath)) {
            foreach ($filePath as $file) {
                $translations = $this->translationService->translateFile(
                    $file,
                    $sourceLang,
                    $targetLang,
                    returnTranslations: [$key],
                    skipFileExistsCheck: true // there can be JSON file with dotted keys (so we can't recognize the right file)
                );

                if (isset($translations[$key])) {
                    /** @var string $translation */
                    $translation = $translations[$key];

                    return $translation;
                }
            }
        } else {
            $translations = $this->translationService->translateFile(
                $filePath,
                $sourceLang,
                $targetLang,
                returnTranslations: [$key],
                skipFileExistsCheck: true // there can be JSON file with dotted keys (so we can't recognize the right file)
            );

            // TODO
            // if the $translations[$key] is not set, then the translation is still missing
            // if the $translations[$key] is null, then the source language translation is missing

            // Return the translated key or the original key if the translation is missing
            /** @var string $translation */
            $translation = $translations[$key] ?? $key;

            return $translation;
        }

        return $key;
    }
}
