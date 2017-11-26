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
        ['name' => 'andreu', 'email' => 'anduwet2@gmail.com', 'password' => bcrypt('marinero'), 'api_token' => '12345'],
        ['name' => 'marta', 'email' => 'marta@gmail.com', 'password' => bcrypt('marinera'), 'api_token' => '12346']
      ]);
    }
}
