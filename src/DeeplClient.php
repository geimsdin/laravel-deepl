<?php

namespace PavelZanek\LaravelDeepl;

use DeepL\TextResult;
use DeepL\Translator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as CollectionSupport;
use PavelZanek\LaravelDeepl\Enums\V2\SourceLanguage;
use PavelZanek\LaravelDeepl\Enums\V2\TargetLanguage;
use PavelZanek\LaravelDeepl\Models\TranslationCache;
use PavelZanek\LaravelDeepl\Services\Builders\DocumentTranslationBuilder;
use PavelZanek\LaravelDeepl\Services\Builders\Glossary\CreateGlossaryBuilder;
use PavelZanek\LaravelDeepl\Services\Builders\LanguageBuilder;
use PavelZanek\LaravelDeepl\Services\Builders\TranslationBuilder;

/**
 * Class DeeplClient
 *
 * Extends the DeepL Translator to include caching functionality.
 */
class DeeplClient extends Translator
{
    /**
     * Translates text using the DeepL API with optional caching.
     *
     * @param  string|array<array-key, string>|null  $texts  The text(s) to translate.
     * @param  string|null  $sourceLang  The source language code.
     * @param  string|null  $targetLang  The target language code.
     * @param  array<array-key, mixed>  $options  Additional options for the translation.
     * @param  bool|null  $useCache  Whether to use caching.
     * @return TranslationBuilder|TextResult|TextResult[]
     *
     * @throws \DeepL\DeepLException
     */
    public function translateText(
        $texts = null,
        ?string $sourceLang = null,
        ?string $targetLang = null,
        array $options = [],
        ?bool $useCache = null
    ): TranslationBuilder|TextResult|array {
        /** @var bool $useCache */
        $useCache = $useCache ?? config('laravel-deepl.enable_translation_cache', false);
        $sourceLang = $this->ensureSourceLang($sourceLang);
        /** @var string $targetLang */
        $targetLang = $targetLang ?? config('laravel-deepl.default_target_lang', TargetLanguage::CZECH->value);

        if ($texts === null) {
            // Return a new TranslationBuilder instance for method chaining
            return new TranslationBuilder(
                translator: $this,
                useCache: $useCache,
                sourceLang: $sourceLang,
                targetLang: $targetLang
            );
        }

        if ($useCache) {
            // Attempt to retrieve cached translations
            $cachedTranslations = $this->getCachedTranslation($texts, $sourceLang, $targetLang, $options);

            // Map cached translations by text_hash for quick lookup
            $cachedTranslationsMap = $cachedTranslations->keyBy(TranslationCache::ATTR_TEXT_HASH);

            if (is_array($texts)) {
                // Handle multiple texts
                $missingTexts = [];
                $missingTextHashes = [];
                foreach ($texts as $text) {
                    $textHash = hash('sha256', $text);
                    if (! $cachedTranslationsMap->has($textHash)) {
                        $missingTexts[] = $text;
                        $missingTextHashes[] = $textHash;
                    }
                }

                if (empty($missingTexts)) {
                    // All texts are cached; return cached results
                    $result = [];
                    foreach ($texts as $text) {
                        $textHash = hash('sha256', $text);
                        /** @var TranslationCache $translation */
                        $translation = $cachedTranslationsMap->get($textHash);
                        $result[] = new TextResult(
                            $translation->getTranslatedText(),
                            $translation->getDetectedSourceLang() ?? $sourceLang,
                            $translation->getBilledCharacters() ?? 0,
                        );
                    }

                    return $result;
                } else {
                    // Some texts are missing; fetch them from the API
                    $apiResult = parent::translateText($missingTexts, $sourceLang, $targetLang, $options);

                    // Save new translations and update the cached translations map
                    foreach ($apiResult as $index => $resultItem) {
                        $text = $missingTexts[$index];
                        $textHash = $missingTextHashes[$index];
                        $this->saveTranslation(
                            text: $text,
                            textHash: $textHash,
                            translatedText: $resultItem->text,
                            sourceLang: $sourceLang,
                            targetLang: $targetLang,
                            options: $options,
                            detectedSourceLang: $resultItem->detectedSourceLang ?? $sourceLang, // @phpstan-ignore-line
                            billedCharacters: $resultItem->billedCharacters ?? null // @phpstan-ignore-line
                        );
                        $cachedTranslationsMap->put($textHash, (object) [ // @phpstan-ignore-line
                            TranslationCache::ATTR_TEXT_HASH => $textHash,
                            TranslationCache::ATTR_TRANSLATED_TEXT => $resultItem->text,
                            TranslationCache::ATTR_DETECTED_SOURCE_LANG => $resultItem->detectedSourceLang ?? $sourceLang, // @phpstan-ignore-line
                            TranslationCache::ATTR_BILLED_CHARACTERS => $resultItem->billedCharacters ?? 0, // @phpstan-ignore-line
                        ]);
                    }

                    // Build the final result combining cached and new translations
                    $result = [];
                    foreach ($texts as $text) {
                        $textHash = hash('sha256', $text);
                        /** @var TranslationCache $translation */
                        $translation = $cachedTranslationsMap->get($textHash);
                        $result[] = new TextResult(
                            $translation->getTranslatedText(),
                            $translation->getDetectedSourceLang() ?? $sourceLang,
                            $translation->getBilledCharacters() ?? 0,
                        );
                    }

                    return $result;
                }
            } else {
                // Handle a single text
                $textHash = hash('sha256', $texts);
                if ($cachedTranslationsMap->has($textHash)) {
                    // Text is cached; return the cached result
                    /** @var TranslationCache $translation */
                    $translation = $cachedTranslationsMap->get($textHash);

                    return new TextResult(
                        $translation->getTranslatedText(),
                        $translation->getDetectedSourceLang() ?? $sourceLang,
                        $translation->getBilledCharacters() ?? 0,
                    );
                } else {
                    // Text is not cached; fetch from the API and save
                    $result = parent::translateText($texts, $sourceLang, $targetLang, $options);
                    $this->saveTranslation(
                        text: $texts,
                        textHash: $textHash,
                        translatedText: $result->text,
                        sourceLang: $sourceLang,
                        targetLang: $targetLang,
                        options: $options,
                        detectedSourceLang: $result->detectedSourceLang ?? $sourceLang, // @phpstan-ignore-line
                        billedCharacters: $result->billedCharacters ?? null // @phpstan-ignore-line
                    );

                    return $result;
                }
            }
        } else {
            // Caching is disabled; proceed with translation
            return parent::translateText($texts, $sourceLang, $targetLang, $options);
        }
    }

