<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DeepL API Key
    |--------------------------------------------------------------------------
    |
    | This key is used to authenticate with the DeepL API. You can obtain it
    | by signing up on the DeepL platform. Ensure that this key is kept safe
    | and not shared publicly.
    |
    */

    'api_key' => env('DEEPL_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | API Type
    |--------------------------------------------------------------------------
    |
    | This parameter defines which API endpoint should be used. The Free API
    | has a different endpoint than the Pro API. Set to "free" or "pro".
    |
    */

    'api_type' => env('DEEPL_API_TYPE', 'free'),

    /*
    |--------------------------------------------------------------------------
    | Default Source Language
    |--------------------------------------------------------------------------
    |
    | This parameter defines the default source language for translating texts
    | if no specific language is provided. The language code must be in line
    | with the language codes supported by DeepL (e.g., 'en' for English).
    |
    */

    'default_source_lang' => env('DEEPL_DEFAULT_SOURCE_LANG', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Default Target Language
    |--------------------------------------------------------------------------
    |
    | This parameter defines the default target language for translating texts
    | if no specific language is provided. The language code must be in line
    | with the language codes supported by DeepL (e.g., 'de' for German).
    |
    */

    'default_target_lang' => env('DEEPL_DEFAULT_TARGET_LANG', 'cs'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | This parameter determines which version of the API the package uses. The
    | current version is 'v2', but if DeepL releases a new API version, this
    | parameter can be updated without requiring code changes.
    |
    */

    'api_version' => env('DEEPL_API_VERSION', 'v2'),

    /*
    |--------------------------------------------------------------------------
    | Retry on Failures
    |--------------------------------------------------------------------------
    |
    | Defines the number of retry attempts in case of connection failure or
    | timeout. The value must be an integer, where 0 means no retries.
    |
    */

    'retry_on_failures' => env('DEEPL_RETRY_ON_FAILURES', 3),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Specifies the maximum time (in seconds) to wait for a response from the
    | DeepL API. If the API does not respond within this time, the request
    | will fail with an error.
    |
    */

    'timeout' => env('DEEPL_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Enable Translation Cache
    |--------------------------------------------------------------------------
    |
    | This parameter determines whether translations should be cached in the
    | database. If set to true, the package will first check if a translation
    | exists in the database before calling the DeepL API.
    |
    */

    'enable_translation_cache' => env('DEEPL_ENABLE_TRANSLATION_CACHE', true),
];
