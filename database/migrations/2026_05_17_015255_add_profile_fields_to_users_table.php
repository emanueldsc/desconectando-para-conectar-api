<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('address')->nullable()->after('avatar');
            $table->enum('role', ['buyer', 'manager', 'publisher'])->default('buyer')->after('address');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['phone', 'avatar', 'address', 'role', 'status']);
        });
    }
};