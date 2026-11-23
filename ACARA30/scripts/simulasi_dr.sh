#!/usr/bin/env bash
# Acara 30 (tugas mandiri 3): simulasi pemulihan bencana (disaster recovery) pada database PERCOBAAN.
#
# Pemakaian (dari folder proyek; di Windows jalankan lewat Git Bash):   scripts/simulasi_dr.sh [db_percobaan]
# Default database percobaan: db_workshop_acara30_dr (dibuat dari db_workshop_acara30.sql bila belum ada).
# Alur: 1) buat database percobaan  2) catat isi (jumlah baris + checksum)  3) backup (scripts/backup_db.sh, Acara 26)
#       4) "bencana": DROP TABLE mahasiswa  5) pastikan data hilang  6) restore (scripts/restore_db.sh)  7) bandingkan.
# Pengaman: nama database harus berakhiran _dr atau _uji. Database asli (db_workshop_acara30) tidak pernah disentuh.
# Environment opsional: DB_HOST, DB_USER, MYSQL_PWD, MYSQL, MYSQLDUMP (sama seperti skrip backup/restore).
set -euo pipefail

DB="${1:-db_workshop_acara30_dr}"
HOST="${DB_HOST:-127.0.0.1}"; USER_DB="${DB_USER:-root}"; CLIENT="${MYSQL:-mysql}"
PROYEK="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
[[ "$DB" =~ ^[A-Za-z0-9_]+_(dr|uji)$ ]] || { echo "Nama database percobaan harus berakhiran _dr atau _uji (pengaman)." >&2; exit 2; }
q() { "$CLIENT" -h "$HOST" -u "$USER_DB" -N -B "$@"; }

echo "== 1. Menyiapkan database percobaan: $DB"
if [ "$(q -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB'")" = "0" ]; then
    q -e "CREATE DATABASE IF NOT EXISTS \`$DB\` CHARACTER SET utf8mb4"
    # SQL proyek memuat CREATE DATABASE/USE untuk database aslinya: buang kedua baris itu agar masuk ke database percobaan.
    grep -v -i -E '^\s*(CREATE DATABASE|USE )' "$PROYEK/db_workshop_acara30.sql" | "$CLIENT" -h "$HOST" -u "$USER_DB" --default-character-set=utf8mb4 "$DB"
    q "$DB" -e "INSERT INTO mahasiswa (nim,nama,prodi) VALUES ('900000001','Contoh Satu','TI'),('900000002','Contoh Dua','SI')"
fi
RINGKAS="SELECT 'users', COUNT(*) FROM users UNION ALL SELECT 'mahasiswa', COUNT(*) FROM mahasiswa UNION ALL SELECT 'buku', COUNT(*) FROM buku"
SEBELUM="$(q "$DB" -e "$RINGKAS"; q "$DB" -e "CHECKSUM TABLE users, mahasiswa, buku")"
echo "== 2. Kondisi sebelum bencana (tabel, jumlah baris / checksum):"; echo "$SEBELUM"

echo "== 3. Backup"
"$PROYEK/scripts/backup_db.sh" "$DB"
BERKAS="$(ls -t "$PROYEK"/backup/"${DB}"_*.sql | head -n 1)"

echo "== 4. BENCANA: DROP TABLE mahasiswa"
q "$DB" -e "DROP TABLE mahasiswa"
if q "$DB" -e "SELECT COUNT(*) FROM mahasiswa" >/dev/null 2>&1; then echo "TIDAK TERDUGA: tabel masih ada" >&2; exit 1; fi
echo "== 5. Terbukti hilang: SELECT dari mahasiswa gagal (tabel tidak ada)."

echo "== 6. Restore dari $BERKAS"
"$PROYEK/scripts/restore_db.sh" "$BERKAS" "$DB" --force

SESUDAH="$(q "$DB" -e "$RINGKAS"; q "$DB" -e "CHECKSUM TABLE users, mahasiswa, buku")"
echo "== 7. Perbandingan:"
if [ "$SEBELUM" = "$SESUDAH" ]; then
    echo "BERHASIL: jumlah baris dan checksum semua tabel sama dengan sebelum bencana."
else
    echo "GAGAL: kondisi setelah restore berbeda." >&2; diff <(echo "$SEBELUM") <(echo "$SESUDAH") >&2 || true; exit 1
fi
