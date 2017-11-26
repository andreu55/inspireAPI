<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      DB::table('users')->insert([
        ['name' => 'andreu', 'email' => 'anduwet2@gmail.com', 'password' => bcrypt('marinero')],
        ['name' => 'marta', 'email' => 'marta@gmail.com', 'password' => bcrypt('marinera')]
      ]);
    }
}
