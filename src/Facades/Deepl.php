<?php

namespace PavelZanek\LaravelDeepl\Facades;

use Illuminate\Support\Facades\Facade;
use PavelZanek\LaravelDeepl\DeeplClient;

/**
 * @method static \PavelZanek\LaravelDeepl\Services\Builders\TranslationBuilder translateText(string|array|null $texts = null, string|null $sourceLang = null, string|null $targetLang = null, array $options = [], bool|null $useCache = null)
 *
 * @see DeeplClient
 */
class Deepl extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'deepl.translator';
    }
}
