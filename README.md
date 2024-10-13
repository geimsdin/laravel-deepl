# Laravel Deepl

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pavelzanek/laravel-deepl.svg?style=flat-square)](https://packagist.org/packages/pavelzanek/laravel-deepl)
[![Total Downloads](https://img.shields.io/packagist/dt/pavelzanek/laravel-deepl.svg?style=flat-square)](https://packagist.org/packages/pavelzanek/laravel-deepl)
[![GitHub Issues](https://img.shields.io/github/issues/PavelZanek/laravel-deepl.svg?style=flat-square)](https://github.com/PavelZanek/laravel-deepl/issues)
[![License](https://img.shields.io/github/license/PavelZanek/laravel-deepl.svg?style=flat-square)](https://github.com/PavelZanek/laravel-deepl/blob/main/LICENSE.md)

## Table of Contents

- [Introduction](#introduction)
- [Requirements](#requirements)
- [Installation](#installation)
  - [Configuration](#configuration)
  - [Migrations](#migrations)
  - [Environment Variables](#environment-variables)
- [Usage](#usage)
  - [Usage Options: Facade vs. Client](#usage-options-facade-vs-client)
  - [Text Translation](#text-translation)
  - [Document Translation](#document-translation)
  - [Glossary Management](#glossary-management)
  - [Language Support](#language-support)
  - [Usage Limits](#usage-limits)
- [Translating Localization Files](#translating-localization-files)
  - [Translating a Single Localization File](#translating-a-single-localization-file)
  - [Translating Entire Localization Folders](#translating-entire-localization-folders)
  - [Handling JSON and PHP Files](#handling-json-and-php-files)
  - [Placeholders](#placeholders)
  - [Directory Creation](#directory-creation)
  - [Running Pint Code Formatter](#running-pint-code-formatter)
- [Helpers](#helpers)
  - [Enumerations](#enumerations)
- [Testing](#testing)
- [Linting](#linting)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security](#security)
- [License](#license)
- [Support the Developer](#support-the-developer)
- [About the Developer](#about-the-developer)

## Introduction

Laravel Deepl is a Laravel package that integrates with the [DeepL API](https://www.deepl.com/). It allows you to translate text, documents, manage glossaries, and perform other useful tasks using the DeepL service.

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
DEEPL_API_TYPE=free # or pro
DEEPL_DEFAULT_SOURCE_LANG=en
DEEPL_DEFAULT_TARGET_LANG=cs
DEEPL_API_VERSION=v2
DEEPL_RETRY_ON_FAILURES=3
DEEPL_TIMEOUT=30
DEEPL_ENABLE_TRANSLATION_CACHE=true
```

## Usage

### Usage Options: Facade vs. Client

The package offers two primary ways to interact with the DeepL API: using the Facade or directly using the `DeeplClient` class. This flexibility allows you to choose the approach that best fits your application architecture.

### Text Translation

You can easily translate text using the provided client:

```php
use PavelZanek\LaravelDeepl\Facades\Deepl;

$translatedText = Deepl::textTranslation('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->getTranslation();

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

When you request a translation, the package checks if the translation already exists in the database before making an API call to DeepL. This is controlled by the `useCache` property, which is enabled by default.

- **If the translation exists in the cache:** The cached translation is returned, avoiding an API call.
- **If the translation does not exist in the cache:** An API call is made to DeepL, and the translation result is stored in the database for future requests.

#### Important Note on Caching and Options

It’s important to understand that the translation cache is sensitive to the options used in the translation request. For example, the following two translation requests will result in different cached entries:

```php
$translatedText = $client->textTranslation('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->getTranslation();

$translatedText = $client->textTranslation('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->formality('less')
    ->getTranslation();
```

In the first case, the translation is performed with default settings. In the second case, the translation includes the formality option set to `less`. Even though both translations are for the same text and languages, they will produce different results and therefore be cached separately.

This means that any change in the options (such as `formality`, `splitSentences`, `preserveFormatting`, etc.) will lead to a different cache entry. Make sure to consider this when working with translations that require specific options, as the cache will reflect these variations.

#### Example Usage

```php
use PavelZanek\LaravelDeepl\DeeplClient;

$client = new DeeplClient();

$translatedText = $client->textTranslation('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->getTranslation();

echo $translatedText; // Outputs: Hallo, Welt!
```

In this example, the translation of _“Hello, world!”_ from English to German is either retrieved from the cache or, if not cached, obtained from DeepL and then stored in the cache for future use.

**Disabling Cache**

If you need to bypass the cache and always make a fresh API call, you can use the `withoutCache` method:

```php
$translatedText = $client->textTranslation('Hello, world!')
    ->sourceLang('en')
    ->targetLang('de')
    ->withoutCache()  // Disables cache usage
    ->getTranslation();
```

**Customizing Cache Behavior**

The caching mechanism uses the Translation model to store the translated texts. The cache is controlled by the enable_translation_cache option in the configuration file (`config/laravel-deepl.php`), which is set to `true` by default. You can disable caching globally by setting this option to `false`:

```php
// config/laravel-deepl.php

return [
    // ...
    'enable_translation_cache' => false,
    // ...
];
```

Disabling this option means that every translation request will result in an API call to DeepL, which could increase your API usage costs.

**Benefits of Caching**

- **Performance:** Reduces the load on the DeepL API by reusing translations.
- **Cost Savings:** Helps to minimize the number of API calls, reducing potential costs.
- **Flexibility:** Easily bypass or disable the cache when needed for fresh translations.

The caching feature is a powerful tool for optimizing your application’s localization workflow, ensuring that **translations are both fast and cost-effective**.

### Document Translation

You can also translate documents:

```php
$documentClient = Deepl::documentTranslation();
$uploadResponse = $documentClient->uploadDocument('path/to/document.pdf', 'de');

$status = $documentClient->getDocumentStatus($uploadResponse['document_id'], $uploadResponse['document_key']);

if ($status['status'] === \PavelZanek\LaravelDeepl\Enums\V2\DocumentStatus::DONE->value) {
    $translatedDocument = $documentClient->downloadTranslatedDocument($uploadResponse['document_id'], $uploadResponse['document_key']);
    file_put_contents('path/to/translated-document.pdf', $translatedDocument);
}
```

### Glossary Management

You can create, retrieve, and delete glossaries:

```php
$glossaryClient = Deepl::glossary();

$glossary = $glossaryClient->createGlossary(
    'My Glossary',
    'en',
    'de',
    [
        'hello' => 'hallo',
        'world' => 'welt',
    ]
);

$glossaryDetails = $glossaryClient->getGlossary($glossary['glossary_id']);

$glossaryClient->deleteGlossary($glossary['glossary_id']);
```

### Language Support

You can list all supported languages:

```php
$sourceLanguages = Deepl::languages()->getSourceLanguages();
$targetLanguages = Deepl::languages()->getTargetLanguages();
```

### Usage Limits

You can retrieve the current usage and quota information from the DeepL API to monitor your translation usage:

```php
$usage = Deepl::usage()->getUsage();
```

This will return an array containing details about your API usage, including the number of characters translated and any other relevant quota limits. You can use this information to ensure that you do not exceed your API limits.

Additionally, the package provides a convenient Artisan command that allows you to retrieve and display this information directly from the command line:

```bash
php artisan deepl:usage
```

This command will display the number of characters translated in the current billing period and the corresponding account limits in a table format:

```plaintext
+-----------------------------------------------------+--------------+
| Usage & quota                                       | Value        |
+-----------------------------------------------------+--------------+
| Translated characters in the current billing period | 1,234,567    |
| Character limits in the current billing period      | 2,000,000    |
+-----------------------------------------------------+--------------+
```

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
php artisan deepl:translate resources/lang/en/messages.php --sourceLang=en --targetLang=cs
```

This will create a translated file at `resources/lang/cs/messages.php`, preserving the structure and formatting of the original file.

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
php artisan deepl:translate-folder resources/lang/en --sourceLang=en --targetLang=cs
```

This command will recursively traverse all files and subdirectories within `resources/lang/en`, translating each file and saving the translated versions in the corresponding target language directory (e.g., `resources/lang/cs`).

### Handling JSON and PHP Files

Both commands support both JSON and PHP localization files. They will automatically detect the file type based on the file extension and handle the translation appropriately.

- **For JSON files:** The translations are saved in JSON format, with keys and values preserved
- **For PHP files:** The translations are saved in PHP array syntax, with keys and values maintained

#### Example of Content Before and After Translation (JSON File)

**Original File** (`resources/lang/en/messages.json`):

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

**Translated File** (`resources/lang/cs/messages.json`):

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

**Original File** (`resources/lang/en/messages.php`):

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

**Translated File** (`resources/lang/cs/messages.php`):

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

**Existing Target File** (`resources/lang/cs/messages.php`):

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
php artisan deepl:translate resources/lang/en/messages.php --sourceLang=en --targetLang=cs
```

The command will detect that the keys `welcome` and `greeting` already exist in the target file and will skip translating them. It will only translate and add the missing `user` key.

**Updated Target File** (`resources/lang/cs/messages.php`):

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

```php

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

$translatedText = Deepl::textTranslation('How are you today?')
    ->sourceLang(SourceLanguage::ENGLISH->value)
    ->targetLang(TargetLanguage::GERMAN->value)
    ->formality(Formality::PREFER_LESS->value)
    ->getTranslation();

echo $translatedText; // Outputs: Wie geht es dir heute?
```

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
