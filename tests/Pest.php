<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Config;
use PavelZanek\LaravelDeepl\DeeplClient;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(
    \PavelZanek\LaravelDeepl\Tests\OrchestraTestCase::class
)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

//expect()->extend('toBeOne', function () {
//    return $this->toBe(1);
//});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

beforeAll(function () {
    // Set the authKey configuration for all tests
    Config::set('laravel-deepl.api_key', 'test_auth_key');
});

/**
 * Helper function to create a DeeplClient with mocked responses.
 */
function createDeeplClientWithMockedResponse(array $mockedResponses): DeeplClient
{
    $mock = new MockHandler($mockedResponses);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    // Instantiate the DeeplClient with authKey and mocked client
    $deeplClient = new DeeplClient('test_auth_key', ['http_client' => $client]);

    return $deeplClient;
}
