// Acara 25: kode bersama untuk halaman login dan dashboard (alamat API, penyimpanan sesi, pemanggil API).
// Acara 26: panggilApi() juga dapat mengirim FormData (multipart) untuk upload foto.
// Acara 27: Notifikasi (toast Bootstrap) dan Validasi (pemeriksaan isian di sisi View) dipakai dashboard dan form.

// Alamat API diturunkan dari alamat halaman ini, sehingga tetap benar di folder mana pun proyek ditaruh:
//   http://localhost/ACARA16-25/ACARA27/public/views/login.html
//   -> http://localhost/ACARA16-25/ACARA27/public
// Jika halaman dibuka langsung dari berkas (file://), dipakai alamat bawaan di bawah.
const BASE_URL_BAWAAN = 'http://localhost/ACARA16-25/ACARA27/public';
const BASE_URL = window.location.protocol.startsWith('http')
    ? window.location.origin + window.location.pathname.replace(/\/views\/[^/]*$/, '')
    : BASE_URL_BAWAAN;

/**
 * Sesi login di browser (localStorage: token, role, username).
 *
 * PENTING: semua yang ada di localStorage dapat diubah pengguna lewat Developer Tools. Karena itu role di sini
 * HANYA dipakai untuk mengatur tampilan (menyembunyikan tombol). Hak akses sebenarnya diputuskan server:
 * Auth::requireAdmin() membaca role dari token yang tanda tangannya diverifikasi.
 */
const Sesi = {
    ambil() {
        try {
            return {
                token: localStorage.getItem('token'),
                role: localStorage.getItem('role'),
                username: localStorage.getItem('username'),
            };
        } catch (e) {
            return { token: null, role: null, username: null }; // localStorage diblokir (mis. mode privat)
        }
    },

    simpan(token, role, username) {
        try {
            localStorage.setItem('token', token);
            localStorage.setItem('role', role);
            localStorage.setItem('username', username);
        } catch (e) { /* diabaikan: sesi tidak tersimpan */ }
    },

    simpanRole(role) {
        try { localStorage.setItem('role', role); } catch (e) { /* diabaikan */ }
    },

    hapus() {
        try {
            localStorage.removeItem('token');
            localStorage.removeItem('role');
            localStorage.removeItem('username');
        } catch (e) { /* diabaikan */ }
    },

    /** Membaca payload JWT (bagian tengah) tanpa memverifikasi tanda tangan: hanya untuk membaca masa berlaku. */
    payload(token) {
        try {
            const bagian = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
            return JSON.parse(atob(bagian + '='.repeat((4 - (bagian.length % 4)) % 4)));
        } catch (e) {
            return null;
        }
    },

    /** Sisa masa berlaku token dalam milidetik (0 jika sudah lewat atau token tidak terbaca). */
    sisaWaktu(token) {
        const p = token ? Sesi.payload(token) : null;
        if (!p || typeof p.exp !== 'number') return 0;
        return Math.max(0, p.exp * 1000 - Date.now());
    },

    /** Ada token dan belum kedaluwarsa menurut jam browser. Server tetap yang memutuskan. */
    valid() {
        const { token } = Sesi.ambil();
        return Sesi.sisaWaktu(token) > 0;
    },
};

/** Dilempar setelah sesi dinyatakan berakhir dan halaman sedang diarahkan ke login. */
class SesiBerakhir extends Error {}

/** Alamat gambar foto mahasiswa (berkas statis di public/uploads/), atau null jika belum punya foto. */
function alamatFoto(nama) {
    return nama ? BASE_URL + '/uploads/' + encodeURIComponent(nama) : null;
}

function keLogin(pesan) {
    window.location.replace('login.html' + (pesan ? '?pesan=' + encodeURIComponent(pesan) : ''));
}

/**
 * Memanggil API dan mengembalikan { status, ok, hasil }.
 * fetch() hanya gagal bila jaringan putus; status 4xx/5xx tetap dikembalikan agar pemanggil bisa menampilkan pesannya.
 * 401 pada request ber-token berarti token ditolak server (kedaluwarsa/rusak): sesi dihapus dan pengguna diarahkan ke login.
 */
async function panggilApi(method, path, body) {
    const { token } = Sesi.ambil();
    const headers = {};
    const adaForm = typeof FormData !== 'undefined' && body instanceof FormData;
    // FormData: JANGAN mengisi Content-Type sendiri. Browser yang mengisinya lengkap dengan boundary
    // ("multipart/form-data; boundary=..."); jika diisi manual, boundary hilang dan server tidak dapat membaca isinya.
    if (body !== undefined && !adaForm) headers['Content-Type'] = 'application/json';
    if (token) headers['Authorization'] = 'Bearer ' + token;

    const response = await fetch(BASE_URL + path, {
        method,
        headers,
        body: body === undefined ? undefined : (adaForm ? body : JSON.stringify(body)),
    });

    let hasil = null;
    try {
        hasil = await response.json();
    } catch (e) {
        hasil = null; // body bukan JSON (mis. halaman error server)
    }

    if (response.status === 401 && token) {
        Sesi.hapus();
        keLogin('sesi');
        throw new SesiBerakhir('Sesi berakhir');
    }

    return { status: response.status, ok: response.ok, hasil };
}

