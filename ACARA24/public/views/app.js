// Acara 24: menampilkan data mahasiswa dari API memakai Fetch API + async/await.

// Alamat API. Secara otomatis diturunkan dari alamat halaman ini, sehingga tetap benar
// di folder mana pun proyek ditaruh:
//   http://localhost/ACARA16-25/ACARA24/public/views/index.html
//   -> http://localhost/ACARA16-25/ACARA24/public
// Jika halaman dibuka langsung dari berkas (file://), dipakai alamat bawaan di bawah.
const BASE_URL_BAWAAN = 'http://localhost/ACARA16-25/ACARA24/public';
const BASE_URL = window.location.protocol.startsWith('http')
    ? window.location.origin + window.location.pathname.replace(/\/views\/[^/]*$/, '')
    : BASE_URL_BAWAAN;

const elStatus = document.getElementById('status');
const elTabel = document.getElementById('tabel-mahasiswa');
const elBody = elTabel.querySelector('tbody');
const elTombol = document.getElementById('tombol-muat');

/** Menampilkan pesan status. jenis: 'memuat' | 'kosong' | 'error' | 'ok' */
function tampilkanStatus(teks, jenis) {
    elStatus.textContent = teks;
    elStatus.className = 'status status-' + jenis;
    elStatus.hidden = false;
}

function sembunyikanStatus() {
    elStatus.hidden = true;
}

/** Membuat satu baris tabel. textContent dipakai (bukan innerHTML) agar data dari API tidak dieksekusi sebagai HTML. */
function buatBaris(mhs) {
    const tr = document.createElement('tr');
    [mhs.nim, mhs.nama, mhs.prodi].forEach((nilai) => {
        const td = document.createElement('td');
        td.textContent = nilai ?? '';
        tr.appendChild(td);
    });
    return tr;
}

async function muatDataMahasiswa() {
    // Tugas mandiri 1: indikator "Memuat data..." tampil sebelum data diterima.
    elTombol.disabled = true;
    elTabel.hidden = true;
    elBody.replaceChildren();
    tampilkanStatus('Memuat data...', 'memuat');

    try {
        const response = await fetch(`${BASE_URL}/mahasiswa`);

        // fetch() hanya gagal (reject) bila jaringan putus. Status 404/500 tetap dianggap "berhasil",
        // jadi response.ok harus diperiksa sendiri.
        let hasil = null;
        try {
            hasil = await response.json();
        } catch (e) {
            hasil = null; // body bukan JSON (mis. halaman error server)
        }

        if (!response.ok || !hasil || hasil.success !== true || !Array.isArray(hasil.data)) {
            const pesan = hasil && hasil.message ? hasil.message : 'Respons server tidak sesuai harapan';
            throw new Error(`${pesan} (HTTP ${response.status})`);
        }

        // Tugas mandiri 2: kondisi data kosong.
        if (hasil.data.length === 0) {
            tampilkanStatus('Belum ada data', 'kosong');
            return;
        }

        hasil.data.forEach((mhs) => elBody.appendChild(buatBaris(mhs)));
        elTabel.hidden = false;
        tampilkanStatus(`${hasil.data.length} data mahasiswa ditampilkan`, 'ok');
    } catch (error) {
        console.error('Gagal memuat data:', error);
        tampilkanStatus('Gagal memuat data. Periksa apakah server berjalan, lalu coba lagi.', 'error');
    } finally {
        elTombol.disabled = false;
    }
}

elTombol.addEventListener('click', muatDataMahasiswa);
document.addEventListener('DOMContentLoaded', muatDataMahasiswa);
