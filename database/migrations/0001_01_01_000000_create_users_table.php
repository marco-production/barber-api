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
            $table->string('lastname')->nullable();
            $table->string('email')->unique()->index();
            $table->string('phone_number', 40)->unique()->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('avatar')->default('default.png');
            $table->boolean('is_social_avatar')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->string('slug')->unique()->nullable();
            $table->string('password')->nullable();
            $table->boolean('active')->default(true);
            $table->string('google_id', 100)->nullable()->unique();
            $table->string('apple_id')->nullable()->unique();
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
