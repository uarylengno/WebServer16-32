#!/usr/bin/env bash
# Acara 26 (Langkah 6 dan tugas mandiri 2): backup database + foto upload.
#
# Pemakaian (dari folder proyek):   scripts/backup_db.sh [nama_database]
# Hasil di folder backup/ :
#   <db>_<tanggal_jam>.sql           -> isi database (struktur + data)
#   uploads_<tanggal_jam>.tar.gz     -> foto di public/uploads/ (foto TIDAK ikut tersimpan di dalam database)
#
# Pengaturan lewat environment variable (semua opsional):
#   DB_HOST (default 127.0.0.1)   DB_USER (default root)   MYSQL_PWD (kata sandi MySQL, default kosong)
#   MYSQLDUMP (lokasi mysqldump jika tidak ada di PATH)
set -euo pipefail

DB="${1:-db_workshop_acara30}"
HOST="${DB_HOST:-127.0.0.1}"
USER_DB="${DB_USER:-root}"
DUMP="${MYSQLDUMP:-mysqldump}"

PROYEK="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"   # folder proyek
OUT_DIR="$PROYEK/backup"
STAMP="$(date +%Y%m%d_%H%M%S)"
SQL="$OUT_DIR/${DB}_${STAMP}.sql"

mkdir -p "$OUT_DIR"

# --single-transaction : salinan konsisten tanpa mengunci tabel InnoDB
# --routines           : ikut menyimpan stored procedure/function bila ada
# Tanpa --databases    : berkas tidak memuat CREATE DATABASE/USE, sehingga dapat dipulihkan ke database
#                        dengan NAMA LAIN (uji restore) tanpa menimpa database asli.
if ! "$DUMP" -h "$HOST" -u "$USER_DB" --single-transaction --routines \
        --default-character-set=utf8mb4 "$DB" > "$SQL"; then
    rm -f "$SQL"   # jangan menyisakan berkas kosong yang tampak seperti cadangan
    echo "GAGAL: mysqldump error (cek nama database dan apakah MySQL berjalan)." >&2
    exit 1
fi

# Cadangan yang kosong/terpotong tidak berguna: pastikan ada CREATE TABLE dan penanda akhir dump.
if ! grep -q "CREATE TABLE" "$SQL" || ! tail -n 3 "$SQL" | grep -q "Dump completed"; then
    echo "GAGAL: berkas cadangan tidak lengkap: $SQL" >&2
    exit 1
fi
echo "OK  database : $SQL ($(wc -c < "$SQL") byte)"

if [ -d "$PROYEK/public/uploads" ]; then
    ARSIP="$OUT_DIR/uploads_${STAMP}.tar.gz"
    tar -czf "$ARSIP" -C "$PROYEK/public" uploads
    echo "OK  foto     : $ARSIP ($(wc -c < "$ARSIP") byte)"
fi

echo "Selesai. Simpan juga salinannya di tempat lain (flash disk / penyimpanan pribadi), jangan di repository publik."
