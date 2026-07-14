export default (config = {}) => ({
    files: [],
    dragging: false,
    uploading: false,
    validationErrors: [],
    _dragCounter: 0,
    _isLivewire: false,
    _nativeFiles: null,
    _uploadMeta: null,

    // Los handlers de los hooks de Livewire. Sin declarar, Alpine los escribiría en el x-data
    // ancestro: dos uploads en la misma página se pisarían los suyos y el segundo recibiría
    // el progreso del primero. Ver tests/js/alpine-scope.test.js.
    _onStart: null,
    _onProgress: null,
    _onFinish: null,
    _onError: null,

    init() {
        this._isLivewire = !!this.$wire;
        this._nativeFiles = new Map();
        this._uploadMeta = new Map();

        // Load static files
        if (config.static && config.staticFiles) {
            this.files = config.staticFiles.map(f => ({
                id: this._uid(),
                name: f.name,
                size: f.size,
                type: f.type || '',
                progress: 100,
                status: 'static',
                previewUrl: f.url || null,
                speed: 0,
                eta: null,
                retries: 0,
            }));
        }

        if (this._isLivewire) {
            this._bindLivewireEvents();
        }
    },

    destroy() {
        if (this._isLivewire) {
            this._unbindLivewireEvents();
        }
        this.files.forEach(f => {
            if (f.previewUrl && f.status !== 'static') {
                URL.revokeObjectURL(f.previewUrl);
            }
        });
        this._nativeFiles.clear();
        this._uploadMeta.clear();
    },

    // --- Livewire event binding ---

    _bindLivewireEvents() {
        const input = this._getLivewireInput();
        if (!input) return;

        this._onStart = () => {
            this.uploading = true;
            const last = this.files.findLast(f => f.status === 'pending');
            if (last) {
                last.status = 'uploading';
                // Initialize upload metadata for speed/ETA tracking
                const now = performance.now();
                this._uploadMeta.set(last.id, { startTime: now, lastBytes: 0, lastTime: now });
            }
        };
        this._onProgress = (e) => {
            const uploading = this.files.findLast(f => f.status === 'uploading');
            if (!uploading) return;

            const progress = e.detail.progress;
            uploading.progress = progress;

            // Calculate speed and ETA
            const meta = this._uploadMeta.get(uploading.id);
            if (meta) {
                const now = performance.now();
                const currentBytes = (progress / 100) * uploading.size;
                const deltaTime = (now - meta.lastTime) / 1000;

                if (deltaTime > 0.2) {
                    const deltaBytes = currentBytes - meta.lastBytes;
                    const instantSpeed = deltaBytes / deltaTime;
                    uploading.speed = uploading.speed > 0
                        ? uploading.speed * 0.7 + instantSpeed * 0.3
                        : instantSpeed;
                    const remainingBytes = uploading.size - currentBytes;
                    uploading.eta = uploading.speed > 0
                        ? Math.ceil(remainingBytes / uploading.speed)
                        : null;
                    meta.lastBytes = currentBytes;
                    meta.lastTime = now;
                }
            }
        };
        this._onFinish = () => {
            this.uploading = false;
            const uploading = this.files.findLast(f => f.status === 'uploading');
            if (uploading) {
                uploading.status = 'complete';
                uploading.progress = 100;
                uploading.speed = 0;
                uploading.eta = null;
                this._uploadMeta.delete(uploading.id);
            }
        };
        this._onError = () => {
            this.uploading = false;
            const uploading = this.files.findLast(f => f.status === 'uploading');
            if (uploading) {
                uploading.speed = 0;
                uploading.eta = null;
                this._uploadMeta.delete(uploading.id);

                if (config.retryable && uploading.retries < (config.maxRetries || 3)) {
                    uploading.retries++;
                    uploading.status = 'retrying';
                    uploading.progress = 0;
                    setTimeout(() => this._retryFile(uploading.id), config.retryDelay || 2000);
                } else {
                    uploading.status = 'error';
                }
            }
        };

        input.addEventListener('livewire-upload-start', this._onStart);
        input.addEventListener('livewire-upload-progress', this._onProgress);
        input.addEventListener('livewire-upload-finish', this._onFinish);
        input.addEventListener('livewire-upload-error', this._onError);
    },

    _unbindLivewireEvents() {
        const input = this._getLivewireInput();
        if (!input) return;

        if (this._onStart) input.removeEventListener('livewire-upload-start', this._onStart);
        if (this._onProgress) input.removeEventListener('livewire-upload-progress', this._onProgress);
        if (this._onFinish) input.removeEventListener('livewire-upload-finish', this._onFinish);
        if (this._onError) input.removeEventListener('livewire-upload-error', this._onError);
    },

    // --- File handling ---

    openFilePicker() {
        if (config.disabled) return;
        this.$refs.fileInput.click();
    },

    onFileSelected(event) {
        const fileList = event.target.files;
        if (!fileList || fileList.length === 0) return;
        this._addFiles(fileList);
    },

    _addFiles(fileList) {
        const filesToAdd = Array.from(fileList);

        for (const nativeFile of filesToAdd) {
            // Validate
            const validation = this._validate(nativeFile);
            if (!validation.valid) {
                this.validationErrors.push({ file: nativeFile.name, message: validation.message });
                continue;
            }

            // Skip duplicates
            if (this._isDuplicate(nativeFile)) continue;

            // Check maxFiles
            if (config.maxFiles && this.files.filter(f => f.status !== 'error').length >= config.maxFiles) {
                this.validationErrors.push({
                    file: nativeFile.name,
                    message: `Maximum ${config.maxFiles} file${config.maxFiles > 1 ? 's' : ''} allowed.`,
                });
                continue;
            }

            const fileObj = {
                id: this._uid(),
                name: nativeFile.name,
                size: nativeFile.size,
                type: nativeFile.type,
                progress: 0,
                status: this._isLivewire ? 'pending' : 'ready',
                previewUrl: null,
                speed: 0,
                eta: null,
                retries: 0,
            };

            // Create preview for images
            if (config.preview && nativeFile.type.startsWith('image/')) {
                fileObj.previewUrl = URL.createObjectURL(nativeFile);
            }

            // Store native File reference (for retry and manual upload)
            this._nativeFiles.set(fileObj.id, nativeFile);

            // Single mode: replace existing file
            if (!config.multiple) {
                this.files.forEach(f => {
                    if (f.previewUrl && f.status !== 'static') URL.revokeObjectURL(f.previewUrl);
                    this._nativeFiles.delete(f.id);
                });
                this.files = [fileObj];
            } else {
                this.files.push(fileObj);
            }
        }
    },

    // --- Drag & Drop ---

    onDragEnter(e) {
        e.preventDefault();
        this._dragCounter++;
        this.dragging = true;
    },

    onDragOver(e) {
        e.preventDefault();
    },

    onDragLeave(e) {
        this._dragCounter--;
        if (this._dragCounter === 0) this.dragging = false;
    },

    onDrop(e) {
        e.preventDefault();
        this._dragCounter = 0;
        this.dragging = false;
        if (config.disabled) return;

        const droppedFiles = e.dataTransfer.files;
        if (!droppedFiles || droppedFiles.length === 0) return;

        this._addFiles(droppedFiles);

        // Auto-assign to Livewire input (unless autoUpload is false)
        if (config.autoUpload !== false) {
            this._assignFilesToInput(droppedFiles);
        }
    },

    // --- Paste ---

    onPaste(event) {
        if (config.disabled || !config.pasteable) return;

        const files = event.clipboardData?.files;
        if (!files || files.length === 0) return;

        this._addFiles(files);

        // Auto-assign to Livewire input (unless autoUpload is false)
        if (config.autoUpload !== false) {
            this._assignFilesToInput(files);
        }
    },

    // --- Delete & Clear ---

    removeFile(fileId) {
        const index = this.files.findIndex(f => f.id === fileId);
        if (index === -1) return;

        const file = this.files[index];

        // Revoke preview URL
        if (file.previewUrl && file.status !== 'static') {
            URL.revokeObjectURL(file.previewUrl);
        }

        // Call Livewire delete method if configured
        if (this._isLivewire && config.deletable && config.deleteMethod) {
            this.$wire.call(config.deleteMethod, { name: file.name });
        }

        this.files.splice(index, 1);
        this._nativeFiles.delete(fileId);

        // Reset input for non-Livewire
        if (!this._isLivewire) {
            this.$refs.fileInput.value = '';
        }
    },

    clear() {
        this.files.forEach(f => {
            if (f.previewUrl && f.status !== 'static') {
                URL.revokeObjectURL(f.previewUrl);
            }
        });
        this.files = [];
        this.validationErrors = [];
        this._nativeFiles.clear();
        this.$refs.fileInput.value = '';

        if (this._isLivewire) {
            const modelName = this._getWireModelName();
            if (modelName) {
                this.$wire.$set(modelName, config.multiple ? [] : null);
            }
        }
    },

    removeValidationError(index) {
        this.validationErrors.splice(index, 1);
    },

    // --- Retry ---

    _retryFile(fileId) {
        const file = this.files.find(f => f.id === fileId);
        if (!file) return;

        const nativeFile = this._nativeFiles.get(fileId);
        if (!nativeFile) {
            file.status = 'error';
            return;
        }

        file.status = 'pending';
        this._assignFilesToInput([nativeFile]);
    },

    retryFile(fileId) {
        const file = this.files.find(f => f.id === fileId);
        if (!file) return;

        file.retries = 0;
        file.progress = 0;
        this._retryFile(fileId);
    },

    // --- Manual Upload (staging mode) ---

    get hasPendingFiles() {
        return this.files.some(f => f.status === 'pending');
    },

    get pendingCount() {
        return this.files.filter(f => f.status === 'pending').length;
    },

    uploadPending() {
        const pendingFiles = this.files.filter(f => f.status === 'pending');
        const nativeFiles = pendingFiles.map(f => this._nativeFiles.get(f.id)).filter(Boolean);
        if (nativeFiles.length === 0) return;

        const input = this._getLivewireInput();
        if (!input) return;

        try {
            const dt = new DataTransfer();
            nativeFiles.forEach(f => dt.items.add(f));
            input.files = dt.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (e) {
            // DataTransfer not supported
        }
    },

    // --- Validation ---

    _validate(file) {
        // Check accept
        if (config.accept) {
            if (!this._matchesAccept(file, config.accept)) {
                const msg = config.invalidTypeMessage
                    ? config.invalidTypeMessage.replace('{name}', file.name)
                    : `"${file.name}" is not an accepted file type.`;
                return { valid: false, message: msg };
            }
        }

        // Check maxSize (in MB)
        if (config.maxSize) {
            const sizeMB = file.size / (1024 * 1024);
            if (sizeMB > config.maxSize) {
                const msg = config.invalidSizeMessage
                    ? config.invalidSizeMessage
                        .replace('{name}', file.name)
                        .replace('{size}', this._formatSize(file.size))
                        .replace('{maxSize}', config.maxSize + ' MB')
                    : `"${file.name}" (${this._formatSize(file.size)}) exceeds the ${config.maxSize} MB limit.`;
                return { valid: false, message: msg };
            }
        }

        return { valid: true, message: null };
    },

    _matchesAccept(file, acceptString) {
        const tokens = acceptString.split(',').map(t => t.trim()).filter(Boolean);
        const fileName = file.name.toLowerCase();
        const fileType = file.type.toLowerCase();

        return tokens.some(token => {
            token = token.toLowerCase();
            if (token.startsWith('.')) {
                // Extension match
                return fileName.endsWith(token);
            } else if (token.endsWith('/*')) {
                // MIME prefix match (e.g. image/*)
                const prefix = token.slice(0, -1);
                return fileType.startsWith(prefix);
            } else {
                // Exact MIME match
                return fileType === token;
            }
        });
    },

    _isDuplicate(file) {
        return this.files.some(f => f.name === file.name && f.size === file.size && f.type === file.type);
    },

    // --- Getters ---

    get hasFiles() {
        return this.files.length > 0;
    },

    get isUploading() {
        return this.files.some(f => f.status === 'uploading');
    },

    get constraintsText() {
        const parts = [];
        if (config.maxSize) {
            parts.push(`Max ${config.maxSize} MB per file`);
        }
        if (config.accept) {
            const tokens = config.accept.split(',').map(t => t.trim()).filter(Boolean);
            const labels = tokens.map(t => {
                if (t.startsWith('.')) return t.slice(1).toUpperCase();
                if (t.endsWith('/*')) return t.split('/')[0];
                return t;
            });
            parts.push(labels.join(', '));
        }
        if (config.maxFiles) {
            parts.push(`Up to ${config.maxFiles} file${config.maxFiles > 1 ? 's' : ''}`);
        }
        return parts.length > 0 ? parts.join('. ') + '.' : '';
    },

    // --- Utilities ---

    _getLivewireInput() {
        return this.$refs.livewireInput || this.$refs.fileInput;
    },

    _assignFilesToInput(fileList) {
        if (!this._isLivewire) return;
        try {
            const input = this._getLivewireInput();
            const dt = new DataTransfer();
            Array.from(fileList).forEach(f => dt.items.add(f));
            input.files = dt.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (e) {
            // DataTransfer not supported in older browsers — Livewire won't get the file
        }
    },

    _formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        const size = (bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1);
        return size + ' ' + units[i];
    },

    formatSpeed(bytesPerSec) {
        if (bytesPerSec <= 0) return '';
        if (bytesPerSec >= 1024 * 1024) {
            return (bytesPerSec / (1024 * 1024)).toFixed(1) + ' MB/s';
        }
        if (bytesPerSec >= 1024) {
            return (bytesPerSec / 1024).toFixed(0) + ' KB/s';
        }
        return Math.round(bytesPerSec) + ' B/s';
    },

    formatEta(seconds) {
        if (seconds == null || seconds <= 0) return '';
        if (seconds < 60) return seconds + 's';
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return secs > 0 ? `${mins}m ${secs}s` : `${mins}m`;
    },

    _uid() {
        return 'kore-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
    },

    _getWireModelName() {
        const input = this.$refs.fileInput;
        if (!input) return null;
        return input.getAttribute('wire:model.live')
            || input.getAttribute('wire:model.blur')
            || input.getAttribute('wire:model.defer')
            || input.getAttribute('wire:model');
    },
});
