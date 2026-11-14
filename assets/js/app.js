

document.addEventListener('DOMContentLoaded', () => {
    //Toggle Tema yang disimpan ke dalam Cookie selama 30 hari
    const btnTheme = document.getElementById('btnThemeToggle');
    if (btnTheme) {
        btnTheme.addEventListener('click', () => {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            document.cookie = `esport_theme=${next}; path=/; max-age=${86400 * 30}; SameSite=Lax`;
        });
    }

    //Toggle Show/Hide Kata Sandi pada Login & Registrasi
    document.querySelectorAll('.btn-toggle-pass').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Sembunyikan';
            } else {
                input.type = 'password';
                btn.textContent = 'Lihat';
            }
        });
    });

    //Indikator kekuatan kata sandi pada halaman Registrasi
    const regPassword = document.querySelector('#formRegistrasi #sandi');
    const strengthLabel = document.getElementById('passwordStrength');
    if (regPassword && strengthLabel) {
        regPassword.addEventListener('input', () => {
            const val = regPassword.value;
            if (!val) {
                strengthLabel.textContent = '';
                return;
            }
            let score = 0;
            if (val.length >= 6) score++;
            if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = ['Lemah (minimal 6 karakter)', 'Sedang', 'Kuat'];
            strengthLabel.textContent = 'Kekuatan sandi: ' + levels[Math.min(score, 2)];
        });
    }

    //Live Search & Filter Genre pada Tabel Daftar Turnamen
    const searchInput = document.getElementById('liveSearchInput');
    const genreSelect = document.getElementById('liveGenreSelect');
    const tableBody = document.querySelector('#tabelTurnamen tbody');
    const emptyLiveRow = document.getElementById('emptyLiveRow');

    function filterTableRows() {
        if (!tableBody) return;
        const keyword = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selectedGenre = genreSelect ? genreSelect.value : '';
        const rows = tableBody.querySelectorAll('tr[data-genre]');

        let visibleCount = 0;
        rows.forEach((row) => {
            const text = row.textContent.toLowerCase();
            const rowGenre = row.getAttribute('data-genre') || '';
            const matchText = keyword === '' || text.includes(keyword);
            const matchGenre = selectedGenre === '' || rowGenre === selectedGenre;

            if (matchText && matchGenre) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (emptyLiveRow && rows.length > 0) {
            emptyLiveRow.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterTableRows);
    if (genreSelect) genreSelect.addEventListener('change', filterTableRows);

    //Modal Konfirmasi Hapus Turnamen (CRUD Delete)
    const modalHapus = document.getElementById('modalHapus');
    const hapusNama = document.getElementById('hapusNamaTurnamen');
    const hapusId = document.getElementById('hapusIdTurnamen');
    const btnBatalHapus = document.getElementById('btnBatalHapus');

    document.querySelectorAll('.btn-hapus-trigger').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!modalHapus) return;
            hapusId.value = btn.getAttribute('data-id');
            hapusNama.textContent = btn.getAttribute('data-nama');
            modalHapus.style.display = 'flex';
        });
    });

    if (btnBatalHapus && modalHapus) {
        btnBatalHapus.addEventListener('click', () => {
            modalHapus.style.display = 'none';
        });
        modalHapus.addEventListener('click', (e) => {
            if (e.target === modalHapus) {
                modalHapus.style.display = 'none';
            }
        });
    }

    //Penghitung Karakter & Preview Format Rupiah pada Form Tambah/Edit
    const namaInput = document.getElementById('nama_turnamen');
    const charCount = document.getElementById('charCount');
    if (namaInput && charCount) {
        const updateCount = () => {
            charCount.textContent = `(${namaInput.value.length}/150)`;
        };
        updateCount();
        namaInput.addEventListener('input', updateCount);
    }

    const biayaInput = document.getElementById('biaya_pendaftaran');
    const biayaPreview = document.getElementById('biayaPreview');
    if (biayaInput && biayaPreview) {
        const updateBiaya = () => {
            const num = parseFloat(biayaInput.value);
            if (isNaN(num) || num < 0) {
                biayaPreview.textContent = '';
            } else if (num === 0) {
                biayaPreview.textContent = '→ Gratis';
            } else {
                biayaPreview.textContent = '→ Rp ' + num.toLocaleString('id-ID');
            }
        };
        updateBiaya();
        biayaInput.addEventListener('input', updateBiaya);
    }

    //Auto-dismiss Flash Alert setelah 4.5 detik
    const flashAlert = document.getElementById('flashAlert');
    if (flashAlert) {
        setTimeout(() => {
            flashAlert.style.transition = 'opacity 0.4s';
            flashAlert.style.opacity = '0';
            setTimeout(() => flashAlert.remove(), 400);
        }, 4500);
    }
});
