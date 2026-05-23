<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffles', function (Blueprint $table): void {
            $table->unsignedInteger('extraction_number')->nullable()->unique()->after('draw_date');
        });
    }

    public function down(): void
    {
        Schema::table('raffles', function (Blueprint $table): void {
            $table->dropUnique(['extraction_number']);
            $table->dropColumn('extraction_number');
        });
    }
};
