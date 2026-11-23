@echo off
rem ============================================================================
rem Acara 26 (Langkah 6): memulihkan cadangan .sql ke database BARU untuk uji restore (Windows).
rem
rem Pemakaian:   scripts\restore_db.bat backup\db_workshop_20261029_160000.sql db_workshop_uji
rem Skrip menolak jika database tujuan sudah berisi tabel, supaya data yang ada tidak tertimpa.
rem ============================================================================
setlocal EnableExtensions

if "%~2"=="" (
    echo Pemakaian: scripts\restore_db.bat BERKAS.sql NAMA_DATABASE_BARU
    exit /b 2
)
set "SQL=%~1"
set "DB=%~2"
if not exist "%SQL%" (
    echo Berkas tidak ditemukan: %SQL%
    exit /b 2
)

set "KLIEN="
for /f "delims=" %%i in ('where mysql 2^>nul') do if not defined KLIEN set "KLIEN=%%i"
if not defined KLIEN if exist "C:\xampp\mysql\bin\mysql.exe" set "KLIEN=C:\xampp\mysql\bin\mysql.exe"
if not defined KLIEN for /d %%d in ("C:\laragon\bin\mysql\*") do if exist "%%d\bin\mysql.exe" set "KLIEN=%%d\bin\mysql.exe"
if not defined KLIEN (
    echo GAGAL: mysql.exe tidak ditemukan. Isi lokasinya pada baris "set KLIEN=" di berkas ini.
    exit /b 1
)

set "ADA=0"
for /f %%n in ('"%KLIEN%" -h 127.0.0.1 -u root -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='%DB%'"') do set "ADA=%%n"
if not "%ADA%"=="0" (
    echo DITOLAK: database %DB% sudah berisi %ADA% tabel. Gunakan nama database yang baru.
    exit /b 1
)

"%KLIEN%" -h 127.0.0.1 -u root -e "CREATE DATABASE IF NOT EXISTS `%DB%` CHARACTER SET utf8mb4"
"%KLIEN%" -h 127.0.0.1 -u root --default-character-set=utf8mb4 %DB% < "%SQL%"
if errorlevel 1 (
    echo GAGAL saat mengimpor %SQL%
    exit /b 1
)

echo Dipulihkan ke database %DB%. Jumlah baris:
"%KLIEN%" -h 127.0.0.1 -u root %DB% -e "SELECT 'users' AS tabel, COUNT(*) AS baris FROM users UNION ALL SELECT 'mahasiswa', COUNT(*) FROM mahasiswa UNION ALL SELECT 'buku', COUNT(*) FROM buku;"
endlocal
