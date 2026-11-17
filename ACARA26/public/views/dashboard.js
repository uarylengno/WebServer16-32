// Acara 25: dashboard. Langkah 4 (penjaga halaman), Langkah 5 (ringkasan data), tugas mandiri 1 (Logout)
// dan tugas mandiri 2 (tombol Tambah/Ubah/Hapus disembunyikan untuk role user).
// Acara 26 (Langkah 5): form tambah/ubah mengirim FormData (multipart) agar dapat membawa berkas foto;
// tabel menampilkan kolom foto.
// Memerlukan common.js (BASE_URL, Sesi, panggilApi, keLogin, alamatFoto).

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
    const elFoto = document.getElementById('f-foto');
    const elPratinjau = document.getElementById('pratinjau');
    const elPratinjauBungkus = document.getElementById('pratinjau-bungkus');
    const elPetunjukFoto = document.getElementById('petunjuk-foto');
    const FOTO_MAKS_BYTE = 2 * 1024 * 1024; // sama dengan batas di server (MahasiswaController)

    let role = sesi.role === 'admin' ? 'admin' : 'user'; // hanya untuk tampilan
    let daftar = [];
    let idDiubah = null; // null = mode tambah
    let fotoSaatIni = null; // alamat foto data yang sedang diubah (ditampilkan sebagai pratinjau)
    let urlSementara = null; // blob: URL pratinjau berkas yang baru dipilih

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

    /** Sel foto: gambar bulat kecil, atau huruf pertama nama bila belum ada foto / gambar gagal dimuat. */
    function buatSelFoto(mhs) {
        const td = document.createElement('td');
        td.className = 'kolom-foto';

        const buatKosong = () => {
            const kosong = document.createElement('div');
            kosong.className = 'foto-kosong';
            kosong.textContent = Array.from(String(mhs.nama || '?').trim())[0]?.toUpperCase() || '?';
            kosong.title = 'Belum ada foto';
            return kosong;
        };

        const src = alamatFoto(mhs.foto);
        if (!src) {
            td.appendChild(buatKosong());
            return td;
        }
        const img = document.createElement('img');
        img.className = 'foto-kecil';
        img.width = 44;
        img.height = 44;
        img.loading = 'lazy';
        img.alt = 'Foto ' + (mhs.nama || ''); // properti DOM, bukan innerHTML: aman dari injeksi HTML
        img.src = src;
        img.addEventListener('error', () => img.replaceWith(buatKosong()));
        td.appendChild(img);
        return td;
    }

    /** textContent dipakai (bukan innerHTML) agar data dari API tidak dieksekusi sebagai HTML. */
    function buatBaris(mhs) {
        const tr = document.createElement('tr');
        tr.appendChild(buatSelFoto(mhs));
        [mhs.nim, mhs.nama, mhs.prodi].forEach((nilai, i) => {
            const td = document.createElement('td');
            if (i === 0) td.className = 'kolom-nim'; // NIM tidak dipotong ke baris baru
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
        [...bidang, 'foto'].forEach((b) => { document.getElementById('galat-' + b).textContent = ''; });
    }

    // ---------- foto: pemeriksaan awal dan pratinjau ----------
    // Pemeriksaan di browser hanya untuk kenyamanan (pesan cepat, tanpa mengirim berkas besar). Penentu
    // sebenarnya adalah server: nama/tipe berkas di sisi client mudah dipalsukan.
    function periksaFoto(berkas) {
        if (!['image/jpeg', 'image/png'].includes(berkas.type)) return 'Berkas harus berupa gambar JPG/PNG yang valid';
        if (berkas.size > FOTO_MAKS_BYTE) return 'Ukuran berkas maksimal 2MB';
        return '';
    }

    function lepasUrlSementara() {
        if (urlSementara) {
            URL.revokeObjectURL(urlSementara);
            urlSementara = null;
        }
    }

    function tampilkanPratinjau(src) {
        if (src) {
            elPratinjau.src = src;
            elPratinjauBungkus.hidden = false;
        } else {
            elPratinjau.removeAttribute('src');
            elPratinjauBungkus.hidden = true;
        }
    }

    elFoto.addEventListener('change', () => {
        lepasUrlSementara();
        document.getElementById('galat-foto').textContent = '';
        const berkas = elFoto.files[0];
        if (!berkas) {
            tampilkanPratinjau(fotoSaatIni);
            return;
        }
        const galat = periksaFoto(berkas);
        if (galat) {
            elFoto.value = ''; // kosongkan pilihan agar berkas tidak sah tidak ikut terkirim
            document.getElementById('galat-foto').textContent = galat;
            tampilkanPratinjau(fotoSaatIni);
            return;
        }
        urlSementara = URL.createObjectURL(berkas);
        tampilkanPratinjau(urlSementara);
    });
    elDialog.addEventListener('close', lepasUrlSementara);

    function bukaForm(mhs) {
        idDiubah = mhs ? mhs.id : null;
        document.getElementById('judul-dialog').textContent = mhs ? 'Ubah Mahasiswa' : 'Tambah Mahasiswa';
        bidang.forEach((b) => { document.getElementById('f-' + b).value = mhs ? mhs[b] : ''; });
        lepasUrlSementara();
        elFoto.value = ''; // input file tidak boleh diisi lewat skrip; cukup dikosongkan
        fotoSaatIni = mhs ? alamatFoto(mhs.foto) : null;
        tampilkanPratinjau(fotoSaatIni);
        elPetunjukFoto.textContent = fotoSaatIni
            ? 'Pilih berkas hanya jika ingin mengganti foto. JPG atau PNG, maksimal 2MB.'
            : 'JPG atau PNG, maksimal 2MB.';
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

        // Langkah 5: berkas tidak dapat dikirim sebagai JSON, sehingga memakai FormData (multipart/form-data).
        const data = new FormData();
        bidang.forEach((b) => { data.append(b, document.getElementById('f-' + b).value.trim()); });
        const berkas = elFoto.files[0];
        if (berkas) {
            const galatFoto = periksaFoto(berkas);
            if (galatFoto) {
                document.getElementById('galat-foto').textContent = galatFoto;
                return;
            }
            data.append('foto', berkas);
        }

        elSimpan.disabled = true;
        try {
            const edit = idDiubah !== null;
            // PHP hanya mengisi $_POST/$_FILES untuk POST multipart. Perubahan data dikirim sebagai
            // POST ?id=N&_method=PUT, yang oleh Router diperlakukan sebagai PUT /mahasiswa.
            const { ok, status, hasil } = await panggilApi(
                'POST',
                edit ? `/mahasiswa?id=${idDiubah}&_method=PUT` : '/mahasiswa',
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
            [...bidang, 'foto'].forEach((b) => {
                if (galat[b]) document.getElementById('galat-' + b).textContent = galat[b];
            });
            if (![...bidang, 'foto'].some((b) => galat[b])) {
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