// ============================================================================
// Acara 27 (tugas mandiri 1): notifikasi visual untuk setiap operasi Create/Update/Delete.
// Memakai komponen Toast Bootstrap (vendor/bootstrap/bootstrap.bundle.min.js harus dimuat lebih dulu).
// Teks pesan dipasang dengan textContent, bukan innerHTML, sehingga pesan dari server tidak dieksekusi sebagai HTML.
// ============================================================================
const Notifikasi = {
    KUNCI: 'flash_notifikasi',
    JENIS: ['success', 'danger', 'warning', 'info'],

    /** Menampilkan toast. jenis: success (berhasil), danger (gagal), warning (peringatan), info. */
    tampil(pesan, jenis = 'success') {
        if (!Notifikasi.JENIS.includes(jenis)) jenis = 'info';
        let wadah = document.getElementById('wadah-toast');
        if (!wadah) {
            wadah = document.createElement('div');
            wadah.id = 'wadah-toast';
            wadah.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            wadah.style.zIndex = '1100';
            document.body.appendChild(wadah);
        }

        const el = document.createElement('div');
        el.className = 'toast align-items-center border-0 text-bg-' + jenis;
        el.setAttribute('role', jenis === 'danger' ? 'alert' : 'status');
        el.setAttribute('aria-live', jenis === 'danger' ? 'assertive' : 'polite');
        el.setAttribute('aria-atomic', 'true');

        const baris = document.createElement('div');
        baris.className = 'd-flex';
        const isi = document.createElement('div');
        isi.className = 'toast-body';
        isi.textContent = pesan;
        const tutup = document.createElement('button');
        tutup.type = 'button';
        tutup.className = 'btn-close me-2 m-auto' + (jenis === 'success' || jenis === 'danger' ? ' btn-close-white' : '');
        tutup.setAttribute('data-bs-dismiss', 'toast');
        tutup.setAttribute('aria-label', 'Tutup');
        baris.append(isi, tutup);
        el.appendChild(baris);
        wadah.appendChild(el);

        el.addEventListener('hidden.bs.toast', () => el.remove());
        new bootstrap.Toast(el, { delay: jenis === 'danger' ? 7000 : 4000 }).show();
    },

    /** Menyimpan pesan agar tampil di halaman berikutnya (mis. setelah form menyimpan lalu kembali ke dashboard). */
    simpanUntukBerikutnya(pesan, jenis) {
        try { sessionStorage.setItem(Notifikasi.KUNCI, JSON.stringify({ pesan, jenis })); } catch (e) { /* diabaikan */ }
    },

    /** Menampilkan (sekali saja) pesan yang disimpan halaman sebelumnya. */
    tampilkanTertunda() {
        try {
            const mentah = sessionStorage.getItem(Notifikasi.KUNCI);
            if (!mentah) return;
            sessionStorage.removeItem(Notifikasi.KUNCI);
            const { pesan, jenis } = JSON.parse(mentah);
            if (typeof pesan === 'string' && Notifikasi.JENIS.includes(jenis)) Notifikasi.tampil(pesan, jenis);
        } catch (e) { /* diabaikan */ }
    },
};

// ============================================================================
// Acara 27 (tugas mandiri 2): validasi sisi View. Lapisan tambahan SEBELUM data dikirim ke Controller; aturannya
// sama dengan MahasiswaController::validated() dan validasiFoto(). Server tetap penentu: pemeriksaan di browser
// bisa dilewati siapa pun (mis. lewat Postman), jadi tidak menggantikan validasi Model/Controller.
// ============================================================================
const Validasi = {
    NIM_PANJANG: 9,
    NAMA_MAKS: 100,
    PRODI_MAKS: 50,
    FOTO_MAKS_BYTE: 2 * 1024 * 1024,

    /** Mengembalikan objek { bidang: pesan }. Objek kosong berarti semua isian lolos. */
    mahasiswa({ nim, nama, prodi, foto }) {
        const galat = {};
        nim = String(nim ?? '').trim();
        nama = String(nama ?? '').trim();
        prodi = String(prodi ?? '').trim();

        if (nim === '') galat.nim = 'NIM wajib diisi';
        else if (nim.length !== Validasi.NIM_PANJANG) galat.nim = `NIM harus ${Validasi.NIM_PANJANG} karakter (sekarang ${nim.length})`;

        if (nama === '') galat.nama = 'Nama wajib diisi';
        else if (Array.from(nama).length > Validasi.NAMA_MAKS) galat.nama = `Nama maksimal ${Validasi.NAMA_MAKS} karakter`;

        if (prodi === '') galat.prodi = 'Program studi wajib diisi';
        else if (Array.from(prodi).length > Validasi.PRODI_MAKS) galat.prodi = `Prodi maksimal ${Validasi.PRODI_MAKS} karakter`;

        if (foto) {
            const g = Validasi.foto(foto);
            if (g) galat.foto = g;
        }
        return galat;
    },

    /** Foto bersifat opsional. Mengembalikan pesan galat, atau '' bila berkas boleh dikirim. */
    foto(berkas) {
        if (!['image/jpeg', 'image/png'].includes(berkas.type)) return 'Berkas harus berupa gambar JPG/PNG yang valid';
        if (berkas.size > Validasi.FOTO_MAKS_BYTE) return 'Ukuran berkas maksimal 2MB';
        return '';
    },
};

/** Menyesuaikan tema Bootstrap (terang/gelap) dengan pengaturan sistem, agar sama dengan halaman login. */
function terapkanTema() {
    const gelap = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute('data-bs-theme', gelap ? 'dark' : 'light');
}
terapkanTema();
