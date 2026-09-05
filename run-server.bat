@echo off
title JHN Drive - Port 8088 Server
echo =================================================================
echo   JHN Drive - Minimalist Dark Mode Cloud Storage
echo =================================================================
echo   Port: 8088 (Avoiding reserved ports 8000, 8008, 8080, 8110, 8120, 8200, 8201)
echo   Local Address:   http://localhost:8088
echo   LAN Network:     http://0.0.0.0:8088
echo =================================================================
echo.

php artisan serve --host=0.0.0.0 --port=8088
pause
