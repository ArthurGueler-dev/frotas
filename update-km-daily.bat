@echo off
REM Script de Atualização Diária de Quilometragem
REM Para agendar no Agendador de Tarefas do Windows

cd /d "%~dp0"
node cron-update-km.js >> logs\km-updates.log 2>&1
