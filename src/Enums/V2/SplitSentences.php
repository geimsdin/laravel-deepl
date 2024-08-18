<?php

namespace PavelZanek\LaravelDeepl\Enums\V2;

enum SplitSentences: string
{
    case NONE = '0';
    case DEFAULT = '1';
    case NO_NEWLINES = 'nonewlines';
}
