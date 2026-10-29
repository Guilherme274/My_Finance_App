// Emerald Sonic UX — Motor de Áudio (WAV PCM 16-bit + Web Audio API)
(function () {
    function createChordWavDataUri(notes, noteStepSec, durationSec, volume) {
        const sampleRate = 44100;
        const numSamples = Math.floor(sampleRate * durationSec);
        const buffer = new ArrayBuffer(44 + numSamples * 2);
        const view = new DataView(buffer);

        const writeStr = (offset, str) => {
            for (let i = 0; i < str.length; i++) {
                view.setUint8(offset + i, str.charCodeAt(i));
            }
        };

        writeStr(0, 'RIFF');
        view.setUint32(4, 36 + numSamples * 2, true);
        writeStr(8, 'WAVE');
        writeStr(12, 'fmt ');
        view.setUint32(16, 16, true);
        view.setUint16(20, 1, true); // PCM
        view.setUint16(22, 1, true); // Mono
        view.setUint32(24, sampleRate, true);
        view.setUint32(28, sampleRate * 2, true);
        view.setUint16(32, 2, true);
        view.setUint16(34, 16, true);
        writeStr(36, 'data');
        view.setUint32(40, numSamples * 2, true);

        for (let i = 0; i < numSamples; i++) {
            const t = i / sampleRate;
            let sample = 0;

            for (let n = 0; n < notes.length; n++) {
                const start = n * noteStepSec;
                if (t >= start) {
                    const localT = t - start;
                    const attack = Math.min(1, localT / 0.012);
                    const decay = Math.exp(-localT * 7.5);
                    const env = attack * decay;
                    const freq = notes[n];
                    sample += (Math.sin(2 * Math.PI * freq * localT) + 0.25 * Math.sin(4 * Math.PI * freq * localT)) * env;
                }
            }

            sample = Math.max(-1, Math.min(1, (sample / notes.length) * volume * 2.2));
            view.setInt16(44 + i * 2, sample * 32767, true);
        }

        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return 'data:audio/wav;base64,' + btoa(binary);
    }

    const saveWavUri = createChordWavDataUri([587.33, 739.99, 880.00, 1174.66], 0.055, 0.55, 0.75);
    const errorWavUri = createChordWavDataUri([240.00, 180.00], 0.11, 0.38, 0.65);

    window.emeraldAudio = {
        ctx: null,
        lastPlayedAt: 0,
        init() {
            if (!this.ctx) {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (AudioCtx) {
                    this.ctx = new AudioCtx();
                }
            }
            if (this.ctx && this.ctx.state === 'suspended') {
                this.ctx.resume().catch(() => {});
            }
            return this.ctx;
        },
        playSave() {
            const nowMs = Date.now();
            if (nowMs - this.lastPlayedAt < 800) {
                return;
            }
            this.lastPlayedAt = nowMs;

            try {
                const audio = new Audio(saveWavUri);
                audio.volume = 0.9;
                const playPromise = audio.play();
                if (playPromise && typeof playPromise.catch === 'function') {
                    playPromise.catch(() => this._playWebAudioSave());
                }
            } catch (e) {
                this._playWebAudioSave();
            }

            if (navigator.vibrate) {
                try { navigator.vibrate(20); } catch (e) {}
            }
        },
        _playWebAudioSave() {
            try {
                const ctx = this.init();
                if (!ctx) return;
                const trigger = () => {
                    const now = ctx.currentTime;
                    const notes = [587.33, 739.99, 880.00, 1174.66];
                    notes.forEach((freq, idx) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = idx === notes.length - 1 ? 'triangle' : 'sine';
                        osc.frequency.setValueAtTime(freq, now + idx * 0.055);
                        gain.gain.setValueAtTime(0.001, now + idx * 0.055);
                        gain.gain.exponentialRampToValueAtTime(0.32, now + idx * 0.055 + 0.015);
                        gain.gain.exponentialRampToValueAtTime(0.0001, now + idx * 0.055 + 0.35);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + idx * 0.055);
                        osc.stop(now + idx * 0.055 + 0.37);
                    });
                };
                if (ctx.state === 'suspended') {
                    ctx.resume().then(trigger).catch(() => {});
                } else {
                    trigger();
                }
            } catch (e) {}
        },
        playError() {
            const nowMs = Date.now();
            if (nowMs - this.lastPlayedAt < 500) {
                return;
            }
            this.lastPlayedAt = nowMs;

            try {
                const audio = new Audio(errorWavUri);
                audio.volume = 0.85;
                audio.play().catch(() => {});
            } catch (e) {}
        }
    };

    // Apenas desbloqueia silenciosamente o contexto de áudio no primeiro toque/clique
    document.addEventListener('pointerdown', () => {
        if (window.emeraldAudio) window.emeraldAudio.init();
    }, { passive: true });
})();
