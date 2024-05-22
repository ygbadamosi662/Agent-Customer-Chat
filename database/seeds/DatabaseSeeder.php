<?php
// namespace Database\Seeds;
use App\ApiTransaction;

use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        ApiTransaction::factory(17)->create();
        // $this->call(UsersTableSeeder::class);
    }
}
