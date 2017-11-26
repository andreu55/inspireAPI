<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Input extends Model
{
    public function tipo() { return $this->hasOne('App\Tipo'); }
    public function user() { return $this->belongsTo('App\User'); }

    // 1 => Personajes // 2 => Escenarios // 3 => Objetos // 4 => Situaciones // 5 => Temas
    public static function deTipo($tipo_id) { return \DB::table('inputs')->where('tipo_id', $tipo_id)->where('generico', 1)->inRandomOrder()->value('name'); }

    public static function allGeneric($tipo_id) { return \DB::table('inputs')->where('tipo_id', $tipo_id)->where('generico', 1)->get(); }

    public static function nuevo($request) {

      $values = $request->except('_token');

      $input = new Input;
        foreach ($values as $name => $value) { $input->$name = $value; }
      $input->save();

      return $input->id;
    }

    public static function carga($tipos, $cuantas, $user) {
      $inputs = [];
      $rand = rand(0, 99);
      $carga_custom = $rand < $user->random;      

      foreach ($tipos as $tipo) {

        // Cargamos $cuantos inputs por defecto (los sustituiremos despues si procede)
        $inputs_tipo = \DB::table('inputs')->where('tipo_id', $tipo)->where('generico', 1)->inRandomOrder()->take($cuantas)->get();

        if ($carga_custom) {

          $num_user_inputs = 0;
          $num_user_inputs = \DB::table('inputs')->where('tipo_id', $tipo)->where('user_id', $user->id)->count();

          $num = $num_user_inputs;
          if ($num_user_inputs > $cuantas) { $num = $cuantas; }

          $custom_inputs = \DB::table('inputs')->where('tipo_id', $tipo)->where('user_id', $user->id)->inRandomOrder()->take($num)->get();

          foreach ($custom_inputs as $key => $custom_input) {
            $inputs_tipo[$key] = $custom_input;
          }
        }

        // Guardamos los inputs del tipo en el array mas grande
        $inputs[$tipo] = $inputs_tipo;
      }

      return $inputs;
    }
}
