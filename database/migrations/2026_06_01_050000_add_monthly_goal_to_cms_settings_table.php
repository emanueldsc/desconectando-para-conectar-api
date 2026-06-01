<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_settings', function (Blueprint $table): void {
            $table->decimal('monthly_goal', 12, 2)->default(20000)->after('home_reality');
        });
    }

    public function down(): void
    {
        Schema::table('cms_settings', function (Blueprint $table): void {
            $table->dropColumn('monthly_goal');
        });
    }
};
