@echo off
echo Parando proxy antigo...
taskkill /F /PID 18344 2>nul
timeout /t 2 /nobreak >nul
echo Iniciando novo proxy...
start "Ituran Proxy" node ituran-proxy.js
echo Proxy reiniciado!
