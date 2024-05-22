<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappChatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('whatsapp_chat')){
          Schema::create('whatsapp_chat', function (Blueprint $table) {
              $table->id();
              $table->integer('merchant_id')->nullable();
              $table->string('merchant_name')->nullable();
              $table->string('merchant_msisdn')->nullable();
              $table->string('customer_msisdn')->nullable();
              $table->string('customer_msg_id')->nullable();
              $table->string('customer_name')->nullable();
              $table->string('agent_name')->nullable();
              $table->timestamp('session_start')->nullable();
              $table->timestamp('session_end')->nullable();
              $table->integer('agent_id')->nullable();
              $table->enum('source', ['customer', 'system', 'agent'])->default('system')->nullable();
              $table->text('message')->nullable();
              $table->text('last_message')->nullable();
              $table->bigInteger('closed_by_id')->nullable();
              $table->string('closed_by', 200)->nullable();
              $table->enum('status', ['pending', 'received', 'sent', 'failed'])
                ->default('pending')
                ->nullable();
              $table->string('complaint_id')->nullable();
              $table->enum('state', ['opened', 'closed', 'pending','session_expired'])->nullable();
              $table->enum('plan', ['charge_per_contact', 'charge_per_message'])->default('charge_per_contact');
              $table->decimal('cost', 8, 2)->nullable()->default(0);
              $table->enum('settlement_status', ['pending', 'paid'])->nullable()->default('pending');
              $table->text('credential')->nullable();
              $table->text('meta')->nullable();
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
        Schema::dropIfExists('whatsapp_chat');
    }
}
