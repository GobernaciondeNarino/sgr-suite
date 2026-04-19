/* La Mejor Taza — public JS: vote flow, passport flip, dashboard live feed */
(function () {
    'use strict';
    if (typeof LMT_DATA === 'undefined') return;

    const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));
    const $  = (sel, root) => (root || document).querySelector(sel);

    /* ---------- Vote form ---------- */
    function initVote() {
        const form = document.getElementById('lmt-vote-form');
        if (!form) return;
        const standId = form.dataset.stand;
        const steps = $$('.step', form);
        const progress = $('.progress', form);
        const state = { email: '', emoji: null, compra: null, comentario: '' };

        const showStep = (i) => {
            steps.forEach(s => s.classList.toggle('is-active', Number(s.dataset.step) === i));
            const dots = $$('i', progress);
            dots.forEach((d, idx) => d.classList.toggle('on', idx <= i));
            progress.dataset.step = i;
        };

        // Email validation
        const emailInput = $('input[name="email"]', form);
        emailInput.addEventListener('input', (e) => {
            state.email = e.target.value.trim();
            const valid = /.+@.+\..+/.test(state.email);
            const next = $('[data-next="1"]', form);
            next.disabled = !valid;
            next.style.opacity = valid ? '1' : '0.4';
        });

        // Emoji selection
        $$('.emoji-btn', form).forEach(btn => {
            btn.addEventListener('click', () => {
                state.emoji = btn.dataset.emoji;
                $$('.emoji-btn', form).forEach(b => b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'));
                const next = $('[data-next="2"]', form);
                next.disabled = false;
                next.style.opacity = '1';
            });
        });

        // Compra
        $$('[data-compra]', form).forEach(btn => {
            btn.addEventListener('click', () => {
                state.compra = btn.dataset.compra === '1';
                $$('[data-compra]', form).forEach(b => b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'));
            });
        });

        // Navigation
        $$('[data-next], [data-prev]', form).forEach(btn => {
            btn.addEventListener('click', () => {
                const target = Number(btn.dataset.next ?? btn.dataset.prev);
                if (!Number.isNaN(target)) showStep(target);
            });
        });

        // Submit
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const err = $('.err', form);
            err.hidden = true;
            state.comentario = ($('textarea[name="comentario"]', form) || {}).value || '';
            const submitBtn = $('button[type="submit"]', form);
            const originalLabel = submitBtn.textContent;
            submitBtn.textContent = LMT_DATA.i18n.sending;
            submitBtn.disabled = true;
            try {
                const res = await fetch(LMT_DATA.rest + '/vote', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': LMT_DATA.nonce },
                    body: JSON.stringify({
                        stand_id: Number(standId),
                        email: state.email,
                        emoji: state.emoji,
                        comprado: !!state.compra,
                        comentario: state.comentario,
                        _wpnonce: LMT_DATA.nonce,
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data && data.message ? data.message : LMT_DATA.i18n.duplicate);
                // Append email to passport link
                const passportLink = form.querySelector('a.btn-primary[href*="?email"], a.btn-primary[href*="&email"]');
                if (passportLink) {
                    const u = new URL(passportLink.href, window.location.origin);
                    u.searchParams.set('email', state.email);
                    passportLink.href = u.toString();
                }
                showStep(3);
                try { localStorage.setItem('lmt_email', state.email); } catch (_) {}
            } catch (ex) {
                err.textContent = ex.message;
                err.hidden = false;
                submitBtn.textContent = originalLabel;
                submitBtn.disabled = false;
            }
        });
    }

    /* ---------- Passport ---------- */
    function initPassport() {
        const book = document.getElementById('lmt-book');
        if (!book) return;
        const pages = $$('.page', book);
        const counter = $('[data-pp-counter]');
        const prevBtn = $('[data-pp-prev]');
        const nextBtn = $('[data-pp-next]');
        let idx = 0;
        let busy = false;

        const render = () => {
            pages.forEach((p, i) => { p.hidden = i !== idx; p.classList.remove('flip'); });
            if (counter) counter.textContent = String(idx + 1).padStart(2, '0') + ' / ' + String(pages.length).padStart(2, '0');
            if (prevBtn) prevBtn.disabled = idx === 0;
            if (nextBtn) nextBtn.disabled = idx === pages.length - 1;
        };

        const go = (delta) => {
            if (busy) return;
            const next = idx + delta;
            if (next < 0 || next >= pages.length) return;
            busy = true;
            const current = pages[idx];
            current.classList.add('flip');
            setTimeout(() => { idx = next; render(); busy = false; }, 480);
        };

        if (prevBtn) prevBtn.addEventListener('click', () => go(-1));
        if (nextBtn) nextBtn.addEventListener('click', () => go(1));

        // Swipe
        let touchX = 0;
        book.addEventListener('touchstart', (e) => { touchX = e.touches[0].clientX; }, { passive: true });
        book.addEventListener('touchend', (e) => {
            const dx = e.changedTouches[0].clientX - touchX;
            if (Math.abs(dx) > 40) go(dx < 0 ? 1 : -1);
        });
        // Keyboard
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') go(1);
            if (e.key === 'ArrowLeft') go(-1);
        });
        render();
    }

    /* ---------- Live feed ---------- */
    function initLive() {
        const feed = document.getElementById('lmt-public-live');
        if (!feed) return;
        const endpoint = feed.dataset.endpoint;
        const emojiMap = { bueno: '😍', regular: '😐', malo: '😞' };
        async function refresh() {
            try {
                const res = await fetch(endpoint + '?limit=6&_=' + Date.now());
                if (!res.ok) return;
                const items = await res.json();
                feed.innerHTML = items.map(it => `
                    <div class="item" data-stand="${it.stand_id}" style="animation: lmt-fade-up 0.4s">
                        <span class="emoji">${emojiMap[it.emoji] || '·'}</span>
                        <div>
                            <div style="font-size:13px; font-weight:500;">${escapeHtml(it.stand)}</div>
                            <div class="quote">"${escapeHtml(it.comentario || '')}"</div>
                            <div class="meta">${escapeHtml(it.autor)} · ${escapeHtml(it.hora)}${it.comprado ? ' · <span style="color:var(--cafeto)">compró</span>' : ''}</div>
                        </div>
                    </div>
                `).join('') || '<p class="mono" style="text-align:center">—</p>';
            } catch (_) {}
        }
        setInterval(refresh, 8000);
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, ch => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[ch]));
    }

    document.addEventListener('DOMContentLoaded', () => {
        initVote();
        initPassport();
        initLive();
    });
})();
