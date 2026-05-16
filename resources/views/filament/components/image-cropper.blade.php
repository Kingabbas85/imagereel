@once
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
@endonce

<div
    wire:ignore
    x-data="{
        /*
         * Each entry: { url: string, path: string|null, isTemp: bool }
         * url   = what we show in the cropper
         * path  = permanent path (after crop-save) or null (still temp)
         * isTemp = true means not yet permanently saved
         */
        images: [],
        cropper: null,
        activeIndex: null,
        modalOpen: false,
        saving: false,
        resolution: { w: 576, h: 1024 },

        // ── helpers ───────────────────────────────────────────────
        parseRaw(raw) {
            if (!raw) return [];
            const arr = Array.isArray(raw) ? raw : Object.values(raw ?? {});
            return arr.map(item => {
                if (!item) return null;

                // Already a permanent path string
                if (typeof item === 'string' && item.length && !item.startsWith('[object')) {
                    return { url: '/admin/preview/image?path=' + encodeURIComponent(item), path: item, isTemp: false };
                }

                // Livewire TemporaryUploadedFile object
                if (typeof item === 'object') {
                    const tempUrl = item.temporaryUrl ?? item.previewUrl ?? null;
                    const path    = item.path ?? null;
                    if (tempUrl) return { url: tempUrl, path, isTemp: true };
                }
                return null;
            }).filter(Boolean);
        },

        parseResolution(raw) {
            if (!raw || typeof raw !== 'string') return;
            const [w, h] = raw.split('x').map(Number);
            if (w > 0 && h > 0) this.resolution = { w, h };
        },

        get aspectRatio() { return this.resolution.w / this.resolution.h; },

        // ── init ──────────────────────────────────────────────────
        async init() {
            await this.$nextTick();

            // Try initial state from wire (edit page)
            this.images = this.parseRaw(this.$wire.get('data.image_paths'));
            this.parseResolution(this.$wire.get('data.video_resolution'));

            // Watch Livewire commits — fires after every server round-trip
            if (window.Livewire) {
                window.Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                    succeed(({ snapshot }) => {
                        try {
                            const d = snapshot?.data ?? {};
                            const parsed = this.parseRaw(d.image_paths);
                            if (parsed.length) this.images = parsed;
                            this.parseResolution(d.video_resolution);
                        } catch(e) {}
                    });
                });
            }
        },

        // ── cropper ───────────────────────────────────────────────
        openCropper(index) {
            this.activeIndex = index;
            this.modalOpen   = true;

            this.$nextTick(() => {
                const imgEl = this.$refs.cropImg;
                if (!imgEl) return;

                if (this.cropper) { this.cropper.destroy(); this.cropper = null; }

                imgEl.crossOrigin = 'anonymous';
                imgEl.src = '';
                imgEl.onload = () => {
                    this.cropper = new Cropper(imgEl, {
                        aspectRatio:              this.aspectRatio,
                        viewMode:                 1,
                        dragMode:                 'move',
                        autoCropArea:             1,
                        restore:                  false,
                        guides:                   true,
                        center:                   true,
                        highlight:                false,
                        cropBoxMovable:           true,
                        cropBoxResizable:         true,
                        toggleDragModeOnDblclick: false,
                        background:               false,
                    });
                };
                imgEl.src = this.images[index]?.url ?? '';
            });
        },

        closeCropper() {
            if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
            this.modalOpen = false;
            this.saving    = false;
        },

        async saveCrop() {
            if (!this.cropper || this.saving) return;
            this.saving = true;

            try {
                // Get cropped canvas at exact resolution
                const canvas = this.cropper.getCroppedCanvas({
                    width:  this.resolution.w,
                    height: this.resolution.h,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                const base64 = canvas.toDataURL('image/jpeg', 0.92);

                const res = await fetch('/admin/save-cropped-image', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        image:    base64,
                        target_w: this.resolution.w,
                        target_h: this.resolution.h,
                    }),
                });

                const json = await res.json();

                if (json.success) {
                    // Replace entry with permanent path
                    this.images[this.activeIndex] = {
                        url:    '/admin/preview/image?path=' + encodeURIComponent(json.path),
                        path:   json.path,
                        isTemp: false,
                    };
                    this.images = [...this.images]; // trigger reactivity

                    // Update Livewire state with all permanent paths
                    const paths = this.images.map(img => img.path).filter(Boolean);
                    this.$wire.set('data.image_paths', paths);

                    this.closeCropper();
                } else {
                    alert('❌ ' + (json.error ?? 'Crop save failed'));
                    this.saving = false;
                }
            } catch(e) {
                console.error('Crop error:', e);
                alert('❌ Error: ' + e.message);
                this.saving = false;
            }
        },
    }"
    x-init="init()"
    class="mt-1"
