<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The role of an account (ADR 0008): a member, as every account is at sign-up, or an administrator,
 * who also reaches the admin panel. One column on the account's row, as the 2015 `ic_admin` was.
 */
return new class extends Migration
{
    /**
     * Adds the role, a member for every existing and new account.
     */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->enum('role', ['member', 'administrator'])->default('member')->after('password');
        });
    }

    /**
     * Drops the role.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }
};
