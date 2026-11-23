// Acara 27, Langkah 1: form tambah data (form_tambah.html).
// Mengirim FormData (nim, nama, prodi, foto) ke POST /mahasiswa dengan header Authorization: Bearer <token>.
// Logika bersama ada di form_umum.js. Memerlukan common.js dan Bootstrap.

if (FormMhs.jagaAdmin()) {
    document.getElementById('isi').hidden = false;
    FormMhs.pasangPratinjau();
    document.getElementById('f-nim').focus();

    document.getElementById('form-mhs').addEventListener('submit', (e) => {
        e.preventDefault();
        FormMhs.kirim('/mahasiswa', 'Data berhasil ditambahkan.');
    });
}
