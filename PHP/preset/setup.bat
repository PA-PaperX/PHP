@echo off
chcp 65001 >nul
echo ===================================================
echo     Backend One-Click Setup (XAMPP Version)
echo ===================================================
echo.

:: Go to project root folder
cd ..

:: Detect MySQL Path
set MYSQL_PATH=mysql
if exist "C:\xampp\mysql\bin\mysql.exe" (
    set MYSQL_PATH="C:\xampp\mysql\bin\mysql.exe"
)

echo [1] Checking MySQL connection...
%MYSQL_PATH% -u root -e "SELECT 1" >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Could not connect to MySQL. Make sure XAMPP MySQL is running!
    pause
    exit /b
)

echo [2] Setting up Database and User...
%MYSQL_PATH% -u root -e "CREATE DATABASE IF NOT EXISTS iya_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
%MYSQL_PATH% -u root -e "CREATE USER IF NOT EXISTS 'iya_user'@'localhost' IDENTIFIED BY 'iya_pass_2024';"
%MYSQL_PATH% -u root -e "GRANT ALL PRIVILEGES ON iya_db.* TO 'iya_user'@'localhost';"
%MYSQL_PATH% -u root -e "FLUSH PRIVILEGES;"

echo [3] Importing database (backup_before_wipe.sql)...
:: We use --default-character-set=utf8mb4 to fix the Thai alien language bug during import
%MYSQL_PATH% -u root --default-character-set=utf8mb4 iya_db < database\backup_before_wipe.sql
if %errorlevel% neq 0 (
    echo [ERROR] Database import failed!
    pause
    exit /b
)
echo Database imported successfully! (Thai text encoding bug fixed)

echo.
echo [4] Starting PHP Backend Server...
set PHP_PATH=php
if exist "C:\xampp\php\php.exe" (
    set PHP_PATH="C:\xampp\php\php.exe"
)

echo.
echo ===================================================
echo Server is running at: http://localhost:8080
echo You can now start the frontend application.
echo Press Ctrl+C to stop the server.
echo ===================================================
echo.

cd backend
%PHP_PATH% -S localhost:8080
pause
