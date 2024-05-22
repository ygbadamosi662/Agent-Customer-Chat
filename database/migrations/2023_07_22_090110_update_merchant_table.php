<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMerchantTable extends Migration
{
    public function columnExists($tbl,$column)
    {
        if (Schema::hasColumn($tbl, $column)) {
            return false;
        }
        return true;
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = 'merchant';
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {

            if ($this->columnExists($tableName,'show_only_real_total'))
                $table->tinyInteger('show_only_real_total')->default(0)->after('other_wallet');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
     }
}