>
    {{-- Empty state --}}
    <p x-show="images.length === 0"
        class="text-xs text-gray-400 text-center py-2.5 border border-dashed border-gray-300 dark:border-gray-600 rounded-xl">
        Images upload karo — crop buttons yahan dikhenge.
    </p>

    {{-- Image grid --}}
    <div x-show="images.length > 0"
        class="grid gap-3"
        :style="`grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));`">

        <template x-for="(img, index) in images" :key="index">
            <div class="relative group rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 cursor-pointer"
                :style="`aspect-ratio: ${resolution.w} / ${resolution.h};`"
                @click="openCropper(index)">

                <img :src="img.url" class="w-full h-full object-cover" alt="" loading="lazy">

                {{-- Hover overlay --}}
                <div class="absolute inset-0 bg-black/55 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-1.5">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
                    </svg>
                    <span class="text-white text-xs font-semibold">✂️ Crop</span>
                </div>

                {{-- Temp badge --}}
                <div x-show="img.isTemp"
                    class="absolute top-1 right-1 px-1 py-0.5 bg-amber-500 text-white text-[9px] font-bold rounded leading-none">
                    TEMP
                </div>

                {{-- Cropped badge --}}
                <div x-show="!img.isTemp"
                    class="absolute top-1 right-1 px-1 py-0.5 bg-emerald-500 text-white text-[9px] font-bold rounded leading-none">
                    ✓
                </div>
            </div>
        </template>
    </div>

    <p x-show="images.length > 0" class="text-xs text-gray-400 mt-2 px-0.5">
        Image par click karo ya hover karo crop karne ke liye •
        <span class="font-mono text-violet-400" x-text="`${resolution.w}×${resolution.h}`"></span> output
    </p>

    {{-- Cropper Modal --}}
    <template x-teleport="body">
        <div
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/85"
            @keydown.escape.window="closeCropper()"
        >
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl flex flex-col overflow-hidden"
                style="width: min(720px, 95vw); max-height: 92vh;">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <div>
                        <p class="font-semibold text-sm text-gray-900 dark:text-white">Crop Image</p>
                        <p class="text-xs text-gray-400 mt-0.5"
                            x-text="`Output: ${resolution.w}×${resolution.h}px • Drag to move • Handles to resize`"></p>
                    </div>
                    <button type="button" @click="closeCropper()"
                        class="w-7 h-7 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition text-lg">
                        ✕
                    </button>
                </div>

                {{-- Canvas --}}
                <div class="flex-1 overflow-hidden bg-gray-950 flex items-center justify-center" style="min-height: 280px;">
                    <img x-ref="cropImg" src="" alt="" crossorigin="anonymous"
                        style="display:block; max-width:100%; max-height: calc(92vh - 130px);">
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between px-5 py-3.5 border-t border-gray-200 dark:border-gray-700 shrink-0">
                    <p class="text-xs text-gray-400">
                        Cropped image → permanent storage mein save hogi
                    </p>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="closeCropper()"
                            class="px-4 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            Cancel
                        </button>
                        <button type="button" @click="saveCrop()" :disabled="saving"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 disabled:opacity-60 transition flex items-center gap-2">
                            <span x-show="saving"
                                class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span x-text="saving ? 'Saving…' : '✂️ Save Crop'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
