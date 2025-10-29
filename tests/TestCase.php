<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Don't disable exception handling - we need validation exceptions to be rendered as responses
        // (removed: $this->withoutExceptionHandling();)
    }
}

