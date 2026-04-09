/**
 * Manganese Telemetry System
 * Client-side probe for user interaction tracking
 */
const CV_Telemetry = {
    selectionTimer: null,
    lastLoggedSelection: '',
    // On garde en mémoire les paliers déjà envoyés pour cette session
    loggedScrollPaliers: [],
    // On récupère l'ID scellé dans le HTML
    telemetryId: document.body.dataset.telemetryId,    

    init() {
        if (!this.telemetryId) return; // Sécurité
        console.log("🛠️ Manganese Probe: Depth tracking active");
        this.trackCopy();
        //this.trackDownloads();
        this.trackTextSelection();
        this.trackScrollDepth();
        this.trackHeartbeat();
    },

    /**
     * Envoi des données via l'API Manganese
     */
    send(type, el_id = '', data = '') {
        const payload = JSON.stringify({sid: this.telemetryId, type, el_id, data });
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
    /**trackDownloads() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('.download-link');
            if (link) {
                const fileName = link.dataset.filename || link.href.split('/').pop();
                this.send('download', 'file_access', fileName);
            }
        });
    },*/

    /**
     * Capture de la sélection (Lecture active) avec délai de 2s
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
                }, 2000); // Tes 2 secondes validées
            }
        });
    },

    /**
     * Suivi de la profondeur de lecture (Scroll Depth)
     */
    trackScrollDepth() {
        window.addEventListener('scroll', () => {
            // Calcul du pourcentage de scroll
            const windowHeight = window.innerHeight;
            const fullHeight = document.documentElement.scrollHeight;
            const scrolled = window.scrollY;
            
            // Formule : (Position actuelle + Taille écran) / Taille totale
            const percentage = Math.round(((scrolled + windowHeight) / fullHeight) * 100);

            // On définit les paliers que l'on veut capturer
            const paliers = [25, 50, 75, 100];

            paliers.forEach(palier => {
                // Si on a dépassé un palier et qu'on ne l'a pas encore logué
                if (percentage >= palier && !this.loggedScrollPaliers.includes(palier)) {
                    this.send('scroll_depth', `${palier}%`, `L'utilisateur a atteint ${palier}% du CV`);
                    this.loggedScrollPaliers.push(palier);
                }
            });
        }, { passive: true }); // Performance : ne bloque pas le scroll fluide
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