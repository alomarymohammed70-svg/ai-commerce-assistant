@echo off
title AI Commerce Assistant - Quick Launcher
echo ========================================================
echo        AI Commerce Assistant - Quick Launcher
echo ========================================================
echo.
echo [1] Start Backend Server (Laravel API + Filament Admin)
echo [2] Open Filament Admin Dashboard in Browser
echo [3] Start Flutter App in Chrome Browser
echo [4] Start Flutter App on Connected Android Phone
echo [5] Start Backend and Flutter Web Together
echo [6] Fix/Reconnect Phone USB Bridge
echo [7] Exit
echo.
set /p choice="Choose an option [1-7]: "

if "%choice%"=="1" (
    start "Backend Server" call "%~dp0run_backend.bat"
)
if "%choice%"=="2" (
    start http://127.0.0.1:8000/admin
)
if "%choice%"=="3" (
    start "Flutter Chrome" call "%~dp0run_mobile_chrome.bat"
)
if "%choice%"=="4" (
    start "Flutter Android" call "%~dp0run_mobile_android.bat"
)
if "%choice%"=="5" (
    start "Backend Server" call "%~dp0run_backend.bat"
    timeout /t 3 /nobreak >nul
    start "Flutter Chrome" call "%~dp0run_mobile_chrome.bat"
)
if "%choice%"=="6" (
    call "%~dp0fix_phone_connection.bat"
)
if "%choice%"=="7" (
    exit
)

