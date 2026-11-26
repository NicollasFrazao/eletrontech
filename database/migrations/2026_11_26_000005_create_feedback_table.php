<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The feedback messages (E9, #63): what the 2015 form posted to a remote URL is kept here instead, with the
 * account that sent it and when, for the admin panel.
 */
return new class extends Migration
{
    /**
     * Creates the feedback table.
     */
    public function up(): void
    {
        Schema::create('feedback', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 20);
            $table->string('message', 500);
            $table->dateTime('sent_at');
            $table->index('sent_at');
        });
    }

    /**
     * Drops the feedback table.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
