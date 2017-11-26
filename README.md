# inspireAPI
API para la aplicación de escritores que buscan inspiración

## Instalación

Después de clonar este repositorio deberás sigue estos pasos:

- Asegúrate de que tienes __permisos de ejecucion__ en las carpetas **storage** y **bootstrap**
- **Para testeo local** hacer un chmod 777 de storage y bootstrap `sudo chmod -R 777 bootstrap` `sudo chmod -R 777 storage`
- **En produccion** cambiaremos el usuario propietario de las carpetas mencionadas `chown -R web10 delfosAPI` `chgrp -R client0 delfosAPI`
- Para confirmar si tienes los permisos de escritura correctamente entra en la url `/public`

- Ejecuta:
    * `composer install` o `composer update` para crear la carpeta _vendor_
    * `cp .env.example .env`
    * Editamos los datos de bd / `sudo nano .env`
    * `php artisan key:generate`
    * `php artisan config:clear`
- Edita los parámetros de tu base de datos en **.env**
- Ejecuta `php artisan migrate:refresh --seed`

- Para la migración de datos necesitarás tener las tanto la base de datos antigua como la nueva a mano (**c0aplicacion** y **delfos_db**)
- Para hacer la migracion entra en la url `/public/migrate`
