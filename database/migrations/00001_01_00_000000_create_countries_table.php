<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // fixes DigitalOcean sql_require_primary_key problem
        DB::statement('SET SESSION sql_require_primary_key=0');
        //////
        Schema::create('countries', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('official_name')->nullable();
            $table->string('iso2')->index();
            $table->string('iso3');
            $table->string('latitude');
            $table->string('longitude');
            $table->integer('zoom');
            $table->boolean('enabled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('countries');
    }
}
