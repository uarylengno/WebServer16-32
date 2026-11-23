// Acara 24: menampilkan data mahasiswa dari API memakai Fetch API + async/await.
// Acara 29: indikator loading pada tabel (Langkah 1), escapeHTML pada output (Langkah 2), percobaan ulang (tugas 2).
// Memerlukan common.js (BASE_URL, escapeHTML, barisPesan, fetchDenganUlang, Tombol).

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

// Langkah 2: setiap nilai dari API melewati escapeHTML() sebelum masuk ke innerHTML, sehingga data seperti
// <b>Coba</b> atau <img src=x onerror=...> tampil sebagai teks biasa dan tidak dieksekusi browser.
function renderTabel(data) {
    elBody.innerHTML = data.map((mhs) =>
        `<tr><td>${escapeHTML(mhs.nim)}</td><td>${escapeHTML(mhs.nama)}</td><td>${escapeHTML(mhs.prodi)}</td></tr>`
    ).join('');
}

async function muatDataMahasiswa() {
    // Langkah 1: indikator "Memuat data..." tampil di dalam tabel sebelum data diterima.
    Tombol.sibuk(elTombol, true, 'Memuat...');
    elTabel.hidden = false;
    barisPesan(elBody, 3, 'Memuat data...', true);
    tampilkanStatus('Memuat data...', 'memuat');

    try {
        // Tugas mandiri 2: satu kali percobaan ulang otomatis bila jaringan bermasalah sementara.
        const response = await fetchDenganUlang(`${BASE_URL}/mahasiswa`, {}, {
            saatUlang: () => tampilkanStatus('Koneksi bermasalah, mencoba lagi...', 'memuat'),
        });

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

        if (hasil.data.length === 0) {
            elTabel.hidden = true;
            elBody.replaceChildren();
            tampilkanStatus('Belum ada data', 'kosong');
            return;
        }

        renderTabel(hasil.data);
        tampilkanStatus(`${hasil.data.length} data mahasiswa ditampilkan`, 'ok');
    } catch (error) {
        console.error('Gagal memuat data:', error);
        // Pesan galat dari server dapat berisi HTML: barisPesan() meng-escape-nya.
        barisPesan(elBody, 3, 'Gagal memuat data: ' + error.message);
        tampilkanStatus('Gagal memuat data. Periksa apakah server berjalan, lalu coba lagi.', 'error');
    } finally {
        Tombol.sibuk(elTombol, false);
    }
}

elTombol.addEventListener('click', muatDataMahasiswa);
document.addEventListener('DOMContentLoaded', muatDataMahasiswa);
