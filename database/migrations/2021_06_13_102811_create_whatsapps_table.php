<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('whatsapps')) {

        Schema::create('whatsapps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->string('msisdn')->nullable();
            $table->string('facebook_business_id')->nullable();
            $table->string('logo_url')->nullable();
            $table->enum('plan', ['charge_per_contact', 'charge_per_message'])->default('charge_per_contact');
            $table->text('whatsapp_url')->nullable();
            $table->text('whatsapp_token')->nullable();
            $table->text('comments')->nullable();
            $table->enum('type', ['ogaranya_whatsapp', 'ogaranya', 'service'])->nullable();
            $table->string('service_endpoint')->nullable();
            $table->string('service_token')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'activated', 'blocked'])->default('pending');
            $table->dateTime('activated_at')->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('whatsapps');
    }
}
