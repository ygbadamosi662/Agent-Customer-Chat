<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedFundingAdminColumnOnUsersTable extends Migration
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
                if($this->columnExists('users','funding_admin')) {
                    $table->boolean('funding_admin')->default(false)->after('remember_token');
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
            $table->dropColumn('funding_admin');
        });
    }
}
