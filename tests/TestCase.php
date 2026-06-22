<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests assert rendered HTML, not built assets — don't require
        // a compiled Vite manifest to be present.
        $this->withoutVite();
    }
}
