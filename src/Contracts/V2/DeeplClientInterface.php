<?php

namespace PavelZanek\LaravelDeepl\Contracts\V2;

use PavelZanek\LaravelDeepl\Clients\V2\DeeplDocumentTranslationClient;
use PavelZanek\LaravelDeepl\Clients\V2\DeeplGlossaryClient;
use PavelZanek\LaravelDeepl\Clients\V2\DeeplLanguagesClient;
use PavelZanek\LaravelDeepl\Clients\V2\DeeplTextTranslationClient;
use PavelZanek\LaravelDeepl\Clients\V2\DeeplUsageClient;

interface DeeplClientInterface
{
    public function textTranslation(string $text): DeeplTextTranslationClient;

    public function documentTranslation(): DeeplDocumentTranslationClient;

    public function glossary(): DeeplGlossaryClient;

    public function languages(): DeeplLanguagesClient;

    public function usage(): DeeplUsageClient;
}
