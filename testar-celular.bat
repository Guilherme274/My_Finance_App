@echo off
chcp 65001 >nul
title Gestao Financeira - Teste no Celular (Jump)
cd /d "%~dp0"

echo ========================================================
echo   NativePHP Jump - Testar no Android sem Android Studio
echo ========================================================
echo 1. Instale o app "NativePHP Jump" na Play Store do celular.
echo 2. Conecte o celular na mesma rede Wi-Fi deste PC.
echo 3. Escaneie o QR Code que aparecera abaixo.
echo.

php artisan native:jump
pause

