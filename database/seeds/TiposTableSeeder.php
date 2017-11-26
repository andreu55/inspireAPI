<?php

use Illuminate\Database\Seeder;

class TiposTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      DB::table('tipos')->insert([
        ['id' => 1, 'name' => 'Personajes', 'name_trans' => 'personajes', 'icon' => 'user'],
        ['id' => 2, 'name' => 'Escenarios', 'name_trans' => 'escenarios', 'icon' => 'map-marker'],
        ['id' => 3, 'name' => 'Objetos', 'name_trans' => 'objetos', 'icon' => 'key'],
        ['id' => 4, 'name' => 'Situaciones', 'name_trans' => 'situaciones', 'icon' => 'puzzle-piece'],
        ['id' => 5, 'name' => 'Temas', 'name_trans' => 'temas', 'icon' => 'comments-o'],
        ['id' => 6, 'name' => 'Regiones', 'name_trans' => 'regiones', 'icon' => 'globe'],
      ]);
    }
}
