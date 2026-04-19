/* La Mejor Taza — admin JS: live ranking refresher + sello preview */
(function () {
    'use strict';
    const list = document.getElementById('lmt-live-list');
    if (!list) return;
    const endpoint = list.dataset.endpoint;
    const emojiMap = { bueno: '😍', regular: '😐', malo: '😞' };

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, ch => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[ch]));
    }

    async function refresh() {
        try {
            const res = await fetch(endpoint + '?limit=12&_=' + Date.now());
            if (!res.ok) return;
            const items = await res.json();
            list.innerHTML = items.map(it => `
                <div class="lmt-live-item">
                    <span class="emoji">${emojiMap[it.emoji] || '·'}</span>
                    <div class="body">
                        <div class="name">${escapeHtml(it.stand)}</div>
                        <div class="quote">"${escapeHtml(it.comentario || '')}"</div>
                        <div class="mono">${escapeHtml(it.autor)} · ${escapeHtml(it.hora)}${it.comprado ? ' · <span class="bought">Compró</span>' : ''}</div>
                    </div>
                </div>
            `).join('') || '<p class="lmt-hint">—</p>';
        } catch (_) {}
    }
    setInterval(refresh, 6000);
})();
