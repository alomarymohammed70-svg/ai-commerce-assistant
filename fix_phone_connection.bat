@echo off
title Fix Phone Connection - ADB Reverse
echo ========================================================
echo   Fix Phone Connection (Reconnect USB Bridge)
echo ========================================================
set ADB="%LOCALAPPDATA%\Android\Sdk\platform-tools\adb.exe"

echo Reconnecting ADB...
%ADB% reconnect
timeout /t 2 /nobreak >nul
%ADB% reverse tcp:8000 tcp:8000
echo.
echo Connection status:
%ADB% devices
echo.
echo Bridge status:
%ADB% reverse --list
echo.
echo [DONE] The connection between your phone and PC server is restored!
echo You can now refresh the app on your phone.
pause

