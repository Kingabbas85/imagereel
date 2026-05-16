<div
    wire:ignore
    x-data="{
        ws: null,
        wsRegions: null,
        state: 'idle',
        playing: false,
        startTime: 0,
        endTime: 0,
        duration: 0,

        resolvePath(raw) {
            if (!raw) return null;
            // Filament/Livewire may return array, object, or string
            if (Array.isArray(raw))                        raw = raw[0];
            if (raw && typeof raw === 'object')            raw = raw.path || raw.name || raw.filename || null;
            if (typeof raw !== 'string' || !raw.trim())   return null;
            if (raw.startsWith('[object'))                 return null;
            return raw;
        },

        async init() {
            await this.$nextTick();

            let lastPath = null;

            const tryLoad = (raw) => {
                let path = this.resolvePath(raw);
                if (!path || path === lastPath) return;
                lastPath = path;
                this.loadWaveform(path);
            };

            // Edit page: try initial value from wire
            tryLoad(this.$wire.get('data.audio_path'));

            // Watch every Livewire server round-trip and inspect snapshot
            if (window.Livewire) {
                window.Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                    succeed(({ snapshot, effect }) => {
                        try {
                            const audioPath = snapshot?.data?.audio_path;
                            tryLoad(audioPath);
                        } catch(e) {}
                    });
                });
            }
        },

        async loadWaveform(path) {
            this.state   = 'loading';
            this.playing = false;

            if (this.ws) { this.ws.destroy(); this.ws = null; }

            // Wait for DOM to paint the container (must be visible before WaveSurfer init)
            await this.$nextTick();
            await this.$nextTick();

            const container = this.$refs.waveformEl;
            if (!container) { this.state = 'error'; return; }

            try {
                const { default: WaveSurfer }    = await import('https://cdn.jsdelivr.net/npm/wavesurfer.js@7/dist/wavesurfer.esm.js');
                const { default: RegionsPlugin } = await import('https://cdn.jsdelivr.net/npm/wavesurfer.js@7/dist/plugins/regions.esm.js');

                this.wsRegions = RegionsPlugin.create();

                this.ws = WaveSurfer.create({
                    container:     container,
                    waveColor:     '#7c3aed',
                    progressColor: '#5b21b6',
                    cursorColor:   '#a78bfa',
                    height:        80,
                    barWidth:      2,
                    barRadius:     3,
                    barGap:        1,
                    plugins:       [this.wsRegions],
                });

                await this.ws.load('/admin/preview/audio?path=' + encodeURIComponent(path));

                this.ws.on('ready', () => {
                    const dur      = this.ws.getDuration();
                    this.duration  = Math.round(dur * 10) / 10;
                    this.startTime = 0;
                    this.endTime   = this.duration;
                    this.state     = 'ready';

                    this.wsRegions.addRegion({
                        start: 0, end: dur,
                        color: 'rgba(124, 58, 237, 0.25)',
                        drag: true, resize: true,
                    });

                    this.$wire.set('data.audio_start', 0);
                    this.$wire.set('data.audio_end',   this.duration);
                });

                this.wsRegions.on('region-updated', (region) => {
                    this.startTime = Math.round(region.start * 10) / 10;
                    this.endTime   = Math.round(region.end   * 10) / 10;
                    this.$wire.set('data.audio_start', this.startTime);
                    this.$wire.set('data.audio_end',   this.endTime);
                });

                this.ws.on('finish', () => { this.playing = false; });
                this.ws.on('error',  (e) => { console.error('WS error', e); this.state = 'error'; });

            } catch(e) {
                console.error('WaveSurfer load error:', e);
                this.state = 'error';
            }
        },

        reset() {
            if (this.ws) { this.ws.destroy(); this.ws = null; }
            this.state = 'idle'; this.playing = false;
            this.startTime = 0; this.endTime = 0; this.duration = 0;
        },

        togglePlay()   { if (this.ws) { this.ws.playPause(); this.playing = !this.playing; } },
        playSelected() { if (this.ws) { this.ws.play(this.startTime, this.endTime); this.playing = true; } },

        resetRegion() {
            if (!this.wsRegions || !this.ws) return;
            this.wsRegions.clearRegions();
            const dur = this.ws.getDuration();
            this.wsRegions.addRegion({ start: 0, end: dur, color: 'rgba(124,58,237,0.25)', drag: true, resize: true });
            this.startTime = 0;
            this.endTime   = Math.round(dur * 10) / 10;
            this.$wire.set('data.audio_start', 0);
            this.$wire.set('data.audio_end',   this.endTime);
        },
    }"
    x-init="init()"
    class="space-y-3"
>
    {{-- IDLE: no audio yet --}}
    <div x-show="state === 'idle'"
        class="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-4 text-center text-sm text-gray-400">
        Audio upload karo — waveform yahan dikhega aur crop kar sako gy.
    </div>

    {{-- WAVEFORM WRAPPER (always in DOM when not idle so WaveSurfer can render) --}}
    <div x-show="state !== 'idle'" class="space-y-3">

        {{-- Container: waveform renders here, loading overlay on top --}}
        <div class="relative rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700" style="background:#0f0f1a; min-height:80px;">

            {{-- WaveSurfer mounts here — NEVER hidden with x-show --}}
            <div x-ref="waveformEl" style="width:100%;"></div>

            {{-- Loading overlay --}}
            <div x-show="state === 'loading'"
                style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;gap:8px;background:rgba(10,10,26,0.85);"
                class="text-sm text-gray-400">
                <svg class="w-4 h-4 animate-spin text-violet-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                Waveform load ho raha hai...
            </div>
        </div>

        {{-- Error --}}
        <p x-show="state === 'error'" class="text-sm text-red-500 px-1">
            ⚠️ Waveform load nahi hua — audio route ya CDN check karo. Trim fields manually fill karo.
        </p>

        {{-- Controls (only when ready) --}}
        <div x-show="state === 'ready'" x-transition class="flex flex-wrap items-center gap-3">
            <button type="button" @click="togglePlay()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 transition-colors">
                <span x-text="playing ? '⏸ Pause' : '▶ Play'"></span>
            </button>

            <button type="button" @click="playSelected()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-white bg-gray-700 hover:bg-gray-600 transition-colors">
                ✂️ Play Crop
            </button>

            <button type="button" @click="resetRegion()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                ↺ Reset
            </button>

            <div class="ml-auto flex items-center gap-2 text-sm">
                <span class="px-2 py-1 rounded bg-violet-900/40 text-violet-300 font-mono" x-text="startTime + 's'"></span>
                <span class="text-gray-500">→</span>
                <span class="px-2 py-1 rounded bg-violet-900/40 text-violet-300 font-mono" x-text="endTime + 's'"></span>
                <span class="text-gray-500 text-xs">/ <span x-text="duration + 's'"></span></span>
            </div>
        </div>

        <p x-show="state === 'ready'" class="text-xs text-gray-500 px-1">
            Purple region drag karo ya handles khicho crop karne ke liye.
        </p>
    </div>
</div>
