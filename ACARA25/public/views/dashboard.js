// Acara 25: dashboard. Langkah 4 (penjaga halaman), Langkah 5 (ringkasan data), tugas mandiri 1 (Logout)
// dan tugas mandiri 2 (tombol Tambah/Ubah/Hapus disembunyikan untuk role user).
// Memerlukan common.js (BASE_URL, Sesi, panggilApi, keLogin).

// ---------- Langkah 4: penjaga halaman ----------
// Tanpa token yang masih berlaku, pengguna dialihkan ke login. Ini hanya kenyamanan: data tetap dijaga server.
const sesi = Sesi.ambil();
if (!Sesi.valid()) {
    Sesi.hapus();
    keLogin(sesi.token ? 'sesi' : '');
} else {
    mulai();
}

function mulai() {
    const elIsi = document.getElementById('isi');
    const elStatus = document.getElementById('status');
    const elTabel = document.getElementById('tabel-mahasiswa');
    const elBody = elTabel.querySelector('tbody');
    const elTombolMuat = document.getElementById('tombol-muat');
    const elDialog = document.getElementById('dialog-form');
    const elForm = document.getElementById('form-mhs');
    const elGalatUmum = document.getElementById('galat-umum');
    const elSimpan = document.getElementById('tombol-simpan');
    const bidang = ['nim', 'nama', 'prodi'];

    let role = sesi.role === 'admin' ? 'admin' : 'user'; // hanya untuk tampilan
    let daftar = [];
    let idDiubah = null; // null = mode tambah

    // ---------- tampilan menurut role (tugas mandiri 2) ----------
    function terapkanRole() {
        const admin = role === 'admin';
        document.querySelectorAll('.hanya-admin').forEach((el) => { el.hidden = !admin; });
        document.getElementById('catatan-user').hidden = admin;
        document.getElementById('nama-pengguna').textContent = sesi.username || '';
        const lencana = document.getElementById('lencana-role');
        lencana.textContent = role;
        lencana.className = 'lencana' + (admin ? ' lencana-admin' : '');
        document.getElementById('stat-role').textContent = role === 'admin' ? 'Admin' : 'User';
    }

    function tampilkanStatus(teks, jenis) {
        elStatus.textContent = teks;
        elStatus.className = 'pesan pesan-' + jenis;
        elStatus.hidden = false;
    }

    // ---------- Langkah 5: ringkasan dan tabel ----------
    function render() {
        document.getElementById('stat-total').textContent = String(daftar.length);
        document.getElementById('stat-prodi').textContent =
            String(new Set(daftar.map((m) => m.prodi)).size);

        elBody.replaceChildren();
        daftar.forEach((mhs) => elBody.appendChild(buatBaris(mhs)));
        elTabel.hidden = daftar.length === 0;
        terapkanRole(); // kolom Aksi pada baris baru ikut disesuaikan
    }

    /** textContent dipakai (bukan innerHTML) agar data dari API tidak dieksekusi sebagai HTML. */
    function buatBaris(mhs) {
        const tr = document.createElement('tr');
        [mhs.nim, mhs.nama, mhs.prodi].forEach((nilai) => {
            const td = document.createElement('td');
            td.textContent = nilai ?? '';
            tr.appendChild(td);
        });

        const tdAksi = document.createElement('td');
        tdAksi.className = 'kolom-aksi hanya-admin';
        if (role === 'admin') { // role user: tombol tidak dibuat sama sekali
            const ubah = document.createElement('button');
            ubah.type = 'button';
            ubah.className = 'sekunder kecil';
            ubah.textContent = 'Ubah';
            ubah.addEventListener('click', () => bukaForm(mhs));
            const hapus = document.createElement('button');
            hapus.type = 'button';
            hapus.className = 'bahaya kecil';
            hapus.textContent = 'Hapus';
            hapus.addEventListener('click', () => hapusMahasiswa(mhs));
            tdAksi.append(ubah, hapus);
        }
        tr.appendChild(tdAksi);
        return tr;
    }

    async function muatData(pesanSukses) {
        elTombolMuat.disabled = true;
        elTabel.hidden = true;
        tampilkanStatus('Memuat data...', 'info');

        try {
            const { ok, status, hasil } = await panggilApi('GET', '/mahasiswa');
            if (!ok || !hasil || hasil.success !== true || !Array.isArray(hasil.data)) {
                const pesan = hasil && hasil.message ? hasil.message : 'Respons server tidak sesuai harapan';
                throw new Error(`${pesan} (HTTP ${status})`);
            }
            daftar = hasil.data;
            render();
            if (pesanSukses) {
                tampilkanStatus(pesanSukses, 'ok');
            } else if (daftar.length === 0) {
                tampilkanStatus('Belum ada data', 'info');
            } else {
                tampilkanStatus(`${daftar.length} data mahasiswa ditampilkan`, 'ok');
            }
        } catch (error) {
            if (error instanceof SesiBerakhir) return;
            console.error('Gagal memuat data:', error);
            tampilkanStatus('Gagal memuat data. Periksa apakah server berjalan, lalu coba lagi.', 'error');
        } finally {
            elTombolMuat.disabled = false;
        }
    }

    // ---------- Tambah dan Ubah (dialog) ----------
    function bersihkanGalat() {
        elGalatUmum.hidden = true;
        elGalatUmum.textContent = '';
        bidang.forEach((b) => { document.getElementById('galat-' + b).textContent = ''; });
    }

    function bukaForm(mhs) {
        idDiubah = mhs ? mhs.id : null;
        document.getElementById('judul-dialog').textContent = mhs ? 'Ubah Mahasiswa' : 'Tambah Mahasiswa';
        bidang.forEach((b) => { document.getElementById('f-' + b).value = mhs ? mhs[b] : ''; });
        bersihkanGalat();
        elDialog.showModal();
        document.getElementById('f-nim').focus();
    }

    /** 403 dari server: role di localStorage tidak sesuai token. Server yang benar, tampilan disesuaikan. */
    function tanganiDitolak(pesan) {
        elDialog.open && elDialog.close();
        role = 'user';
        Sesi.simpanRole('user');
        render();
        tampilkanStatus(pesan || 'Akses ditolak, hanya admin yang diizinkan', 'error');
    }

    elForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        bersihkanGalat();

        const data = {};
        bidang.forEach((b) => { data[b] = document.getElementById('f-' + b).value.trim(); });

        elSimpan.disabled = true;
        try {
            const edit = idDiubah !== null;
            const { ok, status, hasil } = await panggilApi(
                edit ? 'PUT' : 'POST',
                edit ? `/mahasiswa?id=${idDiubah}` : '/mahasiswa',
                data
            );

            if (ok) {
                elDialog.close();
                await muatData(edit ? 'Data berhasil diperbarui' : 'Data berhasil ditambahkan');
                return;
            }
            if (status === 403) {
                tanganiDitolak(hasil && hasil.message);
                return;
            }
            // 400 / 409 / 422: tampilkan pesan per field dari server
            const galat = (hasil && hasil.errors) || {};
            bidang.forEach((b) => {
                if (galat[b]) document.getElementById('galat-' + b).textContent = galat[b];
            });
            if (!bidang.some((b) => galat[b])) {
                elGalatUmum.textContent = (hasil && hasil.message) || `Gagal menyimpan (HTTP ${status})`;
                elGalatUmum.hidden = false;
            }
        } catch (error) {
            if (error instanceof SesiBerakhir) return;
            console.error('Gagal menyimpan:', error);
            elGalatUmum.textContent = 'Tidak dapat menghubungi server. Coba lagi.';
            elGalatUmum.hidden = false;
        } finally {
            elSimpan.disabled = false;
        }
    });

    document.getElementById('tombol-batal').addEventListener('click', () => elDialog.close());
    document.getElementById('tombol-tambah').addEventListener('click', () => bukaForm(null));

    // ---------- Hapus ----------
    async function hapusMahasiswa(mhs) {
        if (!window.confirm(`Hapus data ${mhs.nama} (${mhs.nim})? Tindakan ini tidak dapat dibatalkan.`)) return;
        try {
            const { ok, status, hasil } = await panggilApi('DELETE', `/mahasiswa?id=${mhs.id}`);
            if (ok) {
                await muatData('Data berhasil dihapus');
            } else if (status === 403) {
                tanganiDitolak(hasil && hasil.message);
            } else {
                tampilkanStatus((hasil && hasil.message) || `Gagal menghapus (HTTP ${status})`, 'error');
                if (status === 404) await muatData();
            }
        } catch (error) {
            if (error instanceof SesiBerakhir) return;
            console.error('Gagal menghapus:', error);
            tampilkanStatus('Tidak dapat menghubungi server. Coba lagi.', 'error');
        }
    }

    // ---------- Tugas mandiri 1: Logout ----------
    document.getElementById('tombol-logout').addEventListener('click', () => {
        Sesi.hapus(); // token dihapus dari browser; token itu sendiri tetap sah sampai habis masa berlakunya (JWT tanpa blacklist)
        keLogin('keluar');
    });

    elTombolMuat.addEventListener('click', () => muatData());

    // Sesi berakhir saat halaman dibiarkan terbuka: alihkan ke login begitu token kedaluwarsa.
    setTimeout(() => { Sesi.hapus(); keLogin('sesi'); }, Sesi.sisaWaktu(sesi.token) + 500);

    // Tombol Back setelah logout dapat memulihkan halaman dari cache browser: periksa sesi lagi.
    window.addEventListener('pageshow', (e) => {
        if (e.persisted && !Sesi.valid()) keLogin('');
    });

    terapkanRole();
    elIsi.hidden = false;
    muatData();
}
