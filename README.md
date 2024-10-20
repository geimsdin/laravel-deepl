# Laravel Deepl

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pavelzanek/laravel-deepl.svg?style=flat-square)](https://packagist.org/packages/pavelzanek/laravel-deepl)
[![Total Downloads](https://img.shields.io/packagist/dt/pavelzanek/laravel-deepl.svg?style=flat-square)](https://packagist.org/packages/pavelzanek/laravel-deepl)
[![GitHub Issues](https://img.shields.io/github/issues/PavelZanek/laravel-deepl.svg?style=flat-square)](https://github.com/PavelZanek/laravel-deepl/issues)
[![License](https://img.shields.io/github/license/PavelZanek/laravel-deepl.svg?style=flat-square)](https://github.com/PavelZanek/laravel-deepl/blob/main/LICENSE.md)

## Introduction

Laravel Deepl is a Laravel package that serves as a wrapper for the official [DeepL PHP client](https://github.com/DeepLcom/deepl-php), enhancing it with additional features such as caching and chainable translation methods. This package leverages the robustness of the official client while providing a more Laravel-centric interface. For comprehensive details about the DeepL API, including available endpoints and parameters, please refer to the [DeepL API Documentation](https://developers.deepl.com/docs).

## Requirements

Before installing and using this package, please ensure your environment meets the following minimum requirements:

- **PHP 8.2** or higher
- **Laravel 11.0** or higher
- **GuzzleHTTP 7.0** or higher

These versions are required to ensure compatibility with the package and the features it provides. Make sure your project is running on these versions or higher before integrating this package.

## Installation

You can install the package via Composer:

```bash
composer require pavelzanek/laravel-deepl
```

### Configuration

To publish the configuration file, run:

```bash
php artisan vendor:publish --tag=laravel-deepl-config
```

This will create a `config/laravel-deepl.php` file where you can set your DeepL API key and other configuration options.

### Migrations

The package includes a migration that creates the translations table used for caching translations (more info: [Text Translation](#text-translation)). To publish the migration files, run:

```bash
php artisan vendor:publish --tag=laravel-deepl-migrations
```

This command will copy the migration files to your application’s `database/migrations` directory.

After publishing the migrations, you need to run them to create the necessary database tables:

```bash
php artisan migrate
```

This will execute the migration and create the translations table in your database, enabling the caching feature.

### Environment Variables

Add your DeepL API key to your `.env` file:

```dotenv
DEEPL_API_KEY=your-deepl-api-key
```

Other environment variables you might want to set:

```dotenv
DEEPL_DEFAULT_SOURCE_LANG=en
DEEPL_DEFAULT_TARGET_LANG=cs
DEEPL_RETRY_ON_FAILURES=3
DEEPL_TIMEOUT=30

DEEPL_ENABLE_TRANSLATION_CACHE=true
DEEPL_TRANSLATION_CACHE_TABLE=your_custom_table_name

DEEPL_ENABLE_ON_THE_FLY_TRANSLATION=true
DEEPL_ON_THE_FLY_OUTSIDE_LOCAL=false
DEEPL_ON_THE_FLY_SOURCE_LANG=en
DEEPL_ON_THE_FLY_USE_QUEUE_FOR_TRANSLATION=true
```

## Usage

### Usage Options: Facade vs. Client

The package offers two primary ways to interact with the DeepL API: using the Facade or directly using the `DeeplClient` class. Additionally, the `translateText` method now supports method chaining, allowing for more flexible and readable translation requests.

**Using the Facade:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$translatedText = Deepl::translateText('Hello, world!', 'en', 'de');

echo $translatedText; // Outputs: Hallo, Welt!
```

**Using the DeeplClient:**

```php
use PavelZanek\LaravelDeepl\DeeplClient;

$client = app(DeeplClient::class);

$translatedText = $client->translateText('Hello, world!', 'en', 'de');

echo $translatedText; // Outputs: Hallo, Welt!
```

**Note:** The result is returned as an object from the official DeepL API PHP package.
For more details on the response structure, refer to the [official documentation](https://www.deepl.com/docs-api).

### Text Translation

You can easily translate text using the provided client:

**Example Using the Facade:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$translatedText = Deepl::translateText('Hello, world!', 'en', 'de');

echo $translatedText; // Outputs: Hallo, Welt!
```

**Example Using the DeeplClient:**

Basic Usage Example of DeeplClient for Translating Text:

```php
use PavelZanek\LaravelDeepl\DeeplClient;

$client = app(DeeplClient::class);

$translatedText = $client->translateText('Hello, world!', 'en', 'de');

echo $translatedText; // Outputs: Hallo, Welt!
```

Integrating DeeplClient into a Laravel Controller for example:

```php
<?php

namespace App\Http\Controllers;

use PavelZanek\LaravelDeepl\DeeplClient;

class TestController extends Controller
{
    public function __construct(
        protected readonly DeeplClient $deeplClient
    ) {}

    public function translateText(): void
    {
        $translation = $this->deeplClient->translateText(
            texts: 'Ahoj, jak se máš?',
            sourceLang: 'cs',
            targetLang: 'en-gb'
        );
    
        dd($translation);

        //    DeepL\TextResult {
        //        +text: "Hey, how are you?"
        //        +detectedSourceLang: "cs"
        //        +billedCharacters: 17
        //    }

        $translation = $this->deeplClient->translateText(
            texts: ['Ahoj, jak se máš?', 'Mám se dobře, jak se máš ty?'],
            sourceLang: 'cs',
            targetLang: 'en-gb'
        );

        dd($translation);

        //    array [
        //        0 => DeepL\TextResult {
        //            +text: "Hey, how are you?"
        //            +detectedSourceLang: "cs"
        //            +billedCharacters: 17
        //        }
        //        1 => DeepL\TextResult {
        //            +text: "I'm fine, how are you?"
        //            +detectedSourceLang: "cs"
        //            +billedCharacters: 28
        //        }
        //    ]
    }
}
```

#### Method Chaining

When utilizing the chainable methods provided by the `translateText` function, **do not pass the `text` as an argument**. Instead, call `translateText` without any parameters to receive a builder instance that allows method chaining. Passing the text directly will bypass the builder and prevent the use of chainable methods.

The `translateText` method supports method chaining, allowing you to set additional options and parameters in a more readable and concise way:

**Example Using the Facade:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$translatedText = Deepl::translateText()
    ->text('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->formality('less')
    ->preserveFormatting('enabled')
    ->splitSentences('1')
    ->glossary('example-glossary-id')
    ->withoutCache()
    ->translate();

echo $translatedText; // Outputs: Hallo, Welt!
```

**Example Using the DeeplClient:**

```php
use PavelZanek\LaravelDeepl\DeeplClient;

$client = app(DeeplClient::class);

$translatedText = $client->translateText()
    ->text('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->formality('less')
    ->preserveFormatting('enabled')
    ->splitSentences('1')
    ->glossary('example-glossary-id')
    ->withoutCache()
    ->translate();

echo $translatedText; // Outputs: Hallo, Welt!
```

**Available Chainable Methods**

The translateText method returns a builder instance that allows you to chain the following methods:

- `text(string|array $texts)`: Sets the text(s) to translate.
- `sourceLang(string $sourceLang)`: Sets the source language code.
- `targetLang(string $targetLang)`: Sets the target language code.
- `formality(string $formality)`: Sets the formality level of the translation.
- `preserveFormatting(string $preserveFormatting)`: Controls whether to preserve the original formatting.
- `splitSentences(string|bool $split)`: Configures how sentences should be split during translation.
- `glossary(string $glossaryId)`: Sets the glossary ID to use for the translation.
- `withoutCache()`: Disables the use of the translation cache for this request.
- `translate()`: Executes the translation and returns the result.

#### Using the options Parameter

The `translateText` method accepts an optional options array as its fourth argument, allowing you to specify additional translation settings. Alternatively, you can use chainable methods to set these options.

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$translatedText = Deepl::translateText(
    'Hello, world!',
    'en',
    'de',
    [
        'formality' => 'less',
        'preserve_formatting' => 'enabled',
        'split_sentences' => '1',
        'glossary_id' => 'example-glossary-id',
    ],
)->translate();

echo $translatedText; // Outputs: Hallo, Welt!
```

**Using Chainable Methods**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$translatedText = Deepl::translateText()
    ->text('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->options([
        'formality' => 'less',
        'preserve_formatting' => 'enabled',
        'split_sentences' => '1',
        'glossary_id' => 'example-glossary-id',
    ])
    ->translate();
    
echo $translatedText; // Outputs: Hallo, Welt!
```

#### Caching Translations

The package includes a caching mechanism that stores translations in the database to optimize performance and reduce the number of API calls to DeepL. This can be especially useful when translating the same text multiple times or when working with large volumes of text.

#### Running the Migration

Before you can use the caching feature, you need to publish and run the migration that creates the `translations` table in your database:

```bash
php artisan vendor:publish --tag=laravel-deepl-migrations
php artisan migrate
```

This will create the necessary table where translations will be stored.

#### How Caching Works

When you request a translation, the package checks if the translation already exists in the database before making an API call to DeepL. This is controlled by the `useCache` property.

- **If the translation exists in the cache:** The cached translation is returned, avoiding an API call.
- **If the translation does not exist in the cache:** An API call is made to DeepL, and the translation result is stored in the database for future requests.

**Customizing Cache Behavior**

The caching mechanism uses the `TranslationCache` model to store the translated texts. The cache table name can be customized via the `DEEPL_TRANSLATION_CACHE_TABLE` environment variable. By default, it is set to `translations_cache`.

```dotenv
DEEPL_ENABLE_TRANSLATION_CACHE=true
DEEPL_TRANSLATION_CACHE_TABLE=your_custom_table_name
```

This option can also be set directly in the configuration file:

```php
// config/laravel-deepl.php

return [
    // ...
    'enable_translation_cache' => env('DEEPL_ENABLE_TRANSLATION_CACHE', false),
    'translation_cache_table' => env('DEEPL_TRANSLATION_CACHE_TABLE', 'translations_cache'),
    // ...
];
```

Disabling this option means that every translation request will result in an API call to DeepL, which could increase your API usage costs.

**Benefits of Caching**

- **Performance:** Reduces the load on the DeepL API by reusing translations.
- **Cost Savings:** Helps to minimize the number of API calls, reducing potential costs.
- **Flexibility:** Easily bypass or disable the cache when needed for fresh translations.

The caching feature is a powerful tool for optimizing your application’s localization workflow, ensuring that **translations are both fast and cost-effective**.

#### Important Note on Caching and Options

The translation cache is sensitive to the options used in the translation request. Any change in the options (such as `formality`, `splitSentences`, `preserveFormatting`, etc.) will lead to a different cache entry. Ensure that you consider this when working with translations that require specific options, as the cache will reflect these variations.

```php
$translatedText = $client->translateText()
    ->text('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->translate();

$translatedText = $client->translateText()
    ->text('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->formality('less')
    ->translate();
```

In the first case, the translation is performed with default settings. In the second case, the translation includes the formality option set to `less`. Even though both translations are for the same text and languages, they will produce different results and therefore be cached separately.

#### Example Usage

```php
use PavelZanek\LaravelDeepl\DeeplClient;

$client = new DeeplClient();

$translatedText = $client->translateText()
    ->text('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->translate();

echo $translatedText; // Outputs: Hallo, Welt! (from cache or API)
```

In this example, the translation of _“Hello, world!”_ from English to German is either retrieved from the cache or, if not cached, obtained from DeepL and then stored in the cache for future use.

#### Disabling Cache

If you need to bypass the cache and always make a fresh API call, you can use the `withoutCache` method:

**Using the `useCache` Parameter:**

The `translateText` method includes a fifth parameter, `useCache`, which determines whether to utilize the translation caching mechanism. This parameter is optional and defaults to the value specified in your configuration (`config('laravel-deepl.enable_translation_cache')`).

- **Type:** `?bool` (nullable boolean)
- **Default:** `null` (which means it will fallback to the configuration setting)

**Using the useCache Parameter Directly:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$translatedText = Deepl::translateText(
    'Hello, world!',
    'en',
    'de',
    [],        // No additional options
    false      // Disables cache usage for this request
)->translate();

// or if u prefer named parameters
$translatedText = Deepl::translateText(
    text: 'Hello, world!',
    sourceLang: 'en',
    targetLang: 'de',
    useCache: false     // Disables cache usage for this request
)->translate();

echo $translatedText; // Always fetches from API
```

**Using Chainable Methods:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$translatedText = Deepl::translateText()
    ->text('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->withoutCache() // Disables cache usage for this request
    ->translate();

echo $translatedText; // Always fetches from API
```

#### Single vs. Multiple Texts

The TranslationBuilder provides two methods for setting the text to translate:

- `text(string $text)`: Sets a single text string for translation.
- `texts(array|string $texts)`: Sets multiple text strings for translation.

**Example Using `text()` for a Single Text:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$translatedText = Deepl::translateText()
    ->text('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->translate();

echo $translatedText; // Outputs: Hallo, Welt!
```

**Example Using `texts()` for Multiple Texts:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$translatedTexts = Deepl::translateText()
    ->texts(['Hello, world!', 'Good morning!'])
    ->sourceLang('en')
    ->targetLang('de')
    ->translate();

foreach ($translatedTexts as $translatedText) {
    echo $translatedText->text . "\n"; // Outputs: Hallo, Welt! and Guten Morgen!
}
```

By utilizing the text() and texts() methods appropriately, you can efficiently handle both single and multiple translation requests within your application.

### Document Translation

You can also translate documents using the provided client or facade.

**Example Using the Facade:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

// Upload the document for translation
$uploadResponse = Deepl::uploadDocument('path/to/document.pdf', 'de');

// Check the status of the translation
$status = Deepl::getDocumentStatus($uploadResponse['document_id'], $uploadResponse['document_key']);

if ($status->status === \PavelZanek\LaravelDeepl\Enums\V2\DocumentStatus::DONE->value) {
    // Download the translated document
    $translatedDocument = Deepl::downloadDocument($uploadResponse['document_id'], $uploadResponse['document_key']);
    file_put_contents('path/to/translated-document.pdf', $translatedDocument);
}
```

**Example Using the DeeplClient:**

```php
use PavelZanek\LaravelDeepl\DeeplClient;

$client = app(DeeplClient::class);

// Upload the document for translation
$uploadResponse = $client->uploadDocument('path/to/document.pdf', 'de');

// Check the status of the translation
$status = $client->getDocumentStatus($uploadResponse['document_id'], $uploadResponse['document_key']);

if ($status->status === \PavelZanek\LaravelDeepl\Enums\V2\DocumentStatus::DONE->value) {
    // Download the translated document
    $translatedDocument = $client->downloadDocument($uploadResponse['document_id'], $uploadResponse['document_key']);
    file_put_contents('path/to/translated-document.pdf', $translatedDocument);
}
```

#### Method Chaining for Document Translation

With the introduction of the `DocumentTranslationBuilder`, you can now perform document translations using chainable methods, making your code more organized and readable. **Do not pass the `inputFile` and `outputFile` directly as arguments** when using chainable methods. Instead, use the builder to set these paths along with other optional parameters.

**Example Using the Facade:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

// Perform a document translation with chainable methods
$status = Deepl::translateDocumentBuilder()
    ->inputFile('path/to/input.docx')
    ->outputFile('path/to/output.docx')
    ->sourceLang('en')
    ->targetLang('de')
    ->enableMinification()
    ->options([
        'formality' => 'more',
        // Add other options as needed
    ])
    ->translate();

echo $status->status; // Outputs: done, translating, etc.
```

**Example Using the DeeplClient:**

```php
use PavelZanek\LaravelDeepl\DeeplClient;

$client = app(DeeplClient::class);

// Perform a document translation with chainable methods
$status = $client->translateDocumentBuilder()
    ->inputFile('path/to/input.pdf')
    ->outputFile('path/to/output.pdf')
    ->sourceLang('en')
    ->targetLang('fr')
    ->translate();

echo $status->status; // Outputs: done, translating, etc.
```

**Available Chainable Methods for Document Translation:**

The DocumentTranslationBuilder provides the following chainable methods:

- `inputFile(string $file)`: Sets the path to the input document.
- `outputFile(string $file)`: Sets the path to save the translated document.
- `sourceLang(string $lang)`: Sets the source language code.
- `targetLang(string $lang)`: Sets the target language code.
- `options(array $options)`: Adds additional options for the translation.
- `enableMinification(bool $enable = true)`: Enables document minification.
- `translate()`: Executes the document translation and returns the `DocumentStatus`.

#### Additional Features

The underlying DeepL PHP API offers more advanced options for document translation, such as specifying translation formality, using glossaries, or enabling document minification for larger files (e.g., PowerPoint presentations with many images).

For example, you can use the `translateDocument` method to set formality or enable document minification:

```php
$client->translateDocument(
    'path/to/input.docx',
    'path/to/output.docx',
    'en',
    'de',
    [
        'formality' => 'more',
        \DeepL\TranslateDocumentOptions::ENABLE_DOCUMENT_MINIFICATION => true,
    ]
);
```

Document minification helps to reduce the size of the document before translation by removing or compressing large media files, ensuring that the file meets the DeepL API size limits. For more details on supported file types, media formats, and additional options, please refer to the [DeepL PHP API documentation](https://github.com/DeepLcom/deepl-php?tab=readme-ov-file#translating-documents).

### Glossary Management

You can create, retrieve, list, and delete glossaries using the provided client or facade. With the introduction of the `CreateGlossaryBuilder`, creating glossaries has become more flexible and streamlined through method chaining.

#### Creating a Glossary

**Example Using the Facade:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

// Create a glossary
$glossary = Deepl::createGlossary(
    'My Glossary',
    'en',
    'de',
    [
        'hello' => 'hallo',
        'world' => 'welt',
    ]
);

// Retrieve glossary details
$glossaryDetails = Deepl::getGlossary($glossary->glossaryId);

// Delete a glossary
Deepl::deleteGlossary($glossary->glossaryId);
```

**Example Using the DeeplClient:**

```php
use PavelZanek\LaravelDeepl\DeeplClient;

$client = app(DeeplClient::class);

// Create a glossary
$glossary = $client->createGlossary(
    'My Glossary',
    'en',
    'de',
    [
        'hello' => 'hallo',
        'world' => 'welt',
    ]
);

// Retrieve glossary details
$glossaryDetails = $client->getGlossary($glossary->glossaryId);

// Delete a glossary
$client->deleteGlossary($glossary->glossaryId);
```

#### Method Chaining for Glossary Creation

**Example Using the Facade with `CreateGlossaryBuilder`:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

try {
    $glossaryInfo = Deepl::createGlossaryBuilder()
        ->name('My Glossary')
        ->sourceLang('en')
        ->targetLang('de')
        ->addEntry('artist', 'Künstler')
        ->addEntry('prize', 'Preis')
        ->create();

    echo "Created '{$glossaryInfo->name}' ({$glossaryInfo->glossaryId}) ";
    echo "{$glossaryInfo->sourceLang} to {$glossaryInfo->targetLang} ";
    echo "containing {$glossaryInfo->entryCount} entries\n";
} catch (\Exception $e) {
    echo "Error creating glossary: " . $e->getMessage();
}
```

**Example Using the DeeplClient with `CreateGlossaryBuilder`:**

```php
use PavelZanek\LaravelDeepl\DeeplClient;

$client = app(DeeplClient::class);

try {
    $glossaryInfo = $client->createGlossaryBuilder()
        ->name('My Glossary')
        ->sourceLang('en')
        ->targetLang('de')
        ->addEntry('artist', 'Künstler')
        ->addEntry('prize', 'Preis')
        ->create();

    echo "Created '{$glossaryInfo->name}' ({$glossaryInfo->glossaryId}) ";
    echo "{$glossaryInfo->sourceLang} to {$glossaryInfo->targetLang} ";
    echo "containing {$glossaryInfo->entryCount} entries\n";
} catch (\Exception $e) {
    echo "Error creating glossary: " . $e->getMessage();
}
```

**Adding Multiple Glossary Entries**

You can add multiple glossary entries either by passing an associative array or by using a `GlossaryEntries` object.

**Adding Entries Using an Associative Array:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

try {
    $glossaryInfo = Deepl::createGlossaryBuilder()
        ->name('Extended Glossary')
        ->sourceLang('en')
        ->targetLang('de')
        ->addEntries([
            'hello' => 'hallo',
            'world' => 'welt',
            'goodbye' => 'auf Wiedersehen',
        ])
        ->create();

    echo "Created '{$glossaryInfo->name}' with ID {$glossaryInfo->glossaryId}";
} catch (\Exception $e) {
    echo "Error creating glossary: " . $e->getMessage();
}
```

**Adding Entries Using a GlossaryEntries Object:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;
use DeepL\GlossaryEntries;

$entries = GlossaryEntries::fromEntries([
    'computer' => 'Computer',
    'internet' => 'Internet',
]);

try {
    $glossaryInfo = Deepl::createGlossaryBuilder()
        ->name('Tech Glossary')
        ->sourceLang('en')
        ->targetLang('de')
        ->addEntriesFromGlossaryEntries($entries)
        ->create();

    echo "Created '{$glossaryInfo->name}' with ID {$glossaryInfo->glossaryId}";
} catch (\Exception $e) {
    echo "Error creating glossary: " . $e->getMessage();
}
```

**Summary of Available Methods in `CreateGlossaryBuilder`**

- `name(string $name)`: Sets the glossary name.
- `sourceLang(string $lang)`: Sets the source language code.
- `targetLang(string $lang)`: Sets the target language code.
- `addEntry(string $source, string $target)`: Adds a single glossary entry.
- `addEntries(array $entries)`: Adds multiple glossary entries from an associative array.
- `addEntriesFromGlossaryEntries(GlossaryEntries $glossaryEntries)`: Adds multiple glossary entries from a `GlossaryEntries` object.
- `create()`: Executes the glossary creation and returns a `GlossaryInfo` object.

#### Additional Features

The official DeepL API offers advanced functionality for glossary management, such as uploading glossaries from CSV files and listing glossary entries. You can also use stored glossaries for both text and document translation.

**CSV Glossary Example:**

```php
$csvData = file_get_contents('/path/to/glossary_file.csv');
$myCsvGlossary = $client->createGlossaryFromCsv('CSV glossary', 'en', 'de', $csvData);
```

To see all stored glossaries, retrieve glossary entries, or use glossaries in translations, refer to the [DeepL PHP API documentation](https://github.com/DeepLcom/deepl-php?tab=readme-ov-file#glossaries).

### Language Support

You can list all supported source and target languages, including details about which target languages support the formality option, and you can also retrieve supported glossary language pairs.

**Example Using the Facade:**

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

// Get source languages
$sourceLanguages = Deepl::getSourceLanguages();
foreach ($sourceLanguages as $sourceLanguage) {
    echo $sourceLanguage->name . ' (' . $sourceLanguage->code . ')';
    // Example: 'English (en)'
}

// Get target languages
$targetLanguages = Deepl::getTargetLanguages();
foreach ($targetLanguages as $targetLanguage) {
    $supportsFormality = $targetLanguage->supportsFormality ? 'supports formality' : 'does not support formality';
    echo $targetLanguage->name . ' (' . $targetLanguage->code . ') ' . $supportsFormality;
    // Example: 'German (de) supports formality'
}

// Get glossary-supported language pairs
$glossaryLanguages = Deepl::getGlossaryLanguages();
foreach ($glossaryLanguages as $glossaryLanguage) {
    echo $glossaryLanguage->sourceLang . ' to ' . $glossaryLanguage->targetLang;
    // Example: 'en to de'
}
```

**Example Using the DeeplClient:**

```php
use PavelZanek\LaravelDeepl\DeeplClient;

$client = app(DeeplClient::class);

// Get source languages
$sourceLanguages = $client->getSourceLanguages();
foreach ($sourceLanguages as $sourceLanguage) {
    echo $sourceLanguage->name . ' (' . $sourceLanguage->code . ')';
    // Example: 'English (en)'
}

// Get target languages
$targetLanguages = $client->getTargetLanguages();
foreach ($targetLanguages as $targetLanguage) {
    $supportsFormality = $targetLanguage->supportsFormality ? 'supports formality' : 'does not support formality';
    echo $targetLanguage->name . ' (' . $targetLanguage->code . ') ' . $supportsFormality;
    // Example: 'German (de) supports formality'
}

// Get glossary-supported language pairs
$glossaryLanguages = $client->getGlossaryLanguages();
foreach ($glossaryLanguages as $glossaryLanguage) {
    echo $glossaryLanguage->sourceLang . ' to ' . $glossaryLanguage->targetLang;
    // Example: 'en to de'
}
```

#### Chainable Methods for Language Support

Leveraging the LanguageBuilder for a more fluent and flexible approach.

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$languages = Deepl::languageBuilder()
    ->source()
    ->target()
    ->glossary()
    ->get();

// Accessing Source Languages
foreach ($languages['source_languages'] as $sourceLanguage) {
    echo $sourceLanguage->name . ' (' . $sourceLanguage->code . ")\n";
    // Example: 'English (en)'
}

echo "\n";

// Accessing Target Languages
foreach ($languages['target_languages'] as $targetLanguage) {
    $supportsFormality = $targetLanguage->supportsFormality ? 'supports formality' : 'does not support formality';
    echo $targetLanguage->name . ' (' . $targetLanguage->code . ') ' . $supportsFormality . "\n";
    // Example: 'German (de) supports formality'
}

echo "\n";

// Accessing Glossary-Supported Language Pairs
foreach ($languages['glossary_languages'] as $glossaryLanguage) {
    echo $glossaryLanguage->sourceLang . ' to ' . $glossaryLanguage->targetLang . "\n";
    // Example: 'en to de'
}
```

**Available Chainable Methods**

The LanguageBuilder provides the following chainable methods:

- `source()`: Sets the builder to retrieve source languages.
- `target()`: Sets the builder to retrieve target languages.
- `glossary()`: Sets the builder to retrieve glossary-supported languages.
- `get()`: Executes the language retrieval based on set flags and returns the results.

### Usage Limits

Monitoring your API usage is essential to ensure that you do not exceed your DeepL plan limits. The package provides mechanisms to retrieve and display usage information.

#### Retrieving Usage Information

You can retrieve the current usage and quota information from the DeepL API to monitor your translation usage:

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$usage = Deepl::usage()->getUsage();
```
This will return an object containing details about your API usage, including the number of characters translated and any other relevant quota limits.

#### Usage Command

The package provides an Artisan command to retrieve and display usage information directly from the command line.

**Command Syntax:**

```bash
php artisan deepl:usage
```

**Example Output:**

```plaintext
+-----------------------+--------+---------+-----------+------------+
| Usage Type            | Count  | Limit   | Remaining | Percentage |
+-----------------------+--------+---------+-----------+------------+
| Translated Characters | 14,464 | 500,000 | 485,536   | 2.89%      |
| Total                 | 14,464 | 500,000 | 485,536   | 2.89%      |
+-----------------------+--------+---------+-----------+------------+
```

#### Command Details

The `deepl:usage` command retrieves usage information within the current billing period along with account limits. It displays the data in a formatted table, highlighting usage types that are approaching or have exceeded their limits.

#### Handling Different Usage Types

The command can display various usage types, such as:

- Translated Characters
- Translated Documents
- Translated Team Documents
- Total

Each usage type includes the count, limit, remaining quota, and the percentage of the limit used.

**Color-Coded Warnings**

- **Red:** Indicates that the usage limit has been exceeded.
- **Yellow:** Indicates that the usage is approaching the limit (e.g., 90% or more of the limit).

## Translating Localization Files

The package includes a convenient Artisan command for translating Laravel localization files using the DeepL API. This allows you to quickly translate your application’s language files from one language to another. **However, it’s always important to review the generated translations and verify their correctness. Human oversight is crucial to ensure that translations are accurate, appropriate, and contextually relevant for your application.**

### Translating a Single Localization File

#### Command Syntax

You can use the command as follows:

```bash
php artisan deepl:translate {file} --sourceLang=en --targetLang=cs [--with-pint]
```

- `file`: The path to the localization file you wish to translate
- `--sourceLang`: The source language code (default is en)
- `--targetLang`: The target language code (default is cs)
- `--with-pint`: (Optional) If provided, the command will run Pint (a code formatter) after translation, but only in the local environment.

#### Example

To translate a file from English to Czech, you can run:

```bash
php artisan deepl:translate lang/en/messages.php --sourceLang=en --targetLang=cs
```

This will create a translated file at `lang/cs/messages.php`, preserving the structure and formatting of the original file.

### Translating Entire Localization Folders

#### Command Syntax

To translate all localization files within a folder (including subdirectories), use the following command:

```bash
php artisan deepl:translate-folder {folder} --sourceLang=en --targetLang=cs [--with-pint]
```

- `folder`: The path to the folder containing localization files to translate.
- `--sourceLang`: The source language code (default is en).
- `--targetLang`: The target language code (default is cs).
- `--with-pint`: (Optional) If provided, the command will run Pint (a code formatter) after translation, but only in the local environment.

#### Example

To translate all files in the English localization folder to Czech, run:

```bash
php artisan deepl:translate-folder lang/en --sourceLang=en --targetLang=cs
```

This command will recursively traverse all files and subdirectories within `lang/en`, translating each file and saving the translated versions in the corresponding target language directory (e.g., `lang/cs`).

**Laravel 10 and Below Compatibility**

In Laravel 10 and earlier versions, the localization files are stored in `resources/lang`. This package supports both Laravel 10 (or below) and Laravel 11. To ensure compatibility, use the appropriate folder path when running the commands:

- For Laravel 11 and higher, the localization files are located in the `lang/` directory at the project root. 
- For Laravel 10 and below, the localization files are in the `resources/lang/` directory.

**Example**

Laravel 11:

```bash
php artisan deepl:translate-folder lang/en --sourceLang=en --targetLang=cs
```

Laravel 10 and below:

```bash
php artisan deepl:translate-folder resources/lang/en --sourceLang=en --targetLang=cs
```

This package will check both paths (`lang/` and `resources/lang/`) and will prevent translating the root folder, ensuring only subdirectories (like `lang/en` or `resources/lang/en`) are translated.

### Handling the Root lang Folder

When using the `deepl:translate-folder` command, the package automatically skips translating the root `lang` folder (or `resources/lang` in Laravel 10 and below). This is because translating the entire `lang` directory is not recommended. Instead, you should specify a subfolder, such as `lang/en` or `resources/lang/en`, to translate localization files for a specific language. If the root `lang` folder is provided, the command will skip it and display a warning without interrupting the process.

### Handling JSON and PHP Files

Both commands support both JSON and PHP localization files. They will automatically detect the file type based on the file extension and handle the translation appropriately.

- **For JSON files:** The translations are saved in JSON format, with keys and values preserved
- **For PHP files:** The translations are saved in PHP array syntax, with keys and values maintained

#### Important Note on Localization File Structure

It’s important to note that examples like `lang/en/messages.json` used in this documentation are purely illustrative. While it’s technically possible to structure your localization files this way, the recommended approach, especially for JSON-based translations, follows a different convention. Laravel encourages storing language-specific JSON files directly in the root of the `lang` folder, such as `lang/en.json`, `lang/cs.json`, etc., where each file represents translations for a specific language. This method allows for easier management of translations across multiple languages, without needing separate subdirectories for each language. By adhering to this structure, Laravel can automatically detect the appropriate JSON file based on the language setting and ensure that the translations are loaded correctly. This best practice also streamlines the process of adding new languages, as you simply need to create a new JSON file for the target language, such as `fr.json` for French, and fill in the necessary translations.

#### Example of Content Before and After Translation (JSON File)

**Original File** (`lang/en/messages.json`):

```json
{
    "welcome": "Welcome to our application!",
    "user": {
        "profile": "Your profile",
        "settings": "Account settings"
    },
    "greeting": "Hello, :name!"
}
```

**Translated File** (`lang/cs/messages.json`):

```json
{
    "welcome": "Vítejte v naší aplikaci!",
    "user": {
        "profile": "Váš profil",
        "settings": "Nastavení účtu"
    },
    "greeting": "Ahoj, :name!"
}
```

#### Example of Content Before and After Translation (PHP File)

**Original File** (`lang/en/messages.php`):

```php
<?php

return [
    'welcome' => 'Welcome to our application!',
    'user' => [
        'profile' => 'Your profile',
        'settings' => 'Account settings',
    ],
    'greeting' => 'Hello, :name!',
];
```

**Translated File** (`lang/cs/messages.php`):

```php  
<?php

return [
    'welcome' => 'Vítejte v naší aplikaci!',
    'user' => [
        'profile' => 'Váš profil',
        'settings' => 'Nastavení účtu',
    ],
    'greeting' => 'Ahoj, :name!',
];
```

### Handling Existing Translations

When translating localization files, the commands are designed to be efficient and avoid overwriting existing translations. **If a key already exists in the target localization file, the command will skip translating that key.** This means that only keys that are not present in the target file will be translated and added.

This behavior is particularly useful when you have previously translated keys or when you want to update your localization files incrementally. By skipping existing keys, the command ensures that your existing translations remain untouched, and only new or missing keys are added.

#### Example

Suppose you have a target localization file that already contains some translations.

**Existing Target File** (`lang/cs/messages.php`):

```php
<?php

return [
    'welcome' => 'Vítejte v naší aplikaci!',
    // The 'user' key is missing
    'greeting' => 'Ahoj, :name!',
];
```

If you run the translation command:

```bash
php artisan deepl:translate lang/en/messages.php --sourceLang=en --targetLang=cs
```

The command will detect that the keys `welcome` and `greeting` already exist in the target file and will skip translating them. It will only translate and add the missing `user` key.

**Updated Target File** (`lang/cs/messages.php`):

```php
<?php

return [
    'welcome' => 'Vítejte v naší aplikaci!',
    'user' => [
        'profile' => 'Váš profil',
        'settings' => 'Nastavení účtu',
    ],
    'greeting' => 'Ahoj, :name!',
];
```

In this way, the command preserves your existing translations and focuses on filling in any gaps with new translations.

### Placeholders

The command is smart enough to handle placeholders (e.g., `:attribute`) within your localization strings. It ensures that these placeholders remain unchanged during the translation process, preserving the integrity of your application.

### Directory Creation

If the target directory does not exist, the command will automatically create it, ensuring that the translated file is saved in the correct location.

### Running Pint Code Formatter

Both commands (`deepl:translate` and `deepl:translate-folder`) support the `--with-pint` option, which allows you to run Pint (a PHP code formatter) after the translation is complete.

Pint will only run if your application is in the local environment. This prevents unintended code formatting changes in production or other environments.

**Note:** Before using the `--with-pint` option, make sure that you have Pint installed in your project. Pint is typically installed as a development dependency via Composer. You can install it using the following command:

```bash
composer require laravel/pint --dev
```

If Pint is not installed, the command will fail when attempting to run Pint. Ensuring that Pint is installed will allow the code formatter to run successfully after translation.

#### Example

To translate all files in the English localization folder to Czech and run Pint after translation, use:

```bash
php artisan deepl:translate-folder resources/lang/en --targetLang=cs --with-pint
```

## On-the-Fly Translation Setup

The package provides an **on-the-fly** translation feature that dynamically translates missing keys at runtime using the DeepL API. This can be helpful when you want to automatically handle missing translations without pre-generating them.

### Registering Custom Translator

To use the **on-the-fly** translation feature, you need to register a custom translator in your application's `AppServiceProvider`. This custom translator will replace Laravel's default translation service with one that integrates with this package.

You can add the following code to your `AppServiceProvider`: 

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Translation\Loader as LoaderContract;
use Illuminate\Translation\FileLoader;
use Illuminate\Filesystem\Filesystem;
use PavelZanek\LaravelDeepl\Services\TranslationService;
use PavelZanek\LaravelDeepl\Services\TranslatorService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register this only if you intend to utilize the queue for handling translations
        $this->app->singleton(LoaderContract::class, function ($app) {
            return new FileLoader(new Filesystem(), lang_path());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->extend('translator', function ($translator, $app) {
            $loader = $app['translation.loader'];
            $translationService = $app->make(TranslationService::class);

            return new TranslatorService($loader, $app, $translationService);
        });
    }
}
```

### Using the Queue for Translations

If you want the missing translation keys to be processed in the background using Laravel’s queue system, include the register method code snippet in your `AppServiceProvider`. This will dispatch the translation jobs to the queue, avoiding any delays during runtime.

Without this, the translations will be processed immediately when they are requested, which may cause a delay if there is a high number of missing translations.

### Configuration Options

Ensure that the following configuration settings in `config/laravel-deepl.php` are enabled or adjusted based on your needs:

**Enable On-The-Fly Translation:**

```php
'enable_on_the_fly_translation' => env('DEEPL_ENABLE_ON_THE_FLY_TRANSLATION', true),
```

**Translate Outside Local Environment:**

```php
'on_the_fly_outside_local' => env('DEEPL_ON_THE_FLY_OUTSIDE_LOCAL', false),
```

**On-The-Fly Source Language:**

```php
'on_the_fly_source_lang' => env('DEEPL_ON_THE_FLY_SOURCE_LANG', 'en'),
```

**Use Queue for Translation:**

```php
'on_the_fly_use_queue_for_translation' => env('DEEPL_ON_THE_FLY_USE_QUEUE_FOR_TRANSLATION', true),
```

These settings allow you to control how on-the-fly translations are handled, including whether they are processed immediately or dispatched to a queue for asynchronous processing.

## Helpers

### Enumerations

This package provides a set of enumerations (enums) to simplify and standardize the use of certain options and parameters when interacting with the DeepL API. These enums help ensure that your code remains consistent and less prone to errors.

#### Available Enums

Here are some of the enums included in the package:

- **DocumentStatus**: Represents the status of a document translation.
    - `DocumentStatus::DONE`
    - `DocumentStatus::TRANSLATING`

- **Formality**: Allows you to control the formality level of the translated text.
    - `Formality::DEFAULT`
    - `Formality::MORE`
    - `Formality::LESS`
    - `Formality::PREFER_MORE`
    - `Formality::PREFER_LESS`

- **PreserveFormatting**: Controls whether the original formatting is preserved in the translation.
    - `PreserveFormatting::DISABLED`
    - `PreserveFormatting::ENABLED`

- **SourceLanguage**: Lists the available source languages.
    - Includes languages such as `SourceLanguage::ENGLISH`, `SourceLanguage::GERMAN`, `SourceLanguage::FRENCH`, etc.
    - Special value: `SourceLanguage::AUTOMATIC` to let DeepL detect the source language automatically.

- **SplitSentences**: Configures how sentences should be split during translation.
    - `SplitSentences::NONE`
    - `SplitSentences::DEFAULT`
    - `SplitSentences::NO_NEWLINES`

- **TargetLanguage**: Lists the available target languages.
    - Includes languages such as `TargetLanguage::ENGLISH_BRITISH`, `TargetLanguage::GERMAN`, `TargetLanguage::SPANISH`, etc.

#### Example Usage

You can use these enums to improve code readability and reduce the chance of errors:

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;
use PavelZanek\LaravelDeepl\Enums\V2\Formality;
use PavelZanek\LaravelDeepl\Enums\V2\SourceLanguage;
use PavelZanek\LaravelDeepl\Enums\V2\TargetLanguage;

$translatedText = Deepl::translateText()
    ->text('How are you today?')
    ->sourceLang(SourceLanguage::ENGLISH->value)
    ->targetLang(TargetLanguage::GERMAN->value)
    ->formality(Formality::PREFER_LESS->value)
    ->translate();

echo $translatedText; // Outputs: Wie geht es dir heute?
```

## Additional Resources

For more in-depth information about the DeepL API, including advanced features and detailed parameter descriptions, please refer to the official [DeepL API Documentation](https://developers.deepl.com/docs).

- **Official DeepL PHP Client:** [GitHub Repository](https://github.com/DeepLcom/deepl-php)
- **DeepL API Reference:** [DeepL API Docs](https://developers.deepl.com/docs)
- **Deepl API Postman Collection**: [Postman Collection](https://www.postman.com/deepl-api)

## Testing

To run the tests, use:

```bash
composer test
```

The test suite covers various aspects, including:

- Translation caching and retrieval.
- Translation using both the Facade and the DeeplClient.
- Translation of JSON and PHP language files.
- Handling of edge cases and error scenarios.
- Document translation processes.
- Glossary management.
- Usage command functionality.

## Linting

You can run PHPStan to ensure code quality:

```bash
composer lint
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email zanek.pavel@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support the Developer

If you find this package helpful and would like to support its ongoing development, consider leaving a tip. Your support is greatly appreciated!

[Leave a Tip](https://streamelements.com/pavelzanek/tip)

Thank you for your generosity!

### About the Developer

This package is developed and maintained by [Pavel Zaněk](https://www.pavelzanek.com/en), a passionate developer with extensive experience in Laravel and PHP development.
