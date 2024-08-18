<?php

namespace PavelZanek\LaravelDeepl\Clients\V2;

use GuzzleHttp\Client;
use PavelZanek\LaravelDeepl\Enums\V2\Formality;
use PavelZanek\LaravelDeepl\Enums\V2\PreserveFormatting;
use PavelZanek\LaravelDeepl\Enums\V2\SourceLanguage;
use PavelZanek\LaravelDeepl\Enums\V2\SplitSentences;
use PavelZanek\LaravelDeepl\Models\Translation;

class DeeplTextTranslationClient
{
    protected Client $client;

    protected string $textToTranslate;

    protected ?string $targetLang = null;

    protected array $options = []; // @phpstan-ignore-line

    protected bool $useCache = true;

    public function __construct(Client $client, string $text)
    {
        $this->client = $client;
        $this->textToTranslate = $text;
        $this->targetLang = config('laravel-deepl.default_target_lang'); // @phpstan-ignore-line
    }

    /**
     * @return $this
     */
    public function withoutCache(): self
    {
        $this->useCache = false;

        return $this;
    }

    /**
     * Set the target language for translation.
     *
     * @return $this
     */
    public function targetLang(string $value): self
    {
        $this->targetLang = $value;

        return $this;
    }

    /**
     * Set the source language for translation.
     *
     * @return $this
     */
    public function sourceLang(string $value): self
    {
        $this->options['source_lang'] = $value;

        return $this;
    }

    /**
     * Set the formality level for translation.
     *
     * @return $this
     */
    public function formality(string $value = Formality::DEFAULT->value): self
    {
        $this->options['formality'] = $value;

        return $this;
    }

    /**
     * Set whether to preserve the original formatting.
     *
     * @return $this
     */
    public function preserveFormatting(string $value = PreserveFormatting::ENABLED->value): self
    {
        $this->options['preserve_formatting'] = $value;

        return $this;
    }

    /**
     * Set how the text should be split into sentences.
     *
     * @return $this
     */
    public function splitSentences(string $value = SplitSentences::DEFAULT->value): self
    {
        $this->options['split_sentences'] = $value;

        return $this;
    }

    /**
     * Provide additional context for translation.
     *
     * @return $this
     */
    public function context(string $context): self
    {
        $this->options['context'] = $context;

        return $this;
    }

    /**
     * Specify a glossary to use for translation.
     *
     * @return $this
     */
    public function glossary(string $glossaryId): self
    {
        $this->options['glossary_id'] = $glossaryId;

        return $this;
    }

    /**
     * Perform the translation and return the result.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTranslation(): string
    {
        /** @var array<string, mixed> $config */
        $config = config('laravel-deepl');

        $sourceLang = $this->options['source_lang'] ?? $config['default_source_lang'];
        $targetLang = $this->targetLang ?? $config['default_target_lang'];

        $options = [];
        if ($sourceLang && $sourceLang !== SourceLanguage::AUTOMATIC) {
            $options = array_merge([
                'source_lang' => $sourceLang,
            ], $this->options);
        }

        \ksort($this->options);

        // Check if the translation already exists in the database
        if ($this->useCache) {
            $cachedTranslation = Translation::query()
                ->where('text', $this->textToTranslate)
                ->where('source_lang', $sourceLang)
                ->where('target_lang', $targetLang)
                ->whereJsonContains('options', json_encode($this->options))
                ->first();

            if ($cachedTranslation) {
                return $cachedTranslation->getTranslatedText(); // @phpstan-ignore-line
            }
        }

        // If it doesn't exist, make the API call
        $response = $this->client->post('translate', [
            'form_params' => array_merge($options, [
                'text' => $this->textToTranslate,
                'target_lang' => $targetLang,
            ]),
        ]);

        $result = json_decode($response->getBody(), true);
        /** @var string $translatedText */
        $translatedText = $result['translations'][0]['text'] ?? ''; // @phpstan-ignore-line

        // Save the new translation to the database
        if ($this->useCache) {
            Translation::query()->create([
                'text' => $this->textToTranslate,
                'translated_text' => $translatedText,
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang,
                'options' => json_encode($this->options),
            ]);
        }

        return $translatedText;
    }
}
