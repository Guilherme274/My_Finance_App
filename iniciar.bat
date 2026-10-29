@echo off
chcp 65001 >nul
title Gestao Financeira - Hub PC
cd /d "%~dp0"

set PORT=8000
for /f "tokens=1,2 delims==" %%a in (.env) do (
    if "%%a"=="SYNC_PORT" set PORT=%%b
)

echo ========================================================
echo   Gestao Financeira - Hub PC
echo ========================================================
echo.

php -r "try { (new PDO('mysql:host=127.0.0.1;port=3306', 'root', 'root'))->exec('CREATE DATABASE IF NOT EXISTS gestao_financeira CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); } catch (Throwable $e) {}"
php artisan migrate --force >nul

if not exist "public\build\manifest.json" (
    echo Compilando estilos da interface ^(primeira execucao^)...
    call npm run build
)

php artisan finance:pairing
echo.
echo Abrindo http://localhost:%PORT% no navegador...
echo Mantenha esta janela aberta enquanto usar ou sincronizar o celular.
echo Pressione Ctrl+C para encerrar.
echo.

start "" "http://localhost:%PORT%"
php artisan serve --host=0.0.0.0 --port=%PORT%

