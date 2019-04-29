<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class AddHydroColumnsToUsersTable
 */
class AddHydroColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table(
            config('hydro-raindrop.users_table', 'users'),
            static function (Blueprint $table) {
                $table->string('hydro_id')
                    ->nullable()
                    ->after('remember_token');
                $table->dateTime('hydro_raindrop_enabled')
                    ->nullable()
                    ->after('hydro_id');
                $table->dateTime('hydro_raindrop_confirmed')
                    ->nullable()
                    ->after('hydro_raindrop_enabled');
                $table->integer('hydro_raindrop_failed_attempts')
                    ->default(0);
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(
            config('hydro-raindrop.users_table', 'users'),
            static function (Blueprint $table) {
                $table->dropColumn([
                    'hydro_id',
                    'hydro_raindrop_enabled',
                    'hydro_raindrop_confirmed'
                ]);
            }
        );
    }
}
