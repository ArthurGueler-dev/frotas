@echo off
REM Script de Backup Diário do Banco de Dados
REM Para agendar no Agendador de Tarefas do Windows

cd /d "%~dp0"
node backup-database.js >> logs\backup.log 2>&1
