<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_superadmin')->default(false)->after('password');
            $table->string('status', 20)->default('pending')->after('is_superadmin'); // pending, approved, rejected
            $table->text('request_note')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('request_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_superadmin', 'status', 'request_note', 'approved_at']);
        });
    }
};
