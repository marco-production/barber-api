<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname');
            $table->string('email')->unique()->index();
            $table->string('phone_number', 40)->unique()->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('avatar');
            $table->boolean('is_verified')->default(false);
            $table->string('slug')->unique()->nullable();
            $table->string('password');
            $table->boolean('active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes($column = 'deleted_at', $precision = 0);

            $table->foreign('country_id')->references('id')->on('countries');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
