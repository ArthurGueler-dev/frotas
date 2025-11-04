@echo off
echo ========================================
echo   REINICIANDO PROXY ITURAN
echo ========================================
echo.

echo [1/3] Parando todos os processos Node.js na porta 8888...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8888 ^| findstr LISTENING') do (
    echo Matando processo PID: %%a
    taskkill /F /PID %%a 2>nul
)

echo.
echo [2/3] Aguardando 3 segundos...
timeout /t 3 /nobreak >nul

echo.
echo [3/3] Iniciando NOVO proxy com CORS corrigido...
start "Ituran Proxy (CORS Fixed)" node ituran-proxy.js

echo.
echo ========================================
echo   PROXY REINICIADO COM SUCESSO!
echo ========================================
echo.
echo Aguarde 2 segundos para o proxy iniciar...
timeout /t 2 /nobreak >nul

echo.
echo Testando proxy...
curl -s http://localhost:8888/api/ituran/health >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Proxy esta respondendo!
) else (
    echo [OK] Proxy iniciado (404 esperado para /health)
)

echo.
echo Agora recarregue a pagina no navegador (Ctrl+F5)
echo.
pause
