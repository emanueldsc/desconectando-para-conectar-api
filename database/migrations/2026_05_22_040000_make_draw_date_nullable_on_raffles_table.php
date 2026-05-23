<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE raffles ALTER COLUMN draw_date DROP NOT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('UPDATE raffles SET draw_date = NOW() WHERE draw_date IS NULL');
        DB::statement('ALTER TABLE raffles ALTER COLUMN draw_date SET NOT NULL');
    }
};
