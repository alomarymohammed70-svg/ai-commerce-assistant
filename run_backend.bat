@echo off
title AI Commerce Assistant - Backend Server
echo ========================================================
echo   AI Commerce Assistant - Backend (Laravel API)
echo ========================================================
set PATH=C:\xampp\php;C:\xampp\mysql\bin;%PATH%
cd /d "%~dp0ai-commerce-assistant"
echo.
echo Starting Laravel Server on http://127.0.0.1:8000 ...
php artisan serve --host=0.0.0.0 --port=8000
pause

