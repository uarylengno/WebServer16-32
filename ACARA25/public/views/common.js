// Acara 25: kode bersama untuk halaman login dan dashboard (alamat API, penyimpanan sesi, pemanggil API).

// Alamat API diturunkan dari alamat halaman ini, sehingga tetap benar di folder mana pun proyek ditaruh:
//   http://localhost/ACARA16-25/ACARA25/public/views/login.html
//   -> http://localhost/ACARA16-25/ACARA25/public
// Jika halaman dibuka langsung dari berkas (file://), dipakai alamat bawaan di bawah.
const BASE_URL_BAWAAN = 'http://localhost/ACARA16-25/ACARA25/public';
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
    if (body !== undefined) headers['Content-Type'] = 'application/json';
    if (token) headers['Authorization'] = 'Bearer ' + token;

    const response = await fetch(BASE_URL + path, {
        method,
        headers,
        body: body !== undefined ? JSON.stringify(body) : undefined,
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
