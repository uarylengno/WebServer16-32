#!/usr/bin/env bash
# Acara 26 (Langkah 6): memulihkan cadangan .sql ke sebuah database.
#
# Pemakaian:   scripts/restore_db.sh backup/db_workshop_20261029_160000.sql db_workshop_uji
#
# Pengaman: skrip MENOLAK memulihkan ke database yang sudah berisi tabel (agar data yang ada tidak tertimpa
# tanpa sengaja). Untuk uji restore, pakai nama database BARU. Untuk benar-benar menimpa, tambahkan --force.
#   scripts/restore_db.sh <berkas.sql> <database> [--force]
#
# Environment variable opsional: DB_HOST, DB_USER, MYSQL_PWD, MYSQL (lokasi klien mysql).
set -euo pipefail

if [ "$#" -lt 2 ]; then
    echo "Pemakaian: $0 <berkas.sql> <nama_database> [--force]" >&2
    exit 2
fi
SQL="$1"; DB="$2"; FORCE="${3:-}"
HOST="${DB_HOST:-127.0.0.1}"
USER_DB="${DB_USER:-root}"
CLIENT="${MYSQL:-mysql}"

[ -f "$SQL" ] || { echo "Berkas tidak ditemukan: $SQL" >&2; exit 2; }
[[ "$DB" =~ ^[A-Za-z0-9_]+$ ]] || { echo "Nama database hanya boleh huruf, angka, dan _" >&2; exit 2; }

ADA=$("$CLIENT" -h "$HOST" -u "$USER_DB" -N -B -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB'")
if [ "$ADA" != "0" ] && [ "$FORCE" != "--force" ]; then
    echo "DITOLAK: database '$DB' sudah berisi $ADA tabel. Pakai nama database baru, atau tambahkan --force." >&2
    exit 1
fi

"$CLIENT" -h "$HOST" -u "$USER_DB" -e "CREATE DATABASE IF NOT EXISTS \`$DB\` CHARACTER SET utf8mb4"
"$CLIENT" -h "$HOST" -u "$USER_DB" --default-character-set=utf8mb4 "$DB" < "$SQL"

echo "Dipulihkan ke database '$DB'. Verifikasi jumlah baris:"
"$CLIENT" -h "$HOST" -u "$USER_DB" "$DB" -e \
  "SELECT 'users' AS tabel, COUNT(*) AS baris FROM users
   UNION ALL SELECT 'mahasiswa', COUNT(*) FROM mahasiswa
   UNION ALL SELECT 'buku', COUNT(*) FROM buku;"
