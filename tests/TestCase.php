<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase {
        migrateFreshUsing as protected parentMigrateFreshUsing;
    }

    protected function migrateFreshUsing(): array
    {
        return array_merge($this->parentMigrateFreshUsing(), [
            '--path' => 'database/migrations',
        ]);
    }
}
