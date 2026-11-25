<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The activity log (the 2015 `tb_atividade`): one row per account action, kept when the account is
 * closed, for the admin panel.
 */
return new class extends Migration
{
    /**
     * Creates the activities table.
     */
    public function up(): void
    {
        Schema::create('activities', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->timestamp('occurred_at');
            $table->index(['user_id', 'occurred_at']);
        });
    }

    /**
     * Drops the activities table.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
