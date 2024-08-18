<?php

namespace PavelZanek\LaravelDeepl\Enums\V2;

enum DocumentStatus: string
{
    case DONE = 'done';
    case TRANSLATING = 'translating';
}
