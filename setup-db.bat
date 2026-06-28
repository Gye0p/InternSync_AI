@echo off
echo ============================================================
echo  INTERNSYNC AI - Database Setup Script
echo ============================================================
echo.
echo [Step 1] Checking if XAMPP MySQL is running...
"C:\xampp\mysql\bin\mysqladmin.exe" -u root --host=127.0.0.1 ping 2>nul
if errorlevel 1 (
    echo MySQL is not running. Starting XAMPP MySQL...
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
    echo Waiting 5 seconds for MySQL to start...
    timeout /t 5 /nobreak >nul
    "C:\xampp\mysql\bin\mysqladmin.exe" -u root --host=127.0.0.1 ping
    if errorlevel 1 (
        echo ERROR: Could not connect to MySQL. Please start MySQL from XAMPP Control Panel first.
        echo Then run this script again.
        pause
        exit /b 1
    )
) else (
    echo MySQL is already running. Good!
)

echo.
echo [Step 2] Creating database...
set OPENSSL_CONF=C:\xampp\apache\conf\openssl.cnf
php bin/console doctrine:database:create --if-not-exists
if errorlevel 1 (
    echo ERROR: Could not create database. Check your DATABASE_URL in .env
    pause
    exit /b 1
)

echo.
echo [Step 3] Creating database schema...
php bin/console doctrine:schema:create
if errorlevel 1 (
    echo Schema may already exist, trying update instead...
    php bin/console doctrine:schema:update --force
)

echo.
echo [Step 4] Loading sample data fixtures...
php bin/console doctrine:fixtures:load --no-interaction
if errorlevel 1 (
    echo WARNING: Fixtures failed. You can retry with: php bin/console doctrine:fixtures:load --no-interaction
)

echo.
echo ============================================================
echo  Setup Complete!
echo.
echo  Sample accounts loaded:
echo    Coordinator : coordinator@internsync.ai / password123
echo    Supervisor 1: supervisor1@internsync.ai / password123
echo    Supervisor 2: supervisor2@internsync.ai / password123
echo    Student 1-5 : student1@internsync.ai   / password123
echo.
echo  To start the dev server, run:  start-dev.bat
echo ============================================================
pause
