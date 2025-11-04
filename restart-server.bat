@echo off
echo Parando servidor antigo...
taskkill /F /PID 42936 2>nul
timeout /t 2 /nobreak >nul
echo Iniciando novo servidor...
start /B node server.js
echo Servidor reiniciado!
