import { StreamVideoClient } from 'https://cdn.jsdelivr.net/npm/@stream-io/video-client@1.48.0/dist/index.browser.es.js';

function csrfToken() {
    return document.head.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function postJson(url, body = {}) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data?.message || 'Request failed');
    }

    return data;
}

function readDataset(el) {
    return {
        tokenUrl: el.dataset.tokenUrl,
        callBaseUrl: el.dataset.callBaseUrl,
        streamConfigured: el.dataset.streamConfigured === '1',
        userStreamId: el.dataset.userStreamId || '',
        i18nConnected: el.dataset.i18nConnected || '',
        i18nNotConfigured: el.dataset.i18nNotConfigured || '',
        i18nPhoneLabel: el.dataset.i18nPhoneLabel || '',
        i18nVideoHelp: el.dataset.i18nVideoHelp || '',
        i18nIncoming: el.dataset.i18nIncoming || '',
        i18nJoin: el.dataset.i18nJoin || '',
        i18nDismiss: el.dataset.i18nDismiss || '',
        i18nWaiting: el.dataset.i18nWaiting || '',
        i18nReady: el.dataset.i18nReady || '',
    };
}

function boot() {
    const root = document.getElementById('client-dashboard');
    if (!root) return;

    const ds = readDataset(root);
    const statusEl = document.getElementById('dashboard-stream-status');
    const phoneLabelEl = document.getElementById('dashboard-phone-label');
    const videoHelpEl = document.getElementById('dashboard-video-help');
    const incomingLabelEl = document.getElementById('dashboard-incoming-label');
    const joinStatusEl = document.getElementById('dashboard-join-status');
    const joinBtn = document.getElementById('dashboard-join-btn');
    const joinBtnLabel = document.getElementById('dashboard-join-btn-label');
    const dismissBtn = document.getElementById('dashboard-dismiss-btn');
    const dismissLabel = document.getElementById('dashboard-dismiss-label');

    if (statusEl) statusEl.textContent = ds.streamConfigured ? ds.i18nConnected : ds.i18nNotConfigured;
    if (phoneLabelEl) phoneLabelEl.textContent = ds.i18nPhoneLabel;
    if (videoHelpEl) videoHelpEl.textContent = ds.i18nVideoHelp;
    if (incomingLabelEl) incomingLabelEl.textContent = ds.i18nIncoming;
    if (joinBtnLabel) joinBtnLabel.textContent = ds.i18nJoin;
    if (dismissLabel) dismissLabel.textContent = ds.i18nDismiss;

    let incoming = null;

    const setJoinUi = () => {
        if (joinStatusEl) joinStatusEl.textContent = incoming ? ds.i18nReady : ds.i18nWaiting;
        if (joinBtn) joinBtn.disabled = !incoming || !ds.streamConfigured;
        if (dismissBtn) dismissBtn.classList.toggle('hidden', !incoming);
    };

    setJoinUi();

    dismissBtn?.addEventListener('click', () => {
        incoming = null;
        setJoinUi();
    });

    joinBtn?.addEventListener('click', () => {
        if (!incoming || !ds.callBaseUrl) return;
        const url = `${ds.callBaseUrl.replace(/\/$/, '')}/${encodeURIComponent(incoming.callType)}/${encodeURIComponent(incoming.callId)}`;
        window.location.assign(url);
    });

    if (!ds.userStreamId || !ds.streamConfigured || !ds.tokenUrl) return;

    let client = null;
    let offRing = null;
    let cancelled = false;

    (async () => {
        try {
            const data = await postJson(ds.tokenUrl);
            const user = { id: data.user.id, name: data.user.name, type: 'authenticated' };
            client = new StreamVideoClient({ apiKey: data.apiKey, user, token: data.token });
            await client.connectUser(user, data.token);
            if (cancelled) {
                await client.disconnectUser().catch(() => {});
                return;
            }

            offRing = client.on('call.ring', (event) => {
                const cid = event.call_cid || '';
                const i = cid.indexOf(':');
                if (i === -1) return;
                incoming = { callType: cid.slice(0, i), callId: cid.slice(i + 1) };
                setJoinUi();
            });
        } catch (e) {
            console.error(e);
        }
    })();

    window.addEventListener('beforeunload', () => {
        cancelled = true;
        offRing?.();
        if (client) client.disconnectUser().catch(() => {});
    });
}

boot();
