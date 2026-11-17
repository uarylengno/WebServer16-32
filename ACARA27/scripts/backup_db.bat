@echo off
rem ============================================================================
rem Acara 26 (Langkah 6 dan tugas mandiri 2): backup database + foto upload (Windows: Laragon / XAMPP).
rem
rem Pemakaian: klik dua kali, atau dari CMD:   scripts\backup_db.bat [nama_database]
rem Hasil di folder backup\ :
rem    NAMA_DB_TANGGAL_JAM.sql     isi database
rem    uploads_TANGGAL_JAM.zip     foto di public\uploads (foto TIDAK ikut tersimpan di dalam database)
rem Jika MySQL memakai kata sandi:  set MYSQL_PWD=katasandi   sebelum menjalankan skrip.
rem ============================================================================
setlocal EnableExtensions

set "DB=%~1"
if "%DB%"=="" set "DB=db_workshop_acara27"

rem Folder proyek = satu tingkat di atas folder scripts
set "PROYEK=%~dp0.."
set "OUT=%PROYEK%\backup"
if not exist "%OUT%" mkdir "%OUT%"

rem --- cari mysqldump.exe: PATH, lalu XAMPP, lalu Laragon ---
set "DUMP="
for /f "delims=" %%i in ('where mysqldump 2^>nul') do if not defined DUMP set "DUMP=%%i"
if not defined DUMP if exist "C:\xampp\mysql\bin\mysqldump.exe" set "DUMP=C:\xampp\mysql\bin\mysqldump.exe"
if not defined DUMP for /d %%d in ("C:\laragon\bin\mysql\*") do if exist "%%d\bin\mysqldump.exe" set "DUMP=%%d\bin\mysqldump.exe"
if not defined DUMP (
    echo GAGAL: mysqldump.exe tidak ditemukan. Isi lokasinya pada baris "set DUMP=" di berkas ini.
    exit /b 1
)

rem --- penanda waktu: yyyyMMdd_HHmmss ---
for /f %%t in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "STAMP=%%t"

set "SQL=%OUT%\%DB%_%STAMP%.sql"
"%DUMP%" -h 127.0.0.1 -u root --single-transaction --routines --default-character-set=utf8mb4 --result-file="%SQL%" %DB%
if errorlevel 1 (
    echo GAGAL: mysqldump mengembalikan error. Periksa nama database dan apakah MySQL sedang berjalan.
    if exist "%SQL%" del "%SQL%"
    exit /b 1
)
echo OK  database : %SQL%

if exist "%PROYEK%\public\uploads" (
    powershell -NoProfile -Command "Compress-Archive -Path '%PROYEK%\public\uploads' -DestinationPath '%OUT%\uploads_%STAMP%.zip' -Force"
    echo OK  foto     : %OUT%\uploads_%STAMP%.zip
)

echo.
echo Selesai. Salin juga ke tempat lain (flash disk / penyimpanan pribadi). Jangan unggah ke repository publik.
endlocal
