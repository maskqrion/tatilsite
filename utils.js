/**
 * utils.js — Shared utilities for Seçkin Rotalar
 *
 * Include this file BEFORE any page-specific script that uses CSRF tokens
 * or the handleTokenError pattern. It exposes two globals: SR.csrf and SR.handleTokenError.
 *
 * WHY THIS FILE EXISTS (DRY principle):
 * handleTokenError() and the "wait for window.csrfToken" polling loop
 * were copy-pasted into rota-detay.js, yorum.js, profilim.js, mekan_paneli.js
 * and favori.js. Five copies of the same 10-line block. One bug fix
 * would require five separate edits and easy to miss one.
 */

window.SR = window.SR || {};

/**
 * Returns a promise that resolves once main.js has fetched and set
 * window.csrfToken. Polls every 80ms with a 5-second hard timeout
 * so the page does not hang forever if check_session.php fails.
 *
 * Usage:
 *   const token = await SR.csrf();
 *   formData.append('csrf_token', token);
 */
SR.csrf = function csrfReady() {
    if (window.csrfToken) return Promise.resolve(window.csrfToken);

    return new Promise((resolve, reject) => {
        const started = Date.now();

        const poll = () => {
            if (window.csrfToken) {
                resolve(window.csrfToken);
            } else if (Date.now() - started > 5000) {
                reject(new Error('CSRF token yüklenemedi. Lütfen sayfayı yenileyin.'));
            } else {
                setTimeout(poll, 80);
            }
        };

        poll();
    });
};

/**
 * Central response-error handler used after every fetch() call that
 * returns a { success, message } JSON object from the server.
 *
 * If the server reports an invalid security key the session has expired
 * and we must reload — continuing to submit forms with a stale token
 * would keep failing silently.
 *
 * @param {string} message  The message from result.message
 */
SR.handleTokenError = function handleTokenError(message) {
    if (message && message.includes('güvenlik anahtarı')) {
        alert('Oturumunuz zaman aşımına uğradı. Lütfen sayfayı yenileyin.');
        window.location.reload();
    } else {
        alert(message || 'Bilinmeyen bir hata oluştu.');
    }
};

/**
 * Thin wrapper around fetch() for POST requests that automatically
 * appends the CSRF token and returns parsed JSON.
 *
 * Usage:
 *   const result = await SR.post('favori_islemleri.php', formData);
 *   if (!result.success) SR.handleTokenError(result.message);
 *
 * @param {string}   url
 * @param {FormData} formData   Must NOT already contain csrf_token.
 * @returns {Promise<object>}
 */
SR.post = async function srPost(url, formData) {
    const token = await SR.csrf();
    formData.append('csrf_token', token);

    const response = await fetch(url, { method: 'POST', body: formData });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${url}`);
    }

    return response.json();
};
