/**
 * Manganese Telemetry System
 * Client-side probe for user interaction tracking
 */
const CV_Telemetry = {
    selectionTimer: null,
    lastLoggedSelection: '',

    init() {
        console.log("🛠️ Manganese Probe: Active");
        this.trackCopy();
        this.trackDownloads();
        this.trackTextSelection();
        this.trackHeartbeat();
    },

    /**
     * Envoi des données via l'API Manganese
     */
    send(type, el_id = '', data = '') {
        const payload = JSON.stringify({ type, el_id, data });
        // Utilisation de l'API Beacon pour un envoi asynchrone et fiable
        navigator.sendBeacon('/api/telemetry', payload);
    },

    /**
     * Capture du copier-coller
     */
    trackCopy() {
        document.addEventListener('copy', () => {
            const selectedText = window.getSelection().toString().substring(0, 100);
            this.send('copy_text', 'clipboard', `Saisi: ${selectedText}...`);
        });
    },

    /**
     * Capture des clics sur les téléchargements
     */
    trackDownloads() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('.download-link');
            if (link) {
                const fileName = link.dataset.filename || link.href.split('/').pop();
                this.send('download', 'file_access', fileName);
            }
        });
    },

    /**
     * Capture de la sélection (Lecture active) avec délai de 15s
     */
    trackTextSelection() {
        document.addEventListener('mouseup', () => {
            const selection = window.getSelection();
            const currentText = selection.toString().trim();

            if (currentText.length > 5 && currentText !== this.lastLoggedSelection) {
                if (this.selectionTimer) clearTimeout(this.selectionTimer);

                // On cherche la section parente la plus proche
                // .anchorNode est le point de départ de la sélection
                const parentSection = selection.anchorNode?.parentElement?.closest('section');
                const sectionId = parentSection ? parentSection.id : 'unknown_section';

                this.selectionTimer = setTimeout(() => {
                    // On inverse : type = reading_focus, el_id = l'id de la section
                    this.send('reading_focus', sectionId, currentText);
                    this.lastLoggedSelection = currentText;
                    console.log(`📡 Focus enregistré dans la section [${sectionId}]`);
                }, 15000); // Tes 15 secondes validées
            }
        });
    },

    /**
     * Heartbeat pour maintenir la durée de session
     */
    trackHeartbeat() {
        setInterval(() => {
            this.send('heartbeat', 'keep_alive');
        }, 60000); // Toutes les minutes
    }
};

document.addEventListener('DOMContentLoaded', () => CV_Telemetry.init());