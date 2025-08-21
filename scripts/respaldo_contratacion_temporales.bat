@echo off
echo Iniciando proceso de backup...
echo.

REM --- Configuración ---
set FECHA=%date:~-4%%date:~3,2%%date:~0,2%
set HORA=%time:~0,2%%time:~3,2%
set NOMBRE_BACKUP=backup_%FECHA%_%HORA%

set RUTA_MYSQL_DUMP="C:\xampp\mysql\bin\mysqldump.exe"
set USUARIO_BD=root
set NOMBRE_BD=contratacion_temporales

set RUTA_PROYECTO="C:\xampp\htdocs\temporales"
set RUTA_DESTINO_BACKUP="D:\backup"

set NOMBRE_ARCHIVO_BD=%NOMBRE_BACKUP%_bd.sql
set NOMBRE_ARCHIVO_PROYECTO=%NOMBRE_BACKUP%_proyecto.zip
set RUTA_BACKUP_BD=%RUTA_DESTINO_BACKUP%\%NOMBRE_ARCHIVO_BD%
set RUTA_BACKUP_PROYECTO=%RUTA_DESTINO_BACKUP%\%NOMBRE_ARCHIVO_PROYECTO%

echo --- Respaldo de la Base de Datos ---
if exist "%RUTA_MYSQL_DUMP%" (
    "%RUTA_MYSQL_DUMP%" -u%USUARIO_BD% %NOMBRE_BD% > "%RUTA_BACKUP_BD%"
    if exist "%RUTA_BACKUP_BD%" (
        echo Backup de la base de datos "%NOMBRE_BD%" exitoso en: "%RUTA_BACKUP_BD%"
    ) else (
        echo ¡Error al realizar el backup de la base de datos!
    )
) else (
    echo ¡Error! No se encontró la herramienta mysqldump en la ruta: "%RUTA_MYSQL_DUMP%"
    echo Asegúrate de que la ruta sea correcta.
)
echo.

echo --- Respaldo de los Archivos del Proyecto ---
if exist "%RUTA_PROYECTO%" (
    echo Comprimiendo archivos del proyecto...
    REM Requiere tener instalado 7-Zip (https://www.7-zip.org/) y configurado en el PATH o especificar la ruta completa.
    REM Ejemplo usando 7-Zip:
    "C:\Program Files\7-Zip\7z.exe" a -tzip "%RUTA_BACKUP_PROYECTO%" "%RUTA_PROYECTO%"\* -r
    if exist "%RUTA_BACKUP_PROYECTO%" (
        echo Backup de los archivos del proyecto exitoso en: "%RUTA_BACKUP_PROYECTO%"
    ) else (
        echo ¡Error al comprimir los archivos del proyecto!
    )
) else (
    echo ¡Error! No se encontró la ruta del proyecto: "%RUTA_PROYECTO%"
    echo Asegúrate de que la ruta sea correcta.
)
echo.

echo Proceso de backup finalizado.
pause