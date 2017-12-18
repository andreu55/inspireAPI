<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Storage;

use App\User;
use App\Empresa;
use App\Archivo;
use App\Provincia;
use App\Update;
use App\Empresa_tipo;
use App\User_tipo;
use App\Cargo;
use App\Archivo_tipo;

class delfosAPIController extends Controller {

  public function getLogin(Request $request) {

    $res = [];

    $email = isset($request->email) ? $request->email : '';
    $pass = isset($request->pass) ? $request->pass : 0;

    $status = 200;
    $msj = '';

    if ($email) {
      if ($pass) {

        // Datos que queremos pasar tambien (Son los nombres de las funciones que hemos creado en el modelo)
        $eager_load = [
          'provincia',
          'user_tipo',
          'cargos',
          'archivos',
          'empresas.empresa_tipo'
        ];

        // OJO! Esta consulta ya ignora los usuarios que tienen rellenado el campo 'deleted_at' si queremos verlos todos hay que añadir ->withTrashed() a la consulta
        if (strpos($email, '@') !== false) {
          $user = User::with($eager_load)->where('email', $email)->first();
        } else {
          $user = User::with($eager_load)->where('clave', $email)->first();
        }

        if ($user) {

          // Comprobar que el $pass que nos pasan coincida con los datos del usuario que pretenden loguear
          if ($user->password == md5($pass)) {

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

            // Datos estáticos que devolvemos con el login
            $res['provincias'] = Provincia::all();
            $res['tiposEmpresa'] = Empresa_tipo::all();
            $res['tiposUser'] = User_tipo::all();
            $res['cargos'] = Cargo::all();
            $res['tiposArchivo'] = Archivo_tipo::all();
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

    // Datos que queremos pasar tambien (Son los nombres de las funciones que hemos creado en el modelo)
    $eager_load = [
      'provincia',
      'user_tipo',
      'cargos',
      'archivos',
      'empresas.empresa_tipo'
    ];

    $res['user'] = $user->load($eager_load);

    $res['provincias'] = Provincia::all();
    $res['tiposEmpresa'] = Empresa_tipo::all();
    $res['tiposUser'] = User_tipo::all();
    $res['cargos'] = Cargo::all();
    $res['tiposArchivo'] = Archivo_tipo::all();

    return response()->json($res, 200);
  }

  public function nuevoTasador(Request $request) {

    // El user logueado que ejecuta la accion
    $user = $request->user();

    // Si NO es admin
    if ($user->user_tipo_id != 2) {

      $res['error'] = 'No tienes permisos para crear nuevos tasadores.';
      return response()->json($res, 401);
    }
    else {

      if (isset($request['user']['email']) && $request['user']['email']) {

        $hay_mail_igual = User::withTrashed()->where('email', $request['user']['email'])->limit(1)->count();

        // Si hay emails iguales lanzamos error
        if ($hay_mail_igual) {
          $res['error'] = 'Ya hay un email igual en la base de datos';
          return response()->json($res, 406);
        }
      }

      if (isset($request['user']['clave']) && $request['user']['clave']) {

        $hay_clave_igual = User::withTrashed()->where('clave', $request['user']['clave'])->limit(1)->count();

        // Si hay emails iguales lanzamos error
        if ($hay_clave_igual) {
          $res['error'] = 'Ya hay una clave igual en la base de datos';
          return response()->json($res, 406);
        }
      }

      if (!isset($request['user']['password']) || !$request['user']['password']) {
          $res['error'] = 'Falta password';
          return response()->json($res, 406);
      }

      // $tasador = $user; // Para test
      $tasador = User::nuevo($request['user']);

      if ($tasador) {

        if (isset($request['user']['empresa'])) { $e = $request['user']['empresa']; }
        if (isset($request['user']['empresas'][0])) { $e = $request['user']['empresas'][0]; }

        if (isset($e) && $e) {

          $e['user_id'] = $tasador->id;
          $empresa = Empresa::create($e);

          $res['tasador']['empresa'] = $empresa; // La empresa del nuevo usuario creado
          $res['tasador']['empresas'] = [$empresa]; // La empresa del nuevo usuario creado
        }

        $res['tasador'] = $tasador; // El nuevo usuario creado
      }

      return response()->json($res, 200);
    }
  }

  public function getTasador(Request $request) {

    // El user logueado que ejecuta la accion
    $user = $request->user();
    $status = 200;

    // Si NO es admin
    if ($user->user_tipo_id != 2) {
      $res['error'] = 'No tienes permisos para borrar tasadores.';
      $status = 401;
    }
    else {

      $tasador_id = (isset($request->tasador_id) && $request->tasador_id) ? $request->tasador_id : 0;

      if ($tasador_id) {

        $user = User::find($tasador_id)->load('empresas');

        $res['user'] = $user;

        // Mandamos la empresa de las dos formas (user->empresa y user->empresas->0)
        if (isset($user->empresas[0]) && !empty($user->empresas[0])) {
          $res['user']['empresa'] = $user->empresas[0];
        }
      } else {
        $res['error'] = 'Falta tasador_id';
        $status = 401;
      }
    }

    return response()->json($res, $status);
  }

  public function borraTasador(Request $request) {

    // El user logueado que ejecuta la accion
    $user = $request->user();
    $status = 200;

    // Si NO es admin
    if ($user->user_tipo_id != 2) {
      $res['error'] = 'No tienes permisos para borrar tasadores.';
      $status = 401;
    }
    else {

      $tasador_id = (isset($request->tasador_id) && $request->tasador_id) ? $request->tasador_id : 0;

      if ($tasador_id) {

        $user = User::find($tasador_id);

        if ($user) {
          // Si no es un admin lo borramos
          if ($user->user_tipo_id != 2) {
            $user->delete();
            $res['msj'] = 'Usuario borrado con éxito';
          } else {
            $res['error'] = 'No puedes borrar a un admin';
            $status = 406;
          }
        } else {
          $res['error'] = 'El user no existe o ya está borrado';
          $status = 405;
        }
      } else {
        $res['error'] = 'Falta tasador_id';
        $status = 406;
      }

    }

    return response()->json($res, $status);
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

          // Si es un NIF comprobamos también que sea válido
          // if (strtolower($campo) == "nif") {
          //
          //   $letra = substr($valor, -1);
          //   $numeros = substr($valor, 0, -1);
          //
          //   if ( substr("TRWAGMYFPDXBNJZSQVHLCKE", $numeros%23, 1) == $letra && strlen($letra) == 1 && strlen ($numeros) == 8 ){
          //     $res['msj'] = "Formato de NIF correcto";
          //   } else {
          //     $res['error'] = "Formato de NIF no válido";
          //   }
          // }

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

  public function guardaUser(Request $request) {

    $user = $request->user(); // El logueado por token

    if (!isset($request['user'])) {
      $res['error'] = 'Falta el user';
      return response()->json($res, 400);
    }

    $tasador = $request['user']; // El que recibimos en la request (el que queremos editar)
    $res = [];

    // Si no pasan id de user, cogemos el logueado
    if (!isset($tasador['id'])) { $tasador['id'] = $user->id; }

    // Si se edita a si mismo o es un admin
    if ($tasador['id'] == $user->id || $user->user_tipo->id == 2) {

      $tasador = User::find($tasador['id']);

      $datos_user = $request['user'];
      unset($datos_user['password']);


      if (isset($datos_user['email']) && $datos_user['email']) {

        $hay_mail_igual = User::withTrashed()->where('email', $datos_user['email'])->limit(1)->count();

        // Si hay emails iguales (y no es el suyo propio) lanzamos error
        if ($hay_mail_igual && $datos_user['email'] != $tasador->email) {
          $res['error'] = 'Ya hay un email igual en la base de datos';
          return response()->json($res, 406);
        }
      }

      if (isset($datos_user['clave']) && $datos_user['clave']) {

        $hay_clave_igual = User::withTrashed()->where('clave', $datos_user['clave'])->limit(1)->count();

        // Si hay emails iguales (y no es el suyo propio) lanzamos error
        if ($hay_clave_igual && $datos_user['clave'] != $tasador->clave) {
          $res['error'] = 'Ya hay una clave igual en la base de datos';
          return response()->json($res, 406);
        }
      }


      $tasador->update($datos_user);
      $tasador->save();

      // Guardamos el user para devolverlo
      $res['user'] = $tasador;


      // Miramos si está la empresa en sus dos formas posibles
      if (isset($request['user']['empresa'])) { $e = $request['user']['empresa']; }
      if (isset($request['user']['empresas'][0])) { $e = $request['user']['empresas'][0]; }

      // Si esta definida la empresa
      if (isset($e) && $e) {

        // Buscamos la empresa del usuario
        $empresa = Empresa::where('user_id', $tasador->id)->first();

        if ($empresa) {
          $empresa->update($e);
          $empresa->save();
        }

        // Si no ha encontrado la empresa la creamos
        if (!isset($empresa) || !$empresa) {
          $e['user_id'] = $tasador['id'];
          $empresa = Empresa::create($e);
        }

        $res['user']['empresa'] = $empresa; // La empresa del nuevo usuario creado
        $res['user']['empresas'] = [$empresa]; // La empresa del nuevo usuario creado

      } else {

        // Si no nos pasan una empresa es que la han borrado, asique la quitamos de base de datos
        Empresa::where('user_id', $tasador['id'])->delete();
      }
    } else {
      $res['error'] = 'No tiene permisos para editar';
      return response()->json($res, 400);
    }

    return response()->json($res, 200);
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

  public function cambiaPassTasador(Request $request) {

    $user = $request->user();

    // Si NO es admin
    if ($user->user_tipo_id != 2) {
      $res['error'] = 'No tienes permisos para editar esto.';
      $status = 401;
    }
    else {

      $tasador_id = (isset($request->tasador_id) && $request->tasador_id) ? $request->tasador_id : 0;

      if ($tasador_id) {

        $tasador = User::find($tasador_id);

        if ($tasador) {

          $cambios = $tasador->cambiaPassTasador($request->all());

          if ($cambios['status'] == 200) {
            $res['msj'] = $cambios['msj'];
            $status = $cambios['status'];
          } else {
            $res['error'] = $cambios['msj'];
            $status = $cambios['status'];
          }
        }
      } else {
        $res['error'] = "falta tasador_id";
        $status = 406;
      }
    }

    return response()->json($res, $status);
  }

  public function nuevoArchivo(Request $request) {

    $user = $request->user();
    $res['error'] = '';

    // Si NO es admin solo se pueden editar a si mismos
    if ($user->user_tipo_id != 2) {
      $tasador = $user;
    }
    else {

      $tasador_id = (isset($request->tasador_id) && $request->tasador_id) ? $request->tasador_id : $user->id;

      if ($tasador_id) {

        $tasador = User::find($tasador_id);

        if (!$tasador) {
          $res['error'] = 'Tasador no encontrado';
          return response()->json($res, 406);
        }
      }
    }

    $base64 = isset($request->base64) ? $request->base64 : '';
    $archivo_tipo_id = isset($request->archivo_tipo_id) ? $request->archivo_tipo_id : 0;
    $nombre = isset($request->nombre) ? $request->nombre : 'foto ' . date('d/m/Y G:i');

    if (!$base64) {
      $res['error'] = "Falta el base64";
      $status = 406;
    } elseif (!$archivo_tipo_id) {
      $res['error'] = "Falta el tipo de archivo";
      $status = 406;
    } else {

      // if (strpos($base64, ',') !== false)

      // Sacamos la extensión
      $data = explode(',', $base64); // Separamos data:image/jpeg;base64,/9j/4AAQSk...
      $aux = explode(';', $data[0], 2); // Separamos data:image/jpeg;base64
      $mime = explode('/', $aux[0], 2); // Separamos data:image/jpeg

      if (isset($mime[1])) {
        $ext = $mime[1]; // Nos quedamos con jpeg
      }

      if (isset($ext) && $ext) {

        // Formateamos algunas extensiones
        switch ($ext) {
          case 'jpeg': $ext = 'jpg'; break;
          case 'plain': $ext = 'txt'; break;
        }

        // Generamos un nombre "unico"
        $nombre_file = md5((date("Y-m-d H:i:s").$nombre)).".".$ext;
        $url = $tasador->id."/".$nombre_file;

        // Creamos y guardamos la file
        Storage::put($url, base64_decode($data[1]));

        // $file = fopen($nombre_file, "wb");
        // fwrite($file, base64_decode($data[1]));
        // fclose($file);

        // Movemos la file a la carpeta que queramos
        // rename($nombre_file, './img/'.$nombre_file);

        $data['nombre'] = $nombre;
        $data['url'] = $url;
        $data['archivo_tipo_id'] = $archivo_tipo_id;

        $res['archivo'] = Archivo::nuevo($tasador->id, $data);
        $status = 200;

      } else {
        $res['error'] = "Archivo no reconocido";
        $status = 406;
      }

    }

    return response()->json($res, $status);
  }

  // public function getArchivo(Request $request) { // Usaremos la funcion publica de momento
  //
  //   $id = isset($request->archivo_id) ? $request->archivo_id : 0;
  //
  //   $user = $request->user();
  //
  //   if ($id) {
  //     if ($user) {
  //       if ($archivo = Archivo::find($id)) {
  //         // if ($archivo->user->id == $user->id) {
  //         if ($user->archivos->contains($archivo->id) || $user->user_tipo->id == 2) { // Acceso al creador del archivo y a los admin
  //
  //           if (Storage::exists($archivo->url)) {
  //
  //             $file = Storage::get($archivo->url);
  //             $type = Storage::mimeType($archivo->url);
  //             $size = Storage::size($archivo->url);
  //
  //             return response($file, 200, ['Content-Type' => $type, 'Content-Length' => $size]);
  //
  //           } else {
  //             $res['error'] = 'Not Found in the Server';
  //             $status = 404;
  //           }
  //         } else {
  //           $res['error'] = 'Unathorized to this archive';
  //           $status = 401;
  //         }
  //       } else {
  //         $res['error'] = 'Archive not Found in the Database';
  //         $status = 404;
  //       }
  //     } else {
  //       $res['error'] = 'Falta loged User';
  //       $status = 400;
  //     }
  //   } else {
  //     $res['error'] = 'Falta archivo_id';
  //     $status = 400;
  //   }
  //
  //   return response()->json($res, $status);
  // }

  public function editaArchivo(Request $request) {

    $id = isset($request->archivo_id) ? $request->archivo_id : 0;
    $nombre = isset($request->nombre) ? trim($request->nombre) : "";

    $user = $request->user();

    if ($id) {
      if ($user) {
        if ($archivo = Archivo::find($id)) {
          // if ($archivo->user->id == $user->id) {
          if ($user->archivos->contains($archivo->id) || $user->user_tipo->id == 2) { // Acceso al creador del archivo y a los admin

            if ($nombre && $archivo->nombre != $nombre) {

              $archivo->nombre = $nombre;
              $archivo->save();

              $res['msj'] = 'Archivo editado con éxito';
              $status = 200;
            } else {
              $res['msj'] = 'Archivo no editado (falta nombre o es el mismo)';
              $status = 200;
            }
          } else {
            $res['error'] = 'Unathorized to this archive';
            $status = 401;
          }
        } else {
          $res['error'] = 'Archive not Found in the Database';
          $status = 404;
        }
      } else {
        $res['error'] = 'Falta loged User';
        $status = 400;
      }
    } else {
      $res['error'] = 'Falta archivo_id';
      $status = 400;
    }

    return response()->json($res, $status);
  }

  public function borraArchivo(Request $request) {

    $id = isset($request->archivo_id) ? $request->archivo_id : 0;

    $user = $request->user();

    if ($id) {
      if ($user) {
        if ($archivo = Archivo::find($id)) {
          // if ($archivo->user->id == $user->id) {
          if ($user->archivos->contains($archivo->id) || $user->user_tipo->id == 2) { // Acceso al creador del archivo y a los admin

            // Si existe borramos fisicamente del servidor
            if (Storage::exists($archivo->url)) { Storage::delete($archivo->url); }

            // Lo borramos de la db
            $archivo->delete();

            $res['msj'] = 'Archivo borrado con éxito';
            $status = 200;

          } else {
            $res['error'] = 'Unathorized to this archive';
            $status = 401;
          }
        } else {
          $res['error'] = 'Archive not Found in the Database';
          $status = 404;
        }
      } else {
        $res['error'] = 'Falta loged User';
        $status = 400;
      }
    } else {
      $res['error'] = 'Falta archivo_id';
      $status = 400;
    }

    return response()->json($res, $status);
  }

  public function getUpdates(Request $request) {

    $res = [];

    $user = $request->user();

    $res = Update::getUpdates($request->all());

    return response()->json($res, 200);
  }

  public function getUsers(Request $request) {

    // El user logueado que ejecuta la accion
    $user = $request->user();

    // Si NO es admin
    if ($user->user_tipo_id != 2) {
      $res['error'] = 'No tienes permisos para ver a los tasadores.';
      return response()->json($res, 401);
    }
    else {

      $page = (isset($request->page) && $request->page) ? $request->page : 1;
      $num_items = (isset($request->num_items) && $request->num_items) ? $request->num_items : 15;
      $queryText = (isset($request->queryText) && $request->queryText) ? $request->queryText : '';
      $provincia_id = (isset($request->provincia_id) && $request->provincia_id) ? $request->provincia_id : 0;

      // Cálculo de la página a mostrar
      $limit = $page - 1;
      $offset = 0;

      if ($limit < 0) { $limit = 0; }
      if ($page) { $offset = $limit * $num_items; }

      $sql = DB::table('users')
      ->select('id', 'name', 'surname', 'second_surname', 'clave', 'provincia_id')
      ->whereNull('deleted_at') // que no estén borrados
      ->where('user_tipo_id', 1); // que sean solo tasadores (no admins)

      // Si hay mas de una palabra, la separamos y la buscamos independientemente en cada campo
      $busq = explode(' ', $queryText);

      if ($busq && isset($busq[0])) {

        foreach ($busq as $b) {

          $sql = $sql->where(function ($query) use ($b) {
            $query->where('name', 'like', '%'.$b.'%')
            ->orWhere('surname', 'like', '%'.$b.'%')
            ->orWhere('second_surname', 'like', '%'.$b.'%')
            ->orWhere('clave', 'like', '%'.$b.'%');
          });
        }
      }

      if ($provincia_id) {
        $sql = $sql->where('provincia_id', $provincia_id);
      }

      // Cogemos solo los que hemos definido
      $res['tasadores'] = $sql->skip($offset)
      ->take($num_items)
      ->get();

      // Pero decimos el total de los resultados
      $res['totalItems'] = $sql->count();

      $res['has_more_pages'] = ($res['tasadores']->count() == $num_items);


    }

    return response()->json($res, 200);
  }

  public function getArchivos(Request $request) {

    // El user logueado que ejecuta la accion
    $user = $request->user();

    // Si NO es admin
    if ($user->user_tipo_id != 2) {
      $res['error'] = 'No tienes permisos para ver a los tasadores.';
      $status = 401;
    }
    else {

      $tasador_id = (isset($request->tasador_id) && $request->tasador_id) ? $request->tasador_id : $user->id;

      if ($tasador_id) {

        if ($tasador = User::find($tasador_id)) {
          $res['archivos'] = $tasador->archivos;
          $status = 200;
        } else {
          $res['error'] = 'Tasador no encontrado';
          $status = 406;
        }
      } else {
        $res['error'] = 'Falta tasador_id';
        $status = 406;
      }

    }

    return response()->json($res, $status);
  }

  // public function getAllUsers(Request $request) {
  //
  //   // El user logueado que ejecuta la accion
  //   $user = $request->user();
  //
  //   // Si NO es admin
  //   if ($user->user_tipo_id != 2) {
  //     $res['error'] = 'No tienes permisos para ver a los tasadores.';
  //     return response()->json($res, 401);
  //   }
  //   else {
  //
  //     $res['tasadores'] = DB::table('users')
  //                           ->select('id', 'name', 'surname', 'second_surname', 'clave', 'provincia_id')
  //                           ->get();
  //
  //     // $res['totalItems'] = $res['items']->count();
  //   }
  //
  //   return response()->json($res, 200);
  // }

}
