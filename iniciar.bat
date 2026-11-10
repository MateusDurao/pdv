@echo off
title PDV Simples
cd /d "%~dp0"
start "" http://localhost:8090
php -S localhost:8090 -t public public/router.php
pause
