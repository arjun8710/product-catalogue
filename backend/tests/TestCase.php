<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests must never reach the real Fake Store API.
        Http::preventStrayRequests();
    }

    /**
     * Fake Store's real /products payload, captured as a fixture.
     *
     * @return list<array<string, mixed>>
     */
    protected function fakeStoreProducts(): array
    {
        return json_decode(file_get_contents(__DIR__.'/Fixtures/fakestore-products.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    protected function fakeFakeStore(): void
    {
        Http::fake(['fakestore.test/products' => Http::response($this->fakeStoreProducts())]);
    }
}
