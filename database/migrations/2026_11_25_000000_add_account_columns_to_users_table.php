<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The account of E4 on the framework's users table: the CPF and the phone of the sign-up, the
 * preferences, the paths of the two pictures, the reactivation deadline of a deactivated account, and
 * when an account was closed.
 *
 * Closing erases the email, the password, the CPF and the phone (so the CPF and email can sign up
 * again), which is why those columns accept null. The CPF is unique, as the email is.
 */
return new class extends Migration
{
    /**
     * Adds the account columns and lets the erased ones be null.
     */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('name', 100)->change();
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->char('cpf', 11)->nullable()->unique()->after('name');
            $table->string('phone_number', 11)->nullable()->after('cpf');
            $table->enum('phone_type', ['home', 'mobile', 'business'])->nullable()->after('phone_number');
            $table->boolean('is_night_mode')->default(false);
            $table->boolean('shows_tutorial')->default(true);
            $table->string('photo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->timestamp('reactivation_deadline')->nullable();
            $table->timestamp('closed_at')->nullable();
        });
    }

    /**
     * Drops the account columns.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropUnique(['cpf']);
            $table->dropColumn([
                'cpf',
                'phone_number',
                'phone_type',
                'is_night_mode',
                'shows_tutorial',
                'photo_path',
                'cover_path',
                'reactivation_deadline',
                'closed_at',
            ]);
        });
    }
};
