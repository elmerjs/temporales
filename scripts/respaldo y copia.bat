@echo off
set FECHA=%date:~6,4%-%date:~3,2%-%date:~0,2%
set HORA=%time:~0,2%-%time:~3,2%
set BACKUP_NAME=backup_contratacion_temporales_%FECHA%_%HORA%.sql

:: Crear carpeta de respaldo si no existe
if not exist "C:\backup" (
    mkdir "C:\backup"
)

echo ===============================
echo  RESPALDO DE BASE DE DATOS
echo ===============================

"C:\xampp\mysql\bin\mysqldump.exe" -u root --databases contratacion_temporales > "C:\backup\%BACKUP_NAME%"

echo ===============================
echo  COPIA DE PROYECTO PHP
echo ===============================

xcopy "C:\xampp\htdocs\temporales" "\\192.168.42.52\temporales" /E /I /H /Y

echo ===============================
echo  COPIA DE RESPALDO SQL
echo ===============================

xcopy "C:\backup\%BACKUP_NAME%" "\\192.168.42.52\temporales\respaldos" /Y

echo ===============================
echo  PROCESO TERMINADO
echo ===============================

pause
