/**
 * Manganese Telemetry System
 */
const CV_Telemetry = {
    appId: document.body.dataset.appId,
    
    init() {
        this.trackCopy();
        this.trackDownloads();
        this.send('session_start');
    },

    send(type, info = '') {
        const formData = new FormData();
        formData.append('app_id', this.appId);
        formData.append('type', type);
        formData.append('data', info);
        navigator.sendBeacon('/api/telemetry.php', formData);
    },

    trackCopy() {
        document.addEventListener('copy', () => {
            const selectedText = window.getSelection().toString().substring(0, 50);
            this.send('copy_paste', `Texte : ${selectedText}...`);
        });
    },

    trackDownloads() {
        document.querySelectorAll('.download-link').forEach(link => {
            link.addEventListener('click', () => {
                this.send('pdf_download', link.dataset.filename);
            });
        });
    }
};

document.addEventListener('DOMContentLoaded', () => CV_Telemetry.init());