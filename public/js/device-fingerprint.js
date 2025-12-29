/**
 * Silent Device Fingerprinting Library
 * Collects comprehensive device/browser characteristics without user prompts
 * Perfect for security logging and attacker tracking
 */
const DeviceFingerprint = {
    /**
     * Collect all available device fingerprint data
     * Returns object with all fingerprint attributes
     */
    async collect() {
        const fp = {};

        // Screen & Display
        fp.screen_resolution = `${screen.width}x${screen.height}`;
        fp.color_depth = screen.colorDepth;
        fp.pixel_ratio = window.devicePixelRatio || 1;

        // Timezone & Locale
        fp.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        // Platform & Hardware
        fp.platform = navigator.platform || 'Unknown';
        fp.hardware_concurrency = navigator.hardwareConcurrency || 0;
        fp.device_memory = navigator.deviceMemory || 0;

        // Touch Support
        fp.touch_support = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

        // Privacy Settings
        fp.do_not_track = navigator.doNotTrack || 'unspecified';
        fp.cookie_enabled = navigator.cookieEnabled;
        fp.online_status = navigator.onLine;

        // Canvas Fingerprint (unique hash based on how browser renders)
        fp.canvas_fingerprint = await this.getCanvasFingerprint();

        // WebGL (GPU info - very unique identifier)
        fp.webgl_vendor = this.getWebGLInfo();

        return fp;
    },

    /**
     * Generate canvas fingerprint hash
     * Different browsers/GPUs render differently - acts as unique ID
     */
    async getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Draw text with various styles
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.textBaseline = 'alphabetic';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('SecurityFingerprint123', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('SecurityFingerprint123', 4, 17);

            // Convert to hash
            const dataURL = canvas.toDataURL();
            return await this.hashString(dataURL);
        } catch (e) {
            return 'canvas_blocked';
        }
    },

    /**
     * Get WebGL renderer info (GPU details)
     */
    getWebGLInfo() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');

            if (!gl) return 'WebGL not supported';

            const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            if (!debugInfo) return 'WebGL debug info unavailable';

            const vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
            const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);

            return `${vendor} | ${renderer}`;
        } catch (e) {
            return 'WebGL blocked';
        }
    },

    /**
     * Simple hash function for fingerprint data
     */
    async hashString(str) {
        if (!window.crypto || !window.crypto.subtle) {
            // Fallback for older browsers
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            return Math.abs(hash).toString(16);
        }

        // Modern crypto API
        const msgBuffer = new TextEncoder().encode(str);
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('').substring(0, 64);
    },

    /**
     * Convert fingerprint object to URL params for sending to server
     */
    toFormData(fp) {
        const formData = new FormData();
        Object.keys(fp).forEach(key => {
            if (fp[key] !== null && fp[key] !== undefined) {
                formData.append(key, fp[key]);
            }
        });
        return formData;
    }
};
