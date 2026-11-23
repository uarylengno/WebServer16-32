// Acara 25: dashboard. Langkah 4 (penjaga halaman), Langkah 5 (ringkasan data), tugas mandiri 1 (Logout)
// dan tugas mandiri 2 (tombol Tambah/Ubah/Hapus disembunyikan untuk role user).
// Acara 26: tabel menampilkan kolom foto.
// Acara 27: Tambah dan Ubah dipindah ke halaman form_tambah.html dan form_edit.html. Dashboard menampilkan data,
// menangani Hapus (Langkah 3) tanpa memuat ulang halaman, dan menampilkan notifikasi (tugas mandiri 1).
// Memerlukan common.js (BASE_URL, Sesi, panggilApi, keLogin, alamatFoto, Notifikasi) dan Bootstrap.

// ---------- penjaga halaman ----------
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
    const elModalHapus = document.getElementById('modal-hapus');
    const modalHapus = new bootstrap.Modal(elModalHapus);
    const elKonfirmasiHapus = document.getElementById('tombol-konfirmasi-hapus');

    let role = sesi.role === 'admin' ? 'admin' : 'user'; // hanya untuk tampilan
    let daftar = [];
    let sedangHapus = null; // data yang menunggu konfirmasi hapus

    // ---------- tampilan menurut role ----------
    function terapkanRole() {
        const admin = role === 'admin';
        document.querySelectorAll('.hanya-admin').forEach((el) => { el.hidden = !admin; });
        document.getElementById('catatan-user').hidden = admin;
        document.getElementById('nama-pengguna').textContent = sesi.username || '';
        const lencana = document.getElementById('lencana-role');
        lencana.textContent = role;
        lencana.className = 'badge ' + (admin ? 'text-bg-primary' : 'text-bg-secondary');
        document.getElementById('stat-role').textContent = admin ? 'Admin' : 'User';
    }

    function tampilkanStatus(teks, jenis) {
        elStatus.textContent = teks;
        elStatus.className = 'alert py-2 alert-' + jenis;
        elStatus.hidden = false;
    }

    // ---------- tabel dan ringkasan ----------
    function render() {
        document.getElementById('stat-total').textContent = String(daftar.length);
        document.getElementById('stat-prodi').textContent = String(new Set(daftar.map((m) => m.prodi)).size);

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
            kosong.className = 'foto-kosong rounded-circle bg-secondary-subtle text-secondary-emphasis ' +
                'd-inline-flex align-items-center justify-content-center fw-semibold';
            kosong.style.width = '44px';
            kosong.style.height = '44px';
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
        img.className = 'foto-kecil rounded-circle object-fit-cover border';
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
            if (i === 0) td.className = 'kolom-nim text-nowrap';
            td.textContent = nilai ?? '';
            tr.appendChild(td);
        });

        const tdAksi = document.createElement('td');
        tdAksi.className = 'kolom-aksi hanya-admin text-end text-nowrap';
        if (role === 'admin') { // role user: tombol tidak dibuat sama sekali
            const ubah = document.createElement('a');
            ubah.className = 'btn btn-outline-primary btn-sm me-1 tombol-ubah';
            ubah.textContent = 'Ubah';
            ubah.href = 'form_edit.html?id=' + encodeURIComponent(mhs.id); // Langkah 2: halaman form_edit.html
            const hapus = document.createElement('button');
            hapus.type = 'button';
            hapus.className = 'btn btn-outline-danger btn-sm tombol-hapus';
            hapus.textContent = 'Hapus';
            hapus.addEventListener('click', () => mintaKonfirmasiHapus(mhs));
            tdAksi.append(ubah, hapus);
        }
        tr.appendChild(tdAksi);
        return tr;
    }

    /** Memuat ulang data lewat fetch (tanpa memuat ulang halaman). Mengembalikan true bila berhasil. */
    async function muatData() {
        elTombolMuat.disabled = true;
        tampilkanStatus('Memuat data...', 'info');

        try {
            const { ok, status, hasil } = await panggilApi('GET', '/mahasiswa');
            if (!ok || !hasil || hasil.success !== true || !Array.isArray(hasil.data)) {
                const pesan = hasil && hasil.message ? hasil.message : 'Respons server tidak sesuai harapan';
                throw new Error(`${pesan} (HTTP ${status})`);
            }
            daftar = hasil.data;
            render();
            tampilkanStatus(daftar.length === 0 ? 'Belum ada data' : `${daftar.length} data mahasiswa ditampilkan`,
                daftar.length === 0 ? 'info' : 'success');
            return true;
        } catch (error) {
            if (error instanceof SesiBerakhir) return false;
            console.error('Gagal memuat data:', error);
            elTabel.hidden = true;
            tampilkanStatus('Gagal memuat data. Periksa apakah server berjalan, lalu coba lagi.', 'danger');
            Notifikasi.tampil('Gagal memuat data dari server.', 'danger');
            return false;
        } finally {
            elTombolMuat.disabled = false;
        }
    }

    /** 403 dari server: role di localStorage tidak sesuai token. Server yang benar, tampilan disesuaikan. */
    function tanganiDitolak(pesan) {
        role = 'user';
        Sesi.simpanRole('user');
        render();
        Notifikasi.tampil(pesan || 'Akses ditolak, hanya admin yang diizinkan', 'danger');
    }

    // ---------- Langkah 3: Hapus ----------
    function mintaKonfirmasiHapus(mhs) {
        sedangHapus = mhs;
        document.getElementById('teks-hapus').textContent = `Hapus data ${mhs.nama} (${mhs.nim})?`;
        modalHapus.show();
    }

    elKonfirmasiHapus.addEventListener('click', async () => {
        const mhs = sedangHapus;
        if (!mhs) return;
        elKonfirmasiHapus.disabled = true;
        try {
            // Method DELETE dengan id pada query string dan token pada header Authorization (ditambahkan panggilApi).
            const { ok, status, hasil } = await panggilApi('DELETE', `/mahasiswa?id=${encodeURIComponent(mhs.id)}`);
            modalHapus.hide();
            if (ok) {
                await muatData();
                Notifikasi.tampil(`Data ${mhs.nama} berhasil dihapus.`, 'success');
            } else if (status === 403) {
                tanganiDitolak(hasil && hasil.message);
            } else if (status === 404) {
                Notifikasi.tampil('Data sudah tidak ada (mungkin dihapus pengguna lain). Tabel diperbarui.', 'warning');
                await muatData();
            } else {
                Notifikasi.tampil((hasil && hasil.message) || `Gagal menghapus (HTTP ${status})`, 'danger');
            }
        } catch (error) {
            if (error instanceof SesiBerakhir) return;
            console.error('Gagal menghapus:', error);
            modalHapus.hide();
            Notifikasi.tampil('Tidak dapat menghubungi server. Data belum dihapus.', 'danger');
        } finally {
            elKonfirmasiHapus.disabled = false;
            sedangHapus = null;
        }
    });

    // ---------- Logout ----------
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
    Notifikasi.tampilkanTertunda(); // pesan dari form_tambah / form_edit (mis. "Data berhasil ditambahkan")
    muatData();
}
