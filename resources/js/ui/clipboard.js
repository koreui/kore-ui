export default (config) => ({
    text: config.text ?? '',
    copied: false,
    feedbackDuration: config.feedbackDuration ?? 2000,
    _timeout: null,

    copy() {
        if (this.copied) return;

        navigator.clipboard.writeText(this.text).then(() => {
            this._onCopied();
        }).catch(() => {
            const textarea = document.createElement('textarea');
            textarea.value = this.text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            this._onCopied();
        });
    },

    _onCopied() {
        this.copied = true;
        this.$dispatch('clipboard-copied', { text: this.text });
        this._timeout = setTimeout(() => {
            this.copied = false;
        }, this.feedbackDuration);
    },

    destroy() {
        if (this._timeout) clearTimeout(this._timeout);
    },
});
