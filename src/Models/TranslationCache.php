<?php

namespace PavelZanek\LaravelDeepl\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationCache extends Model
{
    /**
     * Model attributes.
     */
    public const ATTR_ID = 'id';

    public const ATTR_TEXT = 'text';

    public const ATTR_TEXT_HASH = 'text_hash';

    public const ATTR_TRANSLATED_TEXT = 'translated_text';

    public const ATTR_SOURCE_LANG = 'source_lang';

    public const ATTR_TARGET_LANG = 'target_lang';

    public const ATTR_OPTIONS = 'options';

    public const ATTR_OPTIONS_HASH = 'options_hash';

    public const ATTR_DETECTED_SOURCE_LANG = 'detected_source_lang';

    public const ATTR_BILLED_CHARACTERS = 'billed_characters';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        self::ATTR_TEXT,
        self::ATTR_TEXT_HASH,
        self::ATTR_TRANSLATED_TEXT,
        self::ATTR_SOURCE_LANG,
        self::ATTR_TARGET_LANG,
        self::ATTR_OPTIONS,
        self::ATTR_OPTIONS_HASH,
        self::ATTR_DETECTED_SOURCE_LANG,
        self::ATTR_BILLED_CHARACTERS,
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        self::ATTR_OPTIONS => 'array',
        self::ATTR_BILLED_CHARACTERS => 'integer',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        /** @var string $tableName */
        $tableName = config('laravel-deepl.translation_cache_table', 'translations_cache');

        return $tableName;
    }

    /**
     * Get the text.
     */
    public function getText(): string
    {
        /** @var string $text */
        $text = $this->getAttributeValue(self::ATTR_TEXT);

        return $text;
    }

    /**
     * Get the text hash.
     */
    public function getTextHash(): string
    {
        /** @var string $textHash */
        $textHash = $this->getAttributeValue(self::ATTR_TEXT_HASH);

        return $textHash;
    }

    /**
     * Get the translated text.
     */
    public function getTranslatedText(): string
    {
        /** @var string $translatedText */
        $translatedText = $this->getAttributeValue(self::ATTR_TRANSLATED_TEXT);

        return $translatedText;
    }

    /**
     * Get the source language.
     */
    public function getSourceLang(): string
    {
        /** @var string $sourceLang */
        $sourceLang = $this->getAttributeValue(self::ATTR_SOURCE_LANG);

        return $sourceLang;
    }

    /**
     * Get the target language.
     */
    public function getTargetLang(): string
    {
        /** @var string $targetLang */
        $targetLang = $this->getAttributeValue(self::ATTR_TARGET_LANG);

        return $targetLang;
    }

    /**
     * Get the options.
     *
     * @return array<array-key, mixed>
     */
    public function getOptions(): array
    {
        /** @var array<array-key, mixed> $options */
        $options = $this->getAttributeValue(self::ATTR_OPTIONS);

        return $options;
    }

    /**
     * Get the options hash.
     */
    public function getOptionsHash(): string
    {
        /** @var string $optionsHash */
        $optionsHash = $this->getAttributeValue(self::ATTR_OPTIONS_HASH);

        return $optionsHash;
    }

    /**
     * Get the detected source language.
     */
    public function getDetectedSourceLang(): ?string
    {
        /** @var string|null $detectedSourceLang */
        $detectedSourceLang = $this->getAttributeValue(self::ATTR_DETECTED_SOURCE_LANG);

        return $detectedSourceLang;
    }

    /**
     * Get the billed characters.
     */
    public function getBilledCharacters(): ?int
    {
        /** @var int $billedCharacters */
        $billedCharacters = $this->getAttributeValue(self::ATTR_BILLED_CHARACTERS);

        return $billedCharacters;
    }
}
