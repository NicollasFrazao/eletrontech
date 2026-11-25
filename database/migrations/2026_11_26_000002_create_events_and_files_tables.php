<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each person's events and file manager (the 2015 `tb_evento`, `tb_pasta` and `tb_arquivo`). A file's
     * content is on the private disk under `content_key`; the names are only labels.
     */
    public function up(): void
    {
        Schema::create('events', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 50);
            $table->dateTime('starts_at');
            $table->string('description', 500)->default('');
            $table->timestamps();
            $table->index(['user_id', 'starts_at']);
        });

        Schema::create('folders', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('folders')->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();
            $table->index(['user_id', 'parent_id']);
        });

        Schema::create('files', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 10);
            $table->unsignedBigInteger('size');
            $table->string('content_key', 100)->unique();
            $table->timestamps();
            $table->index(['user_id', 'folder_id']);
        });
    }

    /**
     * Drops the tables.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
        Schema::dropIfExists('folders');
        Schema::dropIfExists('events');
    }
};
