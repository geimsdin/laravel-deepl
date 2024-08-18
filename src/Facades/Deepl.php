<?php

namespace PavelZanek\LaravelDeepl\Facades;

use Illuminate\Support\Facades\Facade;
use PavelZanek\LaravelDeepl\DeeplClient;

/**
 * @method static \PavelZanek\LaravelDeepl\Clients\V2\DeeplTextTranslationClient textTranslation(string|array $text = '')
 * @method static \PavelZanek\LaravelDeepl\Clients\V2\DeeplDocumentTranslationClient documentTranslation()
 * @method static \PavelZanek\LaravelDeepl\Clients\V2\DeeplGlossaryClient glossary()
 * @method static \PavelZanek\LaravelDeepl\Clients\V2\DeeplLanguagesClient languages()
 * @method static \PavelZanek\LaravelDeepl\Clients\V2\DeeplUsageClient usage()
 */
class Deepl extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DeeplClient::class;
    }
}
