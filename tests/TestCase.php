<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\CreatesApplicationTables;
use Tests\Concerns\CreatesFixtures;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplicationTables;
    use CreatesFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createApplicationTables();
    }
}
