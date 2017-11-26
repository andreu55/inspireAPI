<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    // The attributes that are mass assignable.
    protected $fillable = [ 'name', 'email', 'password', ];

    // The attributes that should be hidden for arrays.
    protected $hidden = [ 'password', 'remember_token', ];

    public function inputs($tipo_id) { return $this->hasMany('App\Input')->where('tipo_id', $tipo_id)->get(); }

    public function esAdmin() {
      if ($this->id == 1 || $this->id == 2) { return 1; }
      else { return 0; }
    }

    public function cambiaPass($data) {

      $status = 400;
      $msj = 'Faltan datos';

      if (isset($data['newPassword']) && $data['newPassword']) {
        if (isset($data['password']) && $data['password']) {

          // Si el password antiguo y nuevo es el mismo
          if ($data['password'] != $data['newPassword']) {
            // Si el password actual coincide con el que había
            if ($this->password == md5($data['password'])) {

              $this->password = md5($data['newPassword']);

              $status = 200;
              $msj = 'Password cambiado correctamente';
            }
            else { $msj = 'El password actual no coincide'; $status = 409; }
          }
          else { $msj = 'El password nuevo es igual al actual'; $status = 400; }
        }
        else { $msj = 'Falta el password actual'; $status = 400; }
      }
      // Guardamos los cambios en la base de datos si no hay ningun error
      if ($status == 200) {
        $this->save();
      }

      $res['status'] = $status;
      $res['msj'] = $msj;

      return $res;
    }
}
