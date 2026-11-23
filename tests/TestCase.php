<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The default array store starts empty for every test. When the suite runs
        // against a real store (`composer test:services`, using Redis), clear it so
        // rate-limit counters and locks from one test can't leak into the next.
        if (config('cache.default') !== 'array') {
            Cache::flush();
        }
    }
}
