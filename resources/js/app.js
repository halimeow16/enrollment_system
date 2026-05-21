import '../css/app.css';
import '@fontsource/plus-jakarta-sans/400.css';
import '@fontsource/plus-jakarta-sans/500.css';
import '@fontsource/plus-jakarta-sans/600.css';
import '@fontsource/plus-jakarta-sans/700.css';
import '@fontsource/plus-jakarta-sans/800.css';
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fortawesome/fontawesome-free/css/all.min.css';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import JSZip from 'jszip';
import * as lucide from 'lucide';
import * as pdfjsLib from 'pdfjs-dist';
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

window.Alpine = Alpine;
window.Chart = Chart;
window.JSZip = JSZip;
window.lucide = {
    ...lucide,
    createIcons(options = {}) {
        return lucide.createIcons({
            icons: lucide.icons,
            ...options,
        });
    },
};
window.pdfjsLib = pdfjsLib;
window.pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

import './enrollment.js';
import './dashboard.js';

Alpine.start();
