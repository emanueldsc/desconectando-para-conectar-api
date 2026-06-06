<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('raffles') && Schema::hasColumn('raffles', 'organization_id')) {
            Schema::table('raffles', function (Blueprint $table): void {
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            });
        }

        Schema::dropIfExists('institutions');
    }

    public function down(): void
    {
        if (! Schema::hasTable('institutions')) {
            Schema::create('institutions', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->text('description');
                $table->string('logo')->nullable();
                $table->string('image');
                $table->string('image_position')->default('center center');
                $table->json('contact')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('raffles') && ! Schema::hasColumn('raffles', 'organization_id')) {
            Schema::table('raffles', function (Blueprint $table): void {
                $table->foreignId('organization_id')->nullable()->after('tickets_sold')->constrained('institutions')->nullOnDelete();
            });
        }
    }
};
