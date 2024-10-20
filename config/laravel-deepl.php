<?php

return [

    /*
    |--------------------------------------------------------------------------
    | General DeepL Configuration
    |--------------------------------------------------------------------------
    |
    | These settings are related to the DeepL API and global translation
    | behavior, including API keys, endpoints, and default language options.
    |
    */

    /*
    | DeepL API Key
    |
    | This key is used to authenticate with the DeepL API. You can obtain it
    | by signing up on the DeepL platform. Ensure that this key is kept safe
    | and not shared publicly.
    */
    'api_key' => env('DEEPL_API_KEY', ''),

    /*
    | Default Source Language
    |
    | This parameter defines the default source language for translating texts
    | if no specific language is provided. The language code must be in line
    | with the language codes supported by DeepL (e.g., 'en' for English).
    */
    'default_source_lang' => env('DEEPL_DEFAULT_SOURCE_LANG', 'en'),

    /*
    | Default Target Language
    |
    | This parameter defines the default target language for translating texts
    | if no specific language is provided. The language code must be in line
    | with the language codes supported by DeepL (e.g., 'de' for German).
    */
    'default_target_lang' => env('DEEPL_DEFAULT_TARGET_LANG', 'cs'),

    /*
    | Retry on Failures
    |
    | Defines the number of retry attempts in case of connection failure or
    | timeout. The value must be an integer, where 0 means no retries.
    */
    'retry_on_failures' => env('DEEPL_RETRY_ON_FAILURES', 3),

    /*
    | Timeout
    |
    | Specifies the maximum time (in seconds) to wait for a response from the
    | DeepL API. If the API does not respond within this time, the request
    | will fail with an error.
    */
    'timeout' => env('DEEPL_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Translation Cache Settings
    |--------------------------------------------------------------------------
    |
    | This section handles caching of translations to avoid redundant API
    | calls. When enabled, translations are stored and retrieved from
    | the database, reducing API usage and improving performance.
    |
    */

    /*
    | Enable Translation Cache
    |
    | This parameter determines whether translations should be cached in the
    | database. If set to true, the package will first check if a translation
    | exists in the database before calling the DeepL API.
    */
    'enable_translation_cache' => env('DEEPL_ENABLE_TRANSLATION_CACHE', false),

    /*
    | Translation Cache Table Name
    |
    | This option allows you to specify the database table name where translations
    | should be stored when caching is enabled.
    */
    'translation_cache_table' => env('DEEPL_TRANSLATION_CACHE_TABLE', 'translations_cache'),

    /*
    |--------------------------------------------------------------------------
    | On-The-Fly Translation Settings
    |--------------------------------------------------------------------------
    |
    | These options configure the on-the-fly translation functionality, which
    | dynamically translates missing keys at runtime. You can adjust whether
    | this feature is enabled, specify environments, and choose to use the
    | queue for background processing of translations.
    |
    */

    /*
    | Enable On-The-Fly Translation
    |
    | This option allows the package to translate missing translation keys
    | at runtime. When enabled, the package will attempt to translate any
    | missing keys in the source language into the target language and
    | return the translated string dynamically.
    |
    | Warning: Be mindful when using this feature in production environments
    | as it may introduce a delay due to the API call required for translation.
    | Consider using caching or queuing for better performance.
    */
    'enable_on_the_fly_translation' => env('DEEPL_ENABLE_ON_THE_FLY_TRANSLATION', true),

    /*
    | On-The-Fly: Translate Outside Local Environment
    |
    | This option determines whether on-the-fly translation should be allowed
    | outside the 'local' environment. If set to true, missing translations
    | will be translated in other environments like 'production' or 'staging'.
    |
    | You may want to disable this option in production environments for better
    | performance, or if translations should be pre-generated instead.
    */
    'on_the_fly_outside_local' => env('DEEPL_ON_THE_FLY_OUTSIDE_LOCAL', false),

    /*
    | On-The-Fly: Source Language
    |
    | This option specifies the source language to be used when performing
    | on-the-fly translations. The value must be one of the language codes
    | supported by DeepL (e.g., 'en' for English, 'fr' for French).
    |
    | If no source language is provided during runtime, this default value
    | will be used.
    */
    'on_the_fly_source_lang' => env('DEEPL_ON_THE_FLY_SOURCE_LANG', 'en'),

    /*
    | On-The-Fly: Use Queue for Translation
    |
    | This option allows on-the-fly translation requests to be handled by
    | Laravel's queue system. When enabled, missing translations will be
    | dispatched as jobs to the queue and processed in the background.
    |
    | This can improve performance and prevent users from experiencing delays
    | due to real-time translation processing. However, the translated string
    | will only become available after the job has been processed.
    */
    'on_the_fly_use_queue_for_translation' => env('DEEPL_ON_THE_FLY_USE_QUEUE_FOR_TRANSLATION', true),

];
