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
            const currentSelection = window.getSelection().toString().trim();

            // Règle : > 5 chars ET différent du dernier log
            if (currentSelection.length > 5 && currentSelection !== this.lastLoggedSelection) {
                
                // On réinitialise le timer à chaque mouvement (Debounce)
                if (this.selectionTimer) clearTimeout(this.selectionTimer);

                // On attend 15 secondes de calme avant d'envoyer
                this.selectionTimer = setTimeout(() => {
                    this.send('select_text', 'reading_focus', currentSelection);
                    this.lastLoggedSelection = currentSelection;
                    console.log("📡 Focus de lecture stabilisé et envoyé.");
                }, 15000); 
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