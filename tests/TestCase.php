<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Disable Vite during tests so HTTP requests rendering Inertia views
     * don't require a built manifest. CI never runs `npm run build`, so
     * without this every Inertia-rendering test 500s on the missing
     * public/build/manifest.json. Tests don't exercise client-side asset
     * loading anyway.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
}
