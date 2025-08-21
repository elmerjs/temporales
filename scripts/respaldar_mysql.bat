@echo off
:: Formatear fecha y hora
set FECHA=%date:~6,4%-%date:~3,2%-%date:~0,2%
set HORA=%time:~0,2%-%time:~3,2%
set BACKUP_NAME=backup_contratacion_temporales_%FECHA%_%HORA%.sql

:: Crear carpeta si no existe
if not exist "C:\backup" (
    mkdir "C:\backup"
)

:: Ejecutar mysqldump
"C:\xampp\mysql\bin\mysqldump.exe" -u root --databases contratacion_temporales > "C:\backup\%BACKUP_NAME%"

echo Backup completado en: C:\backup\%BACKUP_NAME%
pause
