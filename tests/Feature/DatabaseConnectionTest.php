<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function test_la_suite_corre_contra_postgresql_en_laravel_test(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('laravel_test', DB::selectOne('select current_database() as db')->db);
    }
}
