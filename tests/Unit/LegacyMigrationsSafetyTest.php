<?php

namespace Tests\Unit;

use Tests\TestCase;

class LegacyMigrationsSafetyTest extends TestCase
{
    public function test_legacy_cms_migration_does_not_drop_tables_in_up_method(): void
    {
        $contents = file_get_contents(base_path('database/migrations/2025_03_21_000000_create_legacy_cms_tables.php'));

        $this->assertNotFalse($contents);
        $this->assertStringNotContainsString("app()->environment(['local', 'testing'])", $contents);
        $this->assertStringNotContainsString('Schema::dropIfExists($table)', $contents);
    }

    public function test_legacy_oms_migration_does_not_drop_tables_in_up_method(): void
    {
        $contents = file_get_contents(base_path('database/migrations/2025_03_21_000100_create_legacy_oms_tables.php'));

        $this->assertNotFalse($contents);
        $this->assertStringNotContainsString("app()->environment(['local', 'testing'])", $contents);
        $this->assertStringNotContainsString('Schema::dropIfExists($table)', $contents);
    }
}
