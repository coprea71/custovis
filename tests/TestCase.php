<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Symfony keeps trusted hosts in a static; tests that switch to
        // production would otherwise leak their host list into later tests.
        Request::setTrustedHosts([]);
    }
}
