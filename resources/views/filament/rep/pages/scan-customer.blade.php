<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Scan Customer QR Code</x-slot>
        <x-slot name="description">Point the camera at a customer's QR code to open their lookup page.</x-slot>

        <div x-data="customerScanner(@js($this->getLookupUrlPrefix()))" x-init="init()" class="space-y-4">
            <div class="relative overflow-hidden rounded-lg bg-black" style="aspect-ratio: 4 / 3;">
                <video x-ref="video" class="h-full w-full object-cover" playsinline muted></video>
                <canvas x-ref="canvas" class="hidden"></canvas>
            </div>

            <p x-show="error" x-text="error" x-cloak class="text-sm text-danger-600"></p>
            <p x-show="!cameraSupported" x-cloak class="text-sm text-gray-500 dark:text-gray-400">
                Camera scanning isn't available on this device or browser. Enter the customer's code below instead.
            </p>

            <form @submit.prevent="goManual()" class="flex gap-2">
                <x-filament::input.wrapper class="flex-1">
                    <x-filament::input
                        type="text"
                        x-model="manualToken"
                        placeholder="Or paste/enter the customer's QR code"
                    />
                </x-filament::input.wrapper>
                <x-filament::button type="submit">Go</x-filament::button>
            </form>
        </div>
    </x-filament::section>

    <script src="{{ asset('vendor/jsqr/jsQR.min.js') }}"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('customerScanner', (lookupUrlPrefix) => ({
                error: null,
                cameraSupported: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),
                manualToken: '',
                stream: null,
                scanning: false,

                init() {
                    if (!this.cameraSupported) {
                        return;
                    }
                    this.startCamera();

                    this.$watch('$el.isConnected', (isConnected) => {
                        if (!isConnected) {
                            this.stopCamera();
                        }
                    });
                },

                async startCamera() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: {facingMode: 'environment'},
                        });
                        this.$refs.video.srcObject = this.stream;
                        await this.$refs.video.play();
                        this.scanning = true;
                        requestAnimationFrame(() => this.tick());
                    } catch (e) {
                        this.error = 'Camera access was denied or unavailable. Enter the customer code below instead.';
                        this.cameraSupported = false;
                    }
                },

                stopCamera() {
                    this.scanning = false;
                    this.stream?.getTracks().forEach((track) => track.stop());
                },

                tick() {
                    if (!this.scanning) {
                        return;
                    }

                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;

                    if (video.readyState === video.HAVE_ENOUGH_DATA) {
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(imageData.data, imageData.width, imageData.height);

                        if (code && code.data) {
                            this.handleDecoded(code.data);
                            return;
                        }
                    }

                    requestAnimationFrame(() => this.tick());
                },

                handleDecoded(text) {
                    if (text.startsWith(lookupUrlPrefix)) {
                        this.stopCamera();
                        window.location.href = text;
                        return;
                    }
                    this.error = 'That QR code isn\'t a recognized customer code.';
                    requestAnimationFrame(() => this.tick());
                },

                goManual() {
                    const token = this.manualToken.trim();
                    if (!token) {
                        return;
                    }
                    window.location.href = lookupUrlPrefix + encodeURIComponent(token);
                },
            }));
        });
    </script>
</x-filament-panels::page>
