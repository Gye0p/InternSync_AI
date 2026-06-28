@echo off
echo ============================================================
echo  INTERNSYNC AI - Dev Server
echo ============================================================
echo.
echo Checking MySQL...
"C:\xampp\mysql\bin\mysqladmin.exe" -u root --host=127.0.0.1 ping 2>nul
if errorlevel 1 (
    echo Starting MySQL...
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
    timeout /t 4 /nobreak >nul
)

set OPENSSL_CONF=C:\xampp\apache\conf\openssl.cnf
echo.
echo Starting Symfony dev server at http://127.0.0.1:8000
echo.
echo  Login page : http://127.0.0.1:8000/login
echo  Register   : http://127.0.0.1:8000/register
echo.
echo Press Ctrl+C to stop.
echo ============================================================
symfony server:start --no-tls
