<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('premium_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->enum('type', ['user', 'merchant', 'agent', 'admin', 'premium', 'merchant_member'])->default('user');
            $table->string('role')->nullable();
            $table->string('phone', 17)->nullable();
            $table->tinyInteger('member_role')->default(0);
            $table->string('password')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('api_token')->nullable();
            $table->string('verify_token', 100)->nullable();
            $table->enum('status', ['active', 'inactive', 'disabled'])->default('active');
            $table->rememberToken();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
       });
    }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
