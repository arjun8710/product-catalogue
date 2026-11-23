<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CorsTest extends TestCase
{
    #[Test]
    public function it_does_not_allow_cross_origin_requests_by_default(): void
    {
        $this->fakeFakeStore();

        $this->getJson('/api/categories', ['Origin' => 'https://evil.test'])
            ->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    #[Test]
    public function it_allows_configured_origins(): void
    {
        $this->fakeFakeStore();
        config(['cors.allowed_origins' => ['https://shop.test']]);

        $this->getJson('/api/categories', ['Origin' => 'https://shop.test'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://shop.test');
    }
}
