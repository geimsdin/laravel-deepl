<?php

namespace PavelZanek\LaravelDeepl\Services\Builders;

use DeepL\TextResult;
use PavelZanek\LaravelDeepl\DeeplClient;
use PavelZanek\LaravelDeepl\Enums\V2\Formality;
use PavelZanek\LaravelDeepl\Enums\V2\PreserveFormatting;

/**
 * Class TranslationBuilder
 *
 * Helps build and execute translations with method chaining.
 */
class TranslationBuilder
{
    /**
     * The translator instance.
     */
    protected DeeplClient $translator;

    /**
     * The text(s) to translate.
     *
     * @var string|array<array-key, string>|null
     */
    protected string|array|null $texts = null;

    /**
     * The source language code.
     */
    protected string $sourceLang;

    /**
     * The target language code.
     */
    protected string $targetLang;

    /**
     * Additional options for the translation.
     *
     * @var array<array-key, mixed>
     */
    protected array $options = [];

    /**
     * Whether to use caching.
     */
    protected bool $useCache;

    /**
     * Constructor.
     *
     * @param  DeeplClient  $translator  The translator instance.
     * @param  bool  $useCache  Whether to use caching.
     * @param  string|null  $sourceLang  The source language code.
     * @param  string  $targetLang  The target language code.
     */
    public function __construct(
        DeeplClient $translator,
        bool $useCache,
        ?string $sourceLang,
        string $targetLang
    ) {
        $this->translator = $translator;
        $this->useCache = $useCache;
        $this->sourceLang = $this->translator->ensureSourceLang($sourceLang);
        $this->targetLang = $targetLang;
    }

    /**
     * Disables caching for this translation.
     *
     * @return $this
     */
    public function withoutCache(): self
    {
        $this->useCache = false;

        return $this;
    }

    /**
     * Sets the texts to translate.
     *
     * @param  string|array<array-key, string>  $texts  The text(s) to translate.
     * @return $this
     */
    public function texts(string|array $texts): self
    {
        $this->texts = $texts;

        return $this;
    }

    /**
     * Sets a single text to translate.
     *
     * @param  string  $text  The text to translate.
     * @return $this
     */
    public function text(string $text): self
    {
        $this->texts = $text;

        return $this;
    }

    /**
     * Sets the source language code.
     *
     * @param  string  $value  The source language code.
     * @return $this
     */
    public function sourceLang(string $value): self
    {
        $this->sourceLang = $this->translator->ensureSourceLang($value);

        return $this;
    }

    /**
     * Sets the target language code.
     *
     * @param  string  $value  The target language code.
     * @return $this
     */
    public function targetLang(string $value): self
    {
        $this->targetLang = $value;

        return $this;
    }

    /**
     * Adds additional options for the translation.
     *
     * @param  array<array-key, mixed>  $options  The options to add.
     * @return $this
     */
    public function options(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Sets the formality level for the translation.
     *
     * @param  string  $value  The formality level.
     * @return $this
     */
    public function formality(string $value = Formality::DEFAULT->value): self
    {
        $this->options['formality'] = $value;

        return $this;
    }

    /**
     * Sets the glossary ID for the translation.
     *
     * @param  string  $glossaryId  The glossary ID.
     * @return $this
     */
    public function glossary(string $glossaryId): self
    {
        $this->options['glossary_id'] = $glossaryId;

        return $this;
    }

    /**
     * Sets the split sentences option for the translation.
     *
     * @param  bool|string  $split  The split sentences option.
     * @return $this
     */
    public function splitSentences(bool|string $split): self
    {
        $this->options['split_sentences'] = $split;

        return $this;
    }

    /**
     * Sets the preserve formatting option for the translation.
     *
     * @param  string  $value  The preserve formatting option.
     * @return $this
     */
    public function preserveFormatting(string $value = PreserveFormatting::ENABLED->value): self
    {
        $this->options['preserve_formatting'] = $value === PreserveFormatting::ENABLED->value;

        return $this;
    }

    /**
     * Provides additional context for the translation.
     *
     * @param  string  $context  The context string.
     * @return $this
     */
    public function context(string $context): self
    {
        $this->options['context'] = $context;

        return $this;
    }

    /**
     * Executes the translation.
     *
     * @return TextResult|array<array-key, TextResult>
     *
     * @throws \DeepL\DeepLException
     */
    public function translate(): TextResult|array
    {
        /** @var TextResult|array<array-key, TextResult> $translation */
        $translation = $this->translator->translateText(
            texts: $this->texts,
            sourceLang: $this->sourceLang,
            targetLang: $this->targetLang,
            options: $this->options,
            useCache: $this->useCache
        );

        return $translation;
    }
}
