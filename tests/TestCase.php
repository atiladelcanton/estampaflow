<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->endTenantContext();
    }

    protected function tearDown(): void
    {
        $this->endTenantContext();
        parent::tearDown();
    }

    private function endTenantContext(): void
    {
        if (function_exists('tenant') && tenant() !== null) {
            tenancy()->end();
        }
    }
}