    /**
     * Ensures that the source language is set, defaulting to 'auto' if null.
     *
     * @param  string|null  $sourceLang  The source language code.
     * @return string The source language code.
     */
    public function ensureSourceLang(?string $sourceLang): string
    {
        /** @var string $configSourceLang */
        $configSourceLang = config('laravel-deepl.default_source_lang', SourceLanguage::AUTOMATIC->value);

        return $sourceLang ?: $configSourceLang ?: SourceLanguage::AUTOMATIC->value;
    }

    /**
     * Retrieves cached translations from the database.
     *
     * @param  string|array<array-key, string>  $texts  The text(s) to translate.
     * @param  string  $sourceLang  The source language code.
     * @param  string  $targetLang  The target language code.
     * @param  array<array-key, mixed>  $options  Additional options for the translation.
     * @return \Illuminate\Database\Eloquent\Collection<int, \PavelZanek\LaravelDeepl\Models\TranslationCache>|\Illuminate\Support\Collection<int, \PavelZanek\LaravelDeepl\Models\TranslationCache>
     */
    private function getCachedTranslation(
        string|array $texts,
        string $sourceLang,
        string $targetLang,
        array $options = []
    ): Collection|CollectionSupport {
        /** @var string $encodedOptions */
        $encodedOptions = json_encode($options);
        $optionsHash = md5($encodedOptions);

        // Compute hashes for the texts
        $textHashes = is_array($texts)
            ? array_map(fn ($text) => hash('sha256', $text), $texts)
            : [hash('sha256', $texts)];

        return TranslationCache::query()
            ->when(is_array($textHashes), function ($query) use ($textHashes) {
                return $query->whereIn(TranslationCache::ATTR_TEXT_HASH, $textHashes);
            }, function ($query) use ($textHashes) {
                return $query->where(TranslationCache::ATTR_TEXT_HASH, $textHashes[0]);
            })
            ->where(TranslationCache::ATTR_SOURCE_LANG, $sourceLang)
            ->where(TranslationCache::ATTR_TARGET_LANG, $targetLang)
            ->where(TranslationCache::ATTR_OPTIONS_HASH, $optionsHash)
            ->get();
    }

    /**
     * Saves a new translation to the database.
     *
     * @param  string  $text  The original text.
     * @param  string  $textHash  The hash of the original text.
     * @param  string  $translatedText  The translated text.
     * @param  string  $sourceLang  The source language code.
     * @param  string  $targetLang  The target language code.
     * @param  array<array-key, mixed>  $options  Additional options used in the translation.
     * @param  string|null  $detectedSourceLang  The detected source language code.
     * @param  int|null  $billedCharacters  The number of billed characters.
     */
    private function saveTranslation(
        string $text,
        string $textHash,
        string $translatedText,
        string $sourceLang,
        string $targetLang,
        array $options,
        ?string $detectedSourceLang = null,
        ?int $billedCharacters = null
    ): void {
        /** @var string $encodedOptions */
        $encodedOptions = json_encode($options);
        $optionsHash = md5($encodedOptions);

        TranslationCache::query()->create([
            TranslationCache::ATTR_TEXT => $text,
            TranslationCache::ATTR_TEXT_HASH => $textHash,
            TranslationCache::ATTR_TRANSLATED_TEXT => $translatedText,
            TranslationCache::ATTR_SOURCE_LANG => $sourceLang,
            TranslationCache::ATTR_TARGET_LANG => $targetLang,
            TranslationCache::ATTR_OPTIONS => json_encode($options),
            TranslationCache::ATTR_OPTIONS_HASH => $optionsHash,
            TranslationCache::ATTR_DETECTED_SOURCE_LANG => $detectedSourceLang ?? $sourceLang,
            TranslationCache::ATTR_BILLED_CHARACTERS => $billedCharacters,
        ]);
    }

    /**
     * Creates a DocumentTranslationBuilder instance for chainable document translation.
     */
    public function translateDocumentBuilder(): DocumentTranslationBuilder
    {
        return new DocumentTranslationBuilder($this);
    }

    /**
     * Creates a CreateGlossaryBuilder instance for chainable glossary creation.
     */
    public function createGlossaryBuilder(): CreateGlossaryBuilder
    {
        return new CreateGlossaryBuilder($this);
    }

    /**
     * Retrieves supported languages from the DeepL API.
     */
    public function languageBuilder(): LanguageBuilder
    {
        return new LanguageBuilder($this);
    }
}
