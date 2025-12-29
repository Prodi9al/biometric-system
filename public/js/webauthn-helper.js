/**
 * WebAuthn Helper Library for Biometric ATM
 * Handles encoding/decoding and API calls
 */

const WebAuthn = {
    // Utilities
    bufferToBase64url: (buffer) => {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary)
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=/g, '');
    },

    base64urlToBuffer: (base64url) => {
        if (!base64url || typeof base64url !== 'string') {
            console.error("Invalid base64url input:", base64url);
            return new Uint8Array(0);
        }
        try {
            const padding = '='.repeat((4 - base64url.length % 4) % 4);
            const base64 = (base64url + padding)
                .replace(/\-/g, '+')
                .replace(/_/g, '/')
                .replace(/\s/g, '');

            const raw = atob(base64);
            const output = new Uint8Array(raw.length);
            for (let i = 0; i < raw.length; i++) {
                output[i] = raw.charCodeAt(i);
            }
            return output.buffer;
        } catch (e) {
            console.error("Base64 decode failed:", e);
            throw new Error("Invalid biometric challenge format");
        }
    },

    // Registration (Enrollment)
    register: async (user, challenge) => {
        const publicKey = {
            challenge: WebAuthn.base64urlToBuffer(challenge),
            rp: { name: "GCTU Secure ATM" },
            user: {
                id: WebAuthn.base64urlToBuffer(user.id),
                name: user.name,
                displayName: user.displayName
            },
            pubKeyCredParams: [
                { alg: -7, type: "public-key" }, // ES256
                { alg: -257, type: "public-key" } // RS256
            ],
            timeout: 60000,
            attestation: "direct",
            authenticatorSelection: {
                // authenticatorAttachment: "platform", // Allow both Platform (TouchID/Hello) and Cross-Platform (USB/NFC)
                userVerification: "required"
            }
        };

        const credential = await navigator.credentials.create({ publicKey });

        return {
            id: credential.id,
            rawId: WebAuthn.bufferToBase64url(credential.rawId),
            type: credential.type,
            response: {
                attestationObject: WebAuthn.bufferToBase64url(credential.response.attestationObject),
                clientDataJSON: WebAuthn.bufferToBase64url(credential.response.clientDataJSON)
            }
        };
    },

    // Authentication (Login)
    authenticate: async (challenge, credentialIds = []) => {
        const publicKey = {
            challenge: WebAuthn.base64urlToBuffer(challenge),
            timeout: 60000,
            userVerification: "required",
            // If we have specific allowed credentials, list them
            allowCredentials: credentialIds.map(id => ({
                id: WebAuthn.base64urlToBuffer(id),
                type: 'public-key'
            }))
        };

        const assertion = await navigator.credentials.get({ publicKey });

        return {
            id: assertion.id,
            rawId: WebAuthn.bufferToBase64url(assertion.rawId),
            response: {
                authenticatorData: WebAuthn.bufferToBase64url(assertion.response.authenticatorData),
                clientDataJSON: WebAuthn.bufferToBase64url(assertion.response.clientDataJSON),
                signature: WebAuthn.bufferToBase64url(assertion.response.signature),
                userHandle: assertion.response.userHandle ? WebAuthn.bufferToBase64url(assertion.response.userHandle) : null
            }
        };
    }
};

window.WebAuthn = WebAuthn;
