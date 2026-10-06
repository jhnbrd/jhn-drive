<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trashed_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('storage_name', 300)->unique();
            $table->text('original_path');
            $table->string('original_name');
            $table->boolean('is_folder')->default(false);
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamp('deleted_at');
            $table->timestamps();

            $table->index(['user_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trashed_items');
    }
};
