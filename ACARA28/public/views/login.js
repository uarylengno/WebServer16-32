// Acara 25 (Langkah 3): login memakai POST /login, lalu menyimpan token dan role di localStorage.
// Memerlukan common.js (BASE_URL, Sesi).

const elForm = document.getElementById('form-login');
const elUsername = document.getElementById('username');
const elPassword = document.getElementById('password');
const elTombol = document.getElementById('tombol-masuk');
const elPesan = document.getElementById('pesan');

function tampilkanPesan(teks, jenis) {
    elPesan.textContent = teks;
    elPesan.className = 'pesan pesan-' + jenis;
    elPesan.hidden = false;
}

// Pesan dari halaman lain lewat ?pesan=... Hanya kata kunci yang dikenal yang diterima, teks lain diabaikan.
const PESAN_DIKENAL = {
    sesi: ['Sesi Anda berakhir. Silakan masuk lagi.', 'peringatan'],
    keluar: ['Anda sudah keluar.', 'ok'],
};
const kodePesan = new URLSearchParams(window.location.search).get('pesan');
if (Object.prototype.hasOwnProperty.call(PESAN_DIKENAL, kodePesan)) {
    tampilkanPesan(...PESAN_DIKENAL[kodePesan]);
}

// Sudah masuk dan token belum kedaluwarsa: langsung ke dashboard.
if (Sesi.valid() && !kodePesan) {
    window.location.replace('dashboard.html');
}

elForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const username = elUsername.value.trim();
    const password = elPassword.value;
    if (username === '' || password === '') {
        tampilkanPesan('Username dan password wajib diisi.', 'error');
        return;
    }

    elTombol.disabled = true;
    tampilkanPesan('Memeriksa data...', 'info');

    try {
        const res = await fetch(`${BASE_URL}/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password }),
        });

        let hasil = null;
        try {
            hasil = await res.json();
        } catch (err) {
            hasil = null;
        }

        if (res.ok && hasil && hasil.success && hasil.data && hasil.data.token) {
            Sesi.simpan(hasil.data.token, hasil.data.role, username);
            window.location.href = 'dashboard.html';
            return;
        }

        // Pesan dari server (mis. "Username atau password salah"), ditampilkan sebagai teks biasa.
        const pesan = hasil && hasil.message ? hasil.message : `Login gagal (HTTP ${res.status}).`;
        tampilkanPesan(pesan, 'error');
        elPassword.value = '';
        elPassword.focus();
    } catch (error) {
        console.error('Gagal menghubungi server:', error);
        tampilkanPesan('Tidak dapat menghubungi server. Periksa apakah server berjalan.', 'error');
    } finally {
        elTombol.disabled = false;
    }
});
