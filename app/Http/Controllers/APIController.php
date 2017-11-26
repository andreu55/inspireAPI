<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Hash;
// use Storage;

use App\User;
use App\Input;
use App\Output;
use App\Tipo;

class APIController extends Controller {

  public function getLogin(Request $request) {

    $res = [];

    $email = isset($request->email) ? $request->email : '';
    $pass = isset($request->pass) ? $request->pass : 0;

    // print_r($pass);
    // exit();

    $status = 200;
    $msj = '';

    if ($email) {
      if ($pass) {

        // OJO! Esta consulta ya ignora los usuarios que tienen rellenado el campo 'deleted_at' si queremos verlos todos hay que añadir ->withTrashed() a la consulta
        if (strpos($email, '@') !== false) {
          $user = User::where('email', $email)->first();
        } else {
          $user = User::where('username', $email)->first();
        }

        if ($user) {

          // Comprobar que el $pass que nos pasan coincida con los datos del usuario que pretenden loguear
          // if ($user->password == md5($pass)) {
          if (Hash::check($pass, $user->password)) {

            // Si el usuario no tiene api_token se lo generamos
            if (!$user->api_token) {
              $token = str_random(60);

              while (User::where('api_token', $token)->take(1)->count()) {
                $token = str_random(60);
              }

              $user->api_token = $token;
              $user->save();
            }

            $res['user'] = $user;
          }
          else { $status = 401; $msj = 'No existe / Borrado / Credenciales inválidas'; }
        }
        // El usuario no existe.
        else { $status = 400; $msj = 'Credenciales inválidas'; }
      }
      else { $status = 400; $msj = 'Falta el pass'; }
    }
    else { $status = 400; $msj = 'Falta el email/usuario'; }

    $res['error'] = $msj;

    return response()->json($res, $status);
  }

  public function getUser(Request $request) {

    // OJO! Se ignoran los usuarios que tienen rellenado el campo 'deleted_at'
    // Si esta borrado devuelve "error": "Unauthenticated."
    $user = $request->user();
    $res['error'] = '';

    // // Datos que queremos pasar tambien (Son los nombres de las funciones que hemos creado en el modelo)
    // $eager_load = [
    //   'provincia',
    //   'user_tipo',
    //   'cargos',
    //   'archivos',
    //   'empresas.empresa_tipo'
    // ];

    // $res['user'] = $user->load($eager_load);
    $res['user'] = $user;

    return response()->json($res, 200);
  }

  // Si valor es el mismo que el que tiene actualmente el tasador_id se lo da por bueno, si es nuevo tasador, no debemos pasarle el tasador_id
  public function checkDuplicado(Request $request) {

    $campo = (isset($request->campo) && $request->campo) ? $request->campo : "";
    $valor = (isset($request->valor) && $request->valor) ? $request->valor : "";
    $tabla = (isset($request->tabla) && $request->tabla) ? $request->tabla : "";
    $tasador_id = (isset($request->tasador_id) && $request->tasador_id) ? $request->tasador_id : 0;

    $status = 400;
    $res['error'] = "";

    if ($tabla) {
      if ($campo) {
        if ($valor) {
          if ($tasador_id) {
            $tasador = User::find($tasador_id);
            $valor_actual = $tasador->$campo;

            if ($valor == $valor_actual) {

              // Es el mismo que tiene actualmente este tasador, asique lo pasamos
              $res['existen'] = 0;
            }
          }

          if (!isset($res['existen'])) {

            // Sino, buscamos resultados iguales en la BD
            $existen = DB::table($tabla)->where($campo, $valor)->take(1)->count();
            $res['existen'] = $existen;
          }

          $status = 200;

        } else { $res['error'] = "Falta el valor del campo"; }
      } else { $res['error'] = "Falta el campo sobre el que tenemos que buscar"; }
    } else { $res['error'] = "Falta la tabla"; }
    return response()->json($res, $status);
  }

  public function cambiaPass(Request $request) {

    $user = $request->user();
    $res['msj'] = $res['error'] = "";

    $cambios = $user->cambiaPass($request->all());

    if ($cambios['status'] == 200) {
      $res['msj'] = $cambios['msj'];
    } else {
      $res['error'] = $cambios['msj'];
    }

    return response()->json($res, $cambios['status']);
  }

  public function getInputs(Request $request) {

    // El user logueado que ejecuta la accion
    $user = $request->user();

    // Si NO es admin
    if (!$user->esAdmin()) {
      $res['error'] = 'No tienes permisos.';
      return response()->json($res, 401);
    }
    else {

      $page = (isset($request->page) && $request->page) ? $request->page : 1;
      $num_items = (isset($request->num_items) && $request->num_items) ? $request->num_items : 15;
      $queryText = (isset($request->queryText) && $request->queryText) ? $request->queryText : '';

      // Cálculo de la página a mostrar
      $limit = $page - 1;
      $offset = 0;

      if ($limit < 0) { $limit = 0; }
      if ($page) { $offset = $limit * $num_items; }

      $sql = Input::select('id', 'name');

      // Si hay mas de una palabra, la separamos y la buscamos independientemente en cada campo
      // $busq = explode(' ', $queryText);
      //
      // if ($busq && isset($busq[0])) {
      //
      //   foreach ($busq as $b) {
      //
      //     $sql = $sql->where(function ($query) use ($b) {
      //       $query->where('name', 'like', '%'.$b.'%')
      //       ->orWhere('surname', 'like', '%'.$b.'%')
      //       ->orWhere('second_surname', 'like', '%'.$b.'%')
      //       ->orWhere('username', 'like', '%'.$b.'%');
      //     });
      //   }
      // }

      // Cogemos solo los que hemos definido
      $res['inputs'] = $sql->skip($offset)
      ->take($num_items)
      ->get();

      // Pero decimos el total de los resultados
      $res['totalItems'] = $sql->count();

      $res['has_more_pages'] = ($res['inputs']->count() == $num_items);


    }

    return response()->json($res, 200);
  }

}
