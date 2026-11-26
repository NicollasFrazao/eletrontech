<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The terms a ready-made package is sold on (E8, #58): its price in reais and the days of use the price
 * is for. The free and the custom packages have none. The packages inserted before cost nothing, as
 * everything did in 2015, for 30 days; the admin panel changes them.
 */
return new class extends Migration
{
    /**
     * Adds the price and the days, and gives the ready-made packages theirs.
     */
    public function up(): void
    {
        Schema::table('packages', static function (Blueprint $table): void {
            $table->decimal('price', 7, 2)->nullable()->after('kind');
            $table->unsignedSmallInteger('days')->nullable()->after('price');
        });

        DB::table('packages')->where('kind', 'ready_made')->update(['price' => '0.00', 'days' => 30]);
    }

    /**
     * Drops the price and the days.
     */
    public function down(): void
    {
        Schema::table('packages', static function (Blueprint $table): void {
            $table->dropColumn(['price', 'days']);
        });
    }
};
