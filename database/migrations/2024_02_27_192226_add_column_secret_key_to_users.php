<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnSecretKeyToUsers extends Migration
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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if($this->columnExists('users','secret_key')) {
                    $table->string('secret_key', 32)->nullable()->after('remember_token');
                }
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('secret_key');
        });
    }
}
