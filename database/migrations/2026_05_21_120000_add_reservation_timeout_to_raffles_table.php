<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffles', function (Blueprint $table): void {
            $table->unsignedInteger('reservation_timeout_minutes')->default(30)->after('tickets_sold');
        });
    }

    public function down(): void
    {
        Schema::table('raffles', function (Blueprint $table): void {
            $table->dropColumn('reservation_timeout_minutes');
        });
    }
};
