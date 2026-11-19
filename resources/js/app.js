// resources/js/app.js
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

// Palet seri grafik per cabang (konsisten di dashboard & laporan)
window.chartColors = ['#0f766e', '#d97706', '#4f46e5', '#db2777', '#0284c7'];

/** Hitung mundur batas bayar (W-05). Memuat ulang halaman saat waktu habis. */
Alpine.data('countdown', (deadlineIso) => ({
    remaining: '',
    expired: false,
    init() {
        const deadline = new Date(deadlineIso).getTime();
        const tick = () => {
            const diff = Math.max(0, deadline - Date.now());
            const h = String(Math.floor(diff / 3.6e6)).padStart(2, '0');
            const m = String(Math.floor((diff % 3.6e6) / 6e4)).padStart(2, '0');
            const s = String(Math.floor((diff % 6e4) / 1e3)).padStart(2, '0');
            this.remaining = `${h} : ${m} : ${s}`;
            if (diff === 0 && !this.expired) {
                this.expired = true;
                setTimeout(() => window.location.reload(), 1500);
            }
        };
        tick();
        setInterval(tick, 1000);
    },
}));

/** Membuat grafik dari konfigurasi JSON yang disisipkan di elemen <canvas data-chart="...">. */
window.renderChart = (el, config) => new Chart(el, config);

/** Salin teks ke clipboard dengan umpan balik singkat (W-05b). */
Alpine.data('copyable', (text) => ({
    copied: false,
    copy() {
        navigator.clipboard?.writeText(text);
        this.copied = true;
        setTimeout(() => (this.copied = false), 1500);
    },
}));

Alpine.start();
