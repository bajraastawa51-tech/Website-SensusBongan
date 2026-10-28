document.addEventListener('DOMContentLoaded', () => {
    // Confirmation dialogs
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    // Mobile sidebar toggle with backdrop & close button
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const backdrop = document.getElementById('sidebarBackdrop');

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        if (backdrop) backdrop.classList.add('show');
        document.body.classList.add('sidebar-opened');
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('show');
        document.body.classList.remove('sidebar-opened');
    }

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    // Auto-close sidebar on mobile when tapping nav links
    if (sidebar) {
        sidebar.querySelectorAll('.nav-link').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) closeSidebar();
            });
        });
    }

    // Resident quick auto-fill helper
    const resSelect = document.getElementById('residentSelect');
    if (resSelect) {
        resSelect.addEventListener('change', () => {
            const opt = resSelect.selectedOptions[0];
            if (opt && opt.dataset.nik) {
                const nikInput = document.querySelector('input[name="nik"]');
                const nameInput = document.querySelector('input[name="person_name"]');
                if (nikInput && !nikInput.value) nikInput.value = opt.dataset.nik;
                if (nameInput && !nameInput.value) nameInput.value = opt.dataset.name;
            }
        });
    }

    // Input Group focus micro-interaction
    document.querySelectorAll('.input-group .form-control').forEach((input) => {
        input.addEventListener('focus', () => {
            const group = input.closest('.input-group');
            if (group) group.classList.add('is-focused');
        });
        input.addEventListener('blur', () => {
            const group = input.closest('.input-group');
            if (group) group.classList.remove('is-focused');
        });
    });

    // Button Material Ripple Effect
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-primary, .auth-submit, .btn-success, .btn-outline-primary');
        if (!btn) return;
        
        const rect = btn.getBoundingClientRect();
        const circle = document.createElement('span');
        const diameter = Math.max(rect.width, rect.height);
        const radius = diameter / 2;
        
        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${e.clientX - rect.left - radius}px`;
        circle.style.top = `${e.clientY - rect.top - radius}px`;
        circle.classList.add('btn-ripple');
        
        const existing = btn.querySelector('.btn-ripple');
        if (existing) existing.remove();
        
        btn.appendChild(circle);
        setTimeout(() => circle.remove(), 600);
    });

    // Identity Overlay Slideshow (Login & Register)
    const slides = document.querySelectorAll('.identity-slide');
    const dots = document.querySelectorAll('.slide-dot');
    const caption = document.getElementById('slideCaption');
    if (slides.length > 1) {
        const isEn = document.documentElement.lang === 'en';
        const captions = [
            isEn ? 'Bongan Agrarian Landscape' : 'Lanskap Agraris Desa Bongan',
            isEn ? 'Tradition of Mesuryak Bongan' : 'Tradisi Mesuryak Desa Bongan',
            isEn ? 'Bongan Village Office' : 'Kantor Perbekel Desa Bongan'
        ];

        let current = 0;
        let timer = null;

        function goToSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove('active', 'prev');
                if (i === index) {
                    slide.classList.add('active');
                } else if (i === current) {
                    slide.classList.add('prev');
                }
            });

            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });

            if (caption && captions[index]) {
                caption.style.opacity = '0';
                setTimeout(() => {
                    caption.textContent = captions[index];
                    caption.style.opacity = '1';
                }, 260);
            }

            current = index;
        }

        window.switchIdentitySlide = function(index) {
            goToSlide(index);
            restartTimer();
        };

        function nextSlide() {
            goToSlide((current + 1) % slides.length);
        }

        function startTimer() {
            timer = setInterval(nextSlide, 5000);
        }

        function restartTimer() {
            if (timer) clearInterval(timer);
            startTimer();
        }

        startTimer();
    }

    // Dynamic CountUp Animation for Stat Numbers
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const statCounters = document.querySelectorAll('.stat-card strong, .event-tile strong, .report-metric strong');
        statCounters.forEach((counter) => {
            const originalText = counter.textContent.trim();
            const numericValue = parseInt(originalText.replace(/[^0-9]/g, ''), 10);
            if (isNaN(numericValue) || numericValue === 0) return;

            const duration = 1200; // ms
            const startTime = performance.now();

            function step(now) {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // easeOutExpo easing: 1 - 2^(-10 * progress)
                const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                const current = Math.floor(eased * numericValue);
                counter.textContent = current.toLocaleString();

                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    counter.textContent = numericValue.toLocaleString();
                }
            }
            requestAnimationFrame(step);
        });
    }

    // Chart.js Configuration with Smooth Animation
    const data = window.sensusCharts;
    if (window.Chart) {
        Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, -apple-system, sans-serif";
        Chart.defaults.color = '#64748b';
        Chart.defaults.animation = {
            duration: 1200,
            easing: 'easeOutQuart'
        };
    }

    const eventsCanvas = document.getElementById('eventsChart');
    if (data && eventsCanvas && window.Chart) {
        new Chart(eventsCanvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    { 
                        label: data.labelBirth || 'Kelahiran', 
                        data: data.births, 
                        backgroundColor: '#10b981', 
                        borderRadius: 6, 
                        maxBarThickness: 28 
                    },
                    { 
                        label: data.labelDeath || 'Kematian', 
                        data: data.deaths, 
                        backgroundColor: '#f43f5e', 
                        borderRadius: 6, 
                        maxBarThickness: 28 
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeOutQuart'
                },
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            usePointStyle: true, 
                            boxWidth: 8, 
                            padding: 20,
                            font: { weight: '600', size: 12 }
                        } 
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { weight: 'bold' }
                    }
                },
                scales: { 
                    x: { 
                        grid: { display: false },
                        ticks: { font: { weight: '500', size: 11.5 } }
                    }, 
                    y: { 
                        beginAtZero: true, 
                        ticks: { precision: 0, font: { weight: '500', size: 11 } }, 
                        grid: { color: '#f1f5f9' } 
                    } 
                },
            },
        });
    }

    const genderCanvas = document.getElementById('genderChart');
    if (data && genderCanvas && window.Chart) {
        new Chart(genderCanvas, {
            type: 'doughnut',
            data: {
                labels: [data.labelMale || 'Laki-laki', data.labelFemale || 'Perempuan'],
                datasets: [{ 
                    data: [data.male, data.female], 
                    backgroundColor: ['#0284c7', '#f43f5e'], 
                    borderWidth: 0, 
                    hoverOffset: 8 
                }],
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                cutout: '74%', 
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1400,
                    easing: 'easeOutQuart'
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 8,
                    }
                } 
            },
        });
    }

    // Fitur Ramah Lansia & Aksesibilitas (Senior Mode & Voice Narrator)
    function applySeniorMode(isActive) {
        if (isActive) {
            document.body.classList.add('senior-mode');
            document.documentElement.classList.add('senior-mode');
        } else {
            document.body.classList.remove('senior-mode');
            document.documentElement.classList.remove('senior-mode');
        }

        const isEn = document.documentElement.lang === 'en';
        const modeTexts = document.querySelectorAll('#seniorModeText');
        modeTexts.forEach(el => {
            if (isActive) {
                el.textContent = isEn ? 'Senior Active' : 'Lansia Aktif';
            } else {
                el.textContent = isEn ? 'Senior Mode' : 'Mode Lansia';
            }
        });

        const topbarTexts = document.querySelectorAll('#topbarSeniorText');
        topbarTexts.forEach(el => {
            if (isActive) {
                el.textContent = isEn ? 'Senior Active' : 'Mode Lansia Aktif';
            } else {
                el.textContent = isEn ? 'Senior Mode' : 'Mode Ramah Lansia';
            }
        });

        const btns = document.querySelectorAll('#toggleSeniorModeBtn, #topbarSeniorBtn');
        btns.forEach(b => {
            b.classList.toggle('active', isActive);
            if (b.id === 'topbarSeniorBtn') {
                if (isActive) {
                    b.classList.remove('btn-outline-success');
                    b.classList.add('btn-success');
                } else {
                    b.classList.remove('btn-success');
                    b.classList.add('btn-outline-success');
                }
            }
        });
    }

    const savedSeniorMode = localStorage.getItem('bongan_senior_mode') === 'true';
    if (savedSeniorMode) {
        applySeniorMode(true);
    }

    window.toggleSeniorMode = function() {
        const currentlyActive = document.body.classList.contains('senior-mode');
        const nextState = !currentlyActive;
        localStorage.setItem('bongan_senior_mode', nextState ? 'true' : 'false');
        applySeniorMode(nextState);
    };

    // Senior Dock Minimize / Expand
    const dock = document.getElementById('seniorAssistDock');
    const isDockMinimized = localStorage.getItem('bongan_senior_dock_minimized') === 'true';
    if (dock && isDockMinimized) {
        dock.classList.add('minimized');
    }

    window.toggleSeniorDock = function(shouldMinimize) {
        if (!dock) return;
        if (typeof shouldMinimize === 'boolean') {
            dock.classList.toggle('minimized', shouldMinimize);
            localStorage.setItem('bongan_senior_dock_minimized', shouldMinimize ? 'true' : 'false');
        } else {
            const next = !dock.classList.contains('minimized');
            dock.classList.toggle('minimized', next);
            localStorage.setItem('bongan_senior_dock_minimized', next ? 'true' : 'false');
        }
    };

    // Text-to-Speech Page Guide Narrator (Web Speech API)
    window.speakPageGuide = function() {
        const isEn = document.documentElement.lang === 'en';
        if (!('speechSynthesis' in window)) {
            alert(isEn ? 'Your browser does not support audio narration.' : 'Perangkat Anda tidak mendukung fitur audio narator.');
            return;
        }

        if (window.speechSynthesis.speaking) {
            window.speechSynthesis.cancel();
            updateSpeechUI(false);
            return;
        }

        let message = '';

        if (window.location.search.includes('page=login') || document.querySelector('#loginForm')) {
            message = isEn
                ? 'Welcome to the Bongan Village Digital Census Portal. This is the login page. Please enter your username in the first box, and your password in the second box, then click the green Sign In button. If you need assistance, click the Village Officer Help button.'
                : 'Selamat datang di Portal Sensus Penduduk Desa Bongan. Ini adalah halaman masuk. Masukkan nama pengguna pada kotak pertama, dan kata sandi pada kotak kedua, kemudian tekan tombol hijau Masuk ke Sistem. Jika butuh bantuan, tekan tombol Bantuan Petugas di bawah.';
        } else if (window.location.search.includes('page=register') || document.querySelector('input[name="action"][value="register_resident"]')) {
            message = isEn
                ? 'Resident registration page for Bongan Village. Please enter your resident code, family card number, new username, and password. You can also click Fill Sample Code for quick demonstration.'
                : 'Halaman pendaftaran warga Desa Bongan. Masukkan nomor penduduk, nomor kartu keluarga, serta buat nama pengguna dan kata sandi baru. Anda juga dapat menekan tombol Isi Kode Contoh untuk mencoba pendaftaran otomatis.';
        } else if (window.location.search.includes('page=dashboard') || !window.location.search) {
            message = isEn
                ? 'Bongan Village population census dashboard. This page shows total residents, family counts, and vital event reports. To add data or view reports, click the green action buttons.'
                : 'Halaman utama sensus Desa Bongan. Halaman ini menampilkan jumlah penduduk, jumlah keluarga, dan laporan peristiwa warga. Untuk menambah data atau melihat laporan, silakan gunakan tombol aksi yang tersedia.';
        } else if (window.location.search.includes('page=residents')) {
            message = isEn
                ? 'Resident data directory page. Displays list of registered citizens in Bongan Village. Use the search field to find a person by name or NIK.'
                : 'Halaman data kependudukan Desa Bongan. Menampilkan daftar warga yang terdaftar. Gunakan kolom cari untuk menemukan warga berdasarkan nama atau nomor KTP.';
        } else if (window.location.search.includes('page=households')) {
            message = isEn
                ? 'Family Card directory page. Displays registered households and family heads in Bongan Village.'
                : 'Halaman data kartu keluarga Desa Bongan. Menampilkan daftar nomor KK dan nama kepala keluarga resmi di Desa Bongan.';
        } else if (window.location.search.includes('page=family')) {
            message = isEn
                ? 'My family data page. Shows registered family members linked to your resident account.'
                : 'Halaman data keluarga. Menampilkan daftar anggota keluarga resmi yang terhubung dengan akun Anda.';
        } else if (window.location.search.includes('page=events')) {
            message = isEn
                ? 'Vital events reporting page. Submit birth, death, or migration reports, and monitor application review status.'
                : 'Halaman laporan peristiwa kependudukan. Ajukan laporan peristiwa atau pantau status verifikasi laporan Anda.';
        } else if (window.location.search.includes('page=reports')) {
            message = isEn
                ? 'Monthly population reports page. View, compile, print, or download official village census data.'
                : 'Halaman laporan kependudukan bulanan. Tinjau, susun, cetak, atau unduh berkas rekapitulasi desa.';
        } else if (window.location.search.includes('page=recap')) {
            message = isEn
                ? 'Census recap page. Demographic statistics, age group breakdowns, and verified vital event accumulations.'
                : 'Halaman rekapitulasi sensus. Menampilkan statistik demografi, kelompok usia penduduk, dan akumulasi peristiwa terverifikasi.';
        } else if (window.location.search.includes('page=profile')) {
            message = isEn
                ? 'User profile page. Review your account information and optionally update your password.'
                : 'Halaman profil akun. Periksa rincian data Anda dan perbarui kata sandi akun jika diinginkan.';
        } else if (window.location.search.includes('page=users')) {
            message = isEn
                ? 'User management page. Approve resident account registration requests and manage system accounts.'
                : 'Halaman manajemen pengguna. Setujui permohonan pendaftaran warga dan kelola akun pengguna sistem.';
        } else {
            message = isEn
                ? 'Bongan Village Census System. Population information portal. If you need assistance, please click the Village Officer Help button.'
                : 'Sistem Informasi Sensus Penduduk Desa Bongan. Jika Anda membutuhkan bantuan langsung dari petugas desa, silakan tekan tombol Bantuan Petugas.';
        }

        const utterance = new SpeechSynthesisUtterance(message);
        utterance.lang = isEn ? 'en-US' : 'id-ID';
        utterance.rate = 0.88; // Pace yang tenang dan sangat jelas bagi lansia
        utterance.pitch = 1.0;

        utterance.onstart = () => updateSpeechUI(true);
        utterance.onend = () => updateSpeechUI(false);
        utterance.onerror = () => updateSpeechUI(false);

        window.speechSynthesis.speak(utterance);
    };

    function updateSpeechUI(isSpeaking) {
        const isEn = document.documentElement.lang === 'en';
        const speakBtns = document.querySelectorAll('.senior-btn-speech');
        const speakTexts = document.querySelectorAll('#speakText');
        const speakIcons = document.querySelectorAll('#speakIcon');

        speakBtns.forEach(btn => btn.classList.toggle('speaking', isSpeaking));
        speakIcons.forEach(icon => {
            if (isSpeaking) {
                icon.className = 'bi bi-stop-circle-fill text-warning';
            } else {
                icon.className = 'bi bi-volume-up-fill';
            }
        });
        speakTexts.forEach(txt => {
            if (isSpeaking) {
                txt.textContent = isEn ? 'Stop' : 'Berhenti';
            } else {
                txt.textContent = isEn ? 'Listen' : 'Bacakan';
            }
        });
    }
});
