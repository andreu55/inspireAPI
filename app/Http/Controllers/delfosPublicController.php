<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;

use Storage;

use App\Archivo;

class delfosPublicController extends Controller
{

    public function getArchivoPublic() {

      $res['msj'] = "Formato de url no válido";
      return response()->view('no_public_image', $res, 400);
    }

    public function getArchivo($id = 0, $user = 0, $url = "") {

      $url_guardada = $user . "/" . $url;

      if ($archivo = Archivo::find($id)) {
        if ($archivo->url == $url_guardada) {
          if (Storage::exists($archivo->url)) {

            $file = Storage::get($archivo->url);
            $type = Storage::mimeType($archivo->url);
            $size = Storage::size($archivo->url);

            return response($file, 200, ['Content-Type' => $type, 'Content-Length' => $size]);

          } else {
            $res['msj'] = 'File not found in the Server';
            return response()->view('no_public_image', $res, 404);
          }
        } else {
          $res['msj'] = 'Los datos del archivo no coinciden en nuestra base de datos';
          return response()->view('no_public_image', $res, 404);
        }
      } else {
        $res['msj'] = 'Archivo no encontrado en la base de datos';
        return response()->view('no_public_image', $res, 404);
      }

      // Esto no se llega a ejecutar nunca
      return response()->json($res, $status);
    }

}
