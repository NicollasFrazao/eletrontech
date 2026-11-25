<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tools, packages and the tools of each, licences (one per person) and the record of tool uses
     * (the 2015 `tb_ferramenta`, `tb_pacote`, `pacote_ferramenta`, `usuario_pacote`, `tb_utilizacao`).
     */
    public function up(): void
    {
        Schema::create('tools', static function (Blueprint $table): void {
            $table->string('id', 50)->primary();
            $table->string('name', 100)->unique();
        });

        Schema::create('packages', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('kind', 20)->index();
            // A person has at most one custom package; the other kinds have no owner.
            $table->foreignId('owner_id')->nullable()->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('package_tool', static function (Blueprint $table): void {
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('tool_id', 50);
            $table->foreign('tool_id')->references('id')->on('tools')->cascadeOnDelete();
            $table->primary(['package_id', 'tool_id']);
        });

        Schema::create('licences', static function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
        });

        Schema::create('tool_uses', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tool_id', 50);
            $table->foreign('tool_id')->references('id')->on('tools')->cascadeOnDelete();
            $table->dateTime('used_at');
            $table->index(['user_id', 'used_at']);
        });
    }

    /**
     * Drops the tables.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_uses');
        Schema::dropIfExists('licences');
        Schema::dropIfExists('package_tool');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('tools');
    }
};
