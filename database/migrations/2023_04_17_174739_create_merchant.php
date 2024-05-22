<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchant extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('merchant')){
        Schema::create('merchant', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_name', 100);
            $table->string('merchant_phone', 20)->nullable();
            $table->string('merchant_email', 150)->nullable();
            $table->string('merchant_address', 200)->nullable();
            $table->string('merchant_contact_person', 200)->nullable();
            $table->string('merchant_code', 50)->nullable();
            $table->float('wallet')->default(0.00);
            $table->float('other_wallet')->default(0.00);
            $table->float('country_id')->default(1);
            $table->float('commission')->default(0.00);
            $table->float('commission_cap')->default(0.00);
            $table->float('commission_lower_bound')->default(0.00);
            $table->float('agent_commission')->default(0.00);
            $table->float('agent_commission_cap')->default(0.00);
            $table->float('agent_commission_lower_bound')->default(0.00);
            $table->float('parent_child_split')->default(0.00);
            $table->tinyInteger('charge_merchant_comission')->default(1);
            $table->tinyInteger('show_service_charge_desc')->default(1);
            $table->tinyInteger('is_parent')->default(0);
            $table->string('payment_remittance_type', 50)->default('normal');
            $table->text('password');
            $table->text('password_reset');
            $table->dateTime('last_login')->nullable();
            $table->enum('status', ['enabled', 'disabled', 'deleted', 'pending'])->default('enabled');
            $table->integer('email_verified')->default(0);
            $table->tinyInteger('notify_via_email')->default(0);
            $table->tinyInteger('manage_stock')->default(0);
            $table->integer('parent_id')->nullable();
            $table->text('inventory_settings');
            $table->tinyInteger('show_merchant_support_line')->default(0);
            $table->integer('show_merchant_name_on_pay_gateway')->default(0);
            $table->tinyInteger('use_custom_payment')->default(0);
            $table->tinyInteger('payment_group_id')->default(0);
            $table->text('payment_settings');
            $table->text('custom_settings');
            $table->string('language', 50)->default('english');
            $table->string('webhook_url', 255)->nullable();
            $table->dateTime('date_added')->nullable();
            $table->timestamp('date_updated')->useCurrent();
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
        Schema::dropIfExists('merchant');
    }
}
