// Acara 27, Langkah 2: form ubah data (form_edit.html?id=N).
// muatDataUntukEdit(id) mengambil data lewat GET /mahasiswa?id=N (MahasiswaController::index()) dan mengisi form;
// simpanPerubahan(id) mengirim FormData ke POST /mahasiswa?id=N&_method=PUT (diteruskan Router ke update()).
// Logika bersama ada di form_umum.js. Memerlukan common.js dan Bootstrap.

if (FormMhs.jagaAdmin()) {
    mulai();
}

function kembaliKeDashboard(pesan, jenis) {
    Notifikasi.simpanUntukBerikutnya(pesan, jenis);
    window.location.replace('dashboard.html');
}

async function muatDataUntukEdit(id) {
    const elStatus = document.getElementById('status-muat');
    try {
        const { ok, status, hasil } = await panggilApi('GET', `/mahasiswa?id=${id}`);
        if (status === 404) {
            kembaliKeDashboard('Data yang akan diubah tidak ditemukan.', 'danger');
            return;
        }
        if (!ok || !hasil || hasil.success !== true || !hasil.data || typeof hasil.data !== 'object') {
            throw new Error((hasil && hasil.message) || `HTTP ${status}`);
        }
        FormMhs.isi(hasil.data);
        FormMhs.atur(true);
        elStatus.hidden = true;
        document.getElementById('f-nim').focus();
    } catch (error) {
        if (error instanceof SesiBerakhir) return;
        console.error('Gagal memuat data:', error);
        elStatus.className = 'alert alert-danger py-2';
        elStatus.textContent = 'Gagal memuat data. Periksa apakah server berjalan, lalu muat ulang halaman.';
        Notifikasi.tampil('Gagal memuat data dari server.', 'danger');
    }
}

function simpanPerubahan(id) {
    return FormMhs.kirim(`/mahasiswa?id=${id}&_method=PUT`, 'Data berhasil diperbarui.');
}

function mulai() {
    // id harus bilangan bulat positif; selain itu halaman tidak diproses.
    const id = new URLSearchParams(window.location.search).get('id');
    if (!/^[1-9][0-9]{0,9}$/.test(id || '')) {
        kembaliKeDashboard('Alamat halaman ubah tidak valid: id data tidak ada.', 'danger');
        return;
    }

    document.getElementById('isi').hidden = false;
    FormMhs.atur(false); // dinonaktifkan sampai data selesai dimuat
    FormMhs.pasangPratinjau();
    muatDataUntukEdit(id);

    document.getElementById('form-mhs').addEventListener('submit', (e) => {
        e.preventDefault();
        simpanPerubahan(id);
    });
}
