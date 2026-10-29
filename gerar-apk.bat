@echo off
chcp 65001 >nul
title Gestao Financeira - Atualizar APK Android
cd /d "%~dp0"

echo ========================================================
echo   Atualizar APK Android (Gestao Financeira)
echo ========================================================
echo.

C:\php\php.exe artisan finance:apk --ansi

echo.
pause
