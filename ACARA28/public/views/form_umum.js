// Acara 27: logika bersama form_tambah.html dan form_edit.html.
//  - jagaAdmin()   : penjaga halaman (perlu sesi yang berlaku dan role admin; server tetap penentu)
//  - nilai()       : membaca isian form
//  - tampilkanGalat(): pesan galat per field memakai kelas is-invalid dan invalid-feedback Bootstrap
//  - kirim(path, pesanSukses): validasi sisi View (tugas mandiri 2), kirim FormData (Langkah 1/2), notifikasi (tugas mandiri 1)
// Memerlukan common.js (BASE_URL, Sesi, panggilApi, keLogin, alamatFoto, Notifikasi, Validasi) dan Bootstrap.

const FormMhs = (() => {
    const BIDANG = ['nim', 'nama', 'prodi'];
    const SEMUA = [...BIDANG, 'foto'];
    const el = (id) => document.getElementById(id);

    let fotoAwal = null;    // alamat foto yang sudah tersimpan (mode ubah), ditampilkan sebagai pratinjau awal
    let urlSementara = null; // blob: URL pratinjau berkas yang baru dipilih

    /** Halaman form hanya untuk admin. Tanpa sesi: ke login. Role user: kembali ke dashboard dengan pesan. */
    function jagaAdmin() {
        const sesi = Sesi.ambil();
        if (!Sesi.valid()) {
            Sesi.hapus();
            keLogin(sesi.token ? 'sesi' : '');
            return false;
        }
        if (sesi.role !== 'admin') {
            Notifikasi.simpanUntukBerikutnya('Menambah dan mengubah data hanya untuk admin.', 'warning');
            window.location.replace('dashboard.html');
            return false;
        }
        // Sesi berakhir saat form dibiarkan terbuka: alihkan ke login begitu token kedaluwarsa.
        setTimeout(() => { Sesi.hapus(); keLogin('sesi'); }, Sesi.sisaWaktu(sesi.token) + 500);
        return true;
    }

    function nilai() {
        return {
            nim: el('f-nim').value,
            nama: el('f-nama').value,
            prodi: el('f-prodi').value,
            foto: el('f-foto').files[0] || null,
        };
    }

    /** Menandai field yang bermasalah. Mengembalikan id field pertama yang bermasalah (untuk fokus), atau null. */
    function tampilkanGalat(galat) {
        let pertama = null;
        SEMUA.forEach((b) => {
            const input = el('f-' + b);
            const umpan = el('galat-' + b);
            if (galat && galat[b]) {
                input.classList.add('is-invalid');
                umpan.textContent = galat[b];
                if (!pertama) pertama = input;
            } else {
                input.classList.remove('is-invalid');
                umpan.textContent = '';
            }
        });
        return pertama;
    }

    function galatUmum(teks) {
        const kotak = el('galat-umum');
        kotak.textContent = teks || '';
        kotak.hidden = !teks;
    }

    // ---------- pratinjau foto ----------
    function lepasUrlSementara() {
        if (urlSementara) {
            URL.revokeObjectURL(urlSementara);
            urlSementara = null;
        }
    }

    function tampilkanPratinjau(src) {
        const img = el('pratinjau');
        if (src) {
            img.src = src;
            el('pratinjau-bungkus').hidden = false;
        } else {
            img.removeAttribute('src');
            el('pratinjau-bungkus').hidden = true;
        }
    }

    function pasangPratinjau() {
        el('f-foto').addEventListener('change', () => {
            lepasUrlSementara();
            const input = el('f-foto');
            const berkas = input.files[0];
            input.classList.remove('is-invalid');
            el('galat-foto').textContent = '';
            if (!berkas) {
                tampilkanPratinjau(fotoAwal);
                return;
            }
            const galat = Validasi.foto(berkas);
            if (galat) {
                input.value = ''; // kosongkan pilihan agar berkas tidak sah tidak ikut terkirim
                tampilkanGalat({ foto: galat });
                tampilkanPratinjau(fotoAwal);
                return;
            }
            urlSementara = URL.createObjectURL(berkas);
            tampilkanPratinjau(urlSementara);
        });
        window.addEventListener('pagehide', lepasUrlSementara);
    }

    /** Mengisi form dengan data yang ada (mode ubah). */
    function isi(mhs) {
        BIDANG.forEach((b) => { el('f-' + b).value = mhs[b] ?? ''; });
        fotoAwal = alamatFoto(mhs.foto);
        tampilkanPratinjau(fotoAwal);
        if (fotoAwal) el('petunjuk-foto').textContent = 'Pilih berkas hanya jika ingin mengganti foto. JPG atau PNG, maksimal 2MB.';
    }

    function atur(aktif) {
        SEMUA.forEach((b) => { el('f-' + b).disabled = !aktif; });
        el('tombol-simpan').disabled = !aktif;
    }

    function sedangMenyimpan(ya) {
        const tombol = el('tombol-simpan');
        tombol.disabled = ya;
        el('spinner-simpan').hidden = !ya;
        el('teks-simpan').textContent = ya ? 'Menyimpan...' : 'Simpan';
    }

    /**
     * Mengirim isian form ke API.
     * @param {string} path         '/mahasiswa' (tambah) atau '/mahasiswa?id=N&_method=PUT' (ubah)
     * @param {string} pesanSukses  pesan toast di dashboard setelah berhasil
     *
     * Langkah 1 dan 2: data dikirim sebagai FormData (multipart/form-data); header Authorization ditambahkan oleh
     * panggilApi() dari token di localStorage. Content-Type TIDAK diisi manual agar browser menambahkan boundary.
     *
     * Perubahan data dikirim sebagai POST ?id=N&_method=PUT, bukan method PUT asli: PHP hanya mengisi $_POST dan
     * $_FILES dari body multipart untuk method POST, sehingga PUT + FormData kehilangan seluruh isiannya.
     * Router memperlakukan _method=PUT sebagai PUT /mahasiswa.
     */
    async function kirim(path, pesanSukses) {
        galatUmum('');
        const isian = nilai();

        // Tugas mandiri 2: validasi sisi View sebelum data dikirim ke Controller.
        const galat = Validasi.mahasiswa(isian);
        const pertama = tampilkanGalat(galat);
        if (pertama) {
            pertama.focus();
            Notifikasi.tampil('Periksa kembali isian formulir.', 'warning');
            return;
        }

        const data = new FormData();
        BIDANG.forEach((b) => { data.append(b, String(isian[b]).trim()); });
        if (isian.foto) data.append('foto', isian.foto);

        sedangMenyimpan(true);
        try {
            const { ok, status, hasil } = await panggilApi('POST', path, data);

            if (ok) {
                Notifikasi.simpanUntukBerikutnya(pesanSukses, 'success');
                window.location.href = 'dashboard.html';
                return; // tombol dibiarkan nonaktif selama berpindah halaman
            }
            if (status === 403) {
                Sesi.simpanRole('user'); // role di localStorage tidak sesuai token; server yang benar
                Notifikasi.simpanUntukBerikutnya((hasil && hasil.message) || 'Akses ditolak, hanya admin yang diizinkan', 'danger');
                window.location.href = 'dashboard.html';
                return;
            }

            // 400 / 404 / 409 / 422: tampilkan pesan per field dari server
            const dariServer = (hasil && hasil.errors) || {};
            const adaPerField = SEMUA.some((b) => dariServer[b]);
            const fokus = tampilkanGalat(dariServer);
            if (fokus) fokus.focus();
            const pesan = (hasil && hasil.message) || `Gagal menyimpan (HTTP ${status})`;
            if (!adaPerField) galatUmum(pesan);
            Notifikasi.tampil('Data gagal disimpan: ' + pesan, 'danger');
        } catch (error) {
            if (error instanceof SesiBerakhir) return;
            console.error('Gagal menyimpan:', error);
            galatUmum('Tidak dapat menghubungi server. Coba lagi.');
            Notifikasi.tampil('Tidak dapat menghubungi server. Data belum disimpan.', 'danger');
        }
        sedangMenyimpan(false);
    }

    return { jagaAdmin, nilai, tampilkanGalat, galatUmum, pasangPratinjau, isi, atur, kirim };
})();
