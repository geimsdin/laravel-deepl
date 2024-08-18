<?php

namespace PavelZanek\LaravelDeepl\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    /**
     * Model attributes.
     */
    public const ATTR_ID = 'id';

    public const ATTR_TEXT = 'text';

    public const ATTR_TRANSLATED_TEXT = 'translated_text';

    public const ATTR_SOURCE_LANG = 'source_lang';

    public const ATTR_TARGET_LANG = 'target_lang';

    public const ATTR_OPTIONS = 'options';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        self::ATTR_TEXT,
        self::ATTR_TRANSLATED_TEXT,
        self::ATTR_SOURCE_LANG,
        self::ATTR_TARGET_LANG,
        self::ATTR_OPTIONS,
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        self::ATTR_OPTIONS => 'array',
    ];

    public function getTranslatedText(): string
    {
        /** @var string $translatedText */
        $translatedText = $this->getAttributeValue(self::ATTR_TRANSLATED_TEXT);

        return $translatedText;
    }
}
