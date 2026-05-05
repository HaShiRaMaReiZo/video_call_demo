import {
    SfuModels,
    StreamVideoClient,
} from 'https://cdn.jsdelivr.net/npm/@stream-io/video-client@1.48.0/dist/index.browser.es.js';

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
    if (!res.ok) throw new Error(data?.message || 'Request failed');
    return data;
}

function setText(el, value) { if (el) el.textContent = value; }
function showError(el, message) { if (el) { el.textContent = message; el.classList.remove('hidden'); } }
function clearError(el) { if (el) { el.textContent = ''; el.classList.add('hidden'); } }

function setStatus(statusEl, text, type) {
    if (!statusEl) return;
    statusEl.textContent = text;
    statusEl.classList.remove('call-status--connecting', 'call-status--ready', 'call-status--warn');
    statusEl.classList.add(`call-status--${type}`);
}

function pickRemoteParticipant(parts) {
    if (!parts?.length) return undefined;
    const withVideo = parts.find((r) => r.publishedTracks?.includes(SfuModels.TrackType.VIDEO));
    return withVideo ?? parts[0];
}

function prepareVideoElement(videoEl, muted) {
    if (!videoEl) return;
    videoEl.autoplay = true;
    videoEl.playsInline = true;
    videoEl.muted = muted;
}

function tryPlay(videoEl) {
    if (!videoEl) return;
    const p = videoEl.play?.();
    if (p && typeof p.catch === 'function') p.catch(() => {});
}

function setControlActive(button, active) {
    if (button) button.classList.toggle('call-btn--active', active);
}

function boot() {
    const root = document.getElementById('active-call-root');
    if (!root) return;

    const tokenUrl = root.dataset.tokenUrl;
    const dashboardUrl = root.dataset.dashboardUrl;
    const callType = root.dataset.callType;
    const callId = root.dataset.callId;
    const errEl = document.getElementById('active-call-error');
    const uiEl = document.getElementById('active-call-ui');
    const statusEl = document.getElementById('active-call-status');
    const localVideo = document.getElementById('active-call-local');
    const remoteVideo = document.getElementById('active-call-remote');
    const remoteEmptyEl = document.getElementById('active-call-remote-empty');
    const btnMic = document.getElementById('active-call-toggle-mic');
    const btnCam = document.getElementById('active-call-toggle-camera');
    const btnLeave = document.getElementById('active-call-leave');
    const localNameEl = document.getElementById('active-call-local-name');
    const remoteNameEl = document.getElementById('active-call-remote-name');

    setText(localNameEl, 'You');
    setText(remoteNameEl, 'Remote');
    uiEl?.classList.remove('hidden');
    setStatus(statusEl, 'Connecting...', 'connecting');
    prepareVideoElement(localVideo, true);
    prepareVideoElement(remoteVideo, true);

    let client = null;
    let call = null;
    const subs = [];
    let unbindLocal = null;
    let unbindRemote = null;
    let unbindViewport = null;
    let localSessionId = null;
    let remoteSessionId = null;

    const updateControlStates = () => {
        setControlActive(btnMic, Boolean(call?.microphone?.state?.enabled));
        setControlActive(btnCam, Boolean(call?.camera?.state?.enabled));
    };

    const setRemoteEmpty = (show, text = 'Waiting for remote camera...') => {
        if (!remoteEmptyEl) return;
        setText(remoteEmptyEl, text);
        remoteEmptyEl.classList.toggle('hidden', !show);
    };

    const releaseBindings = () => {
        if (typeof unbindLocal === 'function') unbindLocal();
        if (typeof unbindRemote === 'function') unbindRemote();
        if (typeof unbindViewport === 'function') unbindViewport();
        unbindLocal = null;
        unbindRemote = null;
        unbindViewport = null;
        localSessionId = null;
        remoteSessionId = null;
        if (localVideo) localVideo.srcObject = null;
        if (remoteVideo) remoteVideo.srcObject = null;
    };

    const bindLocal = (c, participant) => {
        const displayName = participant?.name || participant?.userId || 'You';
        setText(localNameEl, `You: ${displayName}`);
        const nextSessionId = participant?.sessionId ?? null;
        if (!nextSessionId || !localVideo) return;
        if (nextSessionId === localSessionId) return void tryPlay(localVideo);
        if (typeof unbindLocal === 'function') unbindLocal();
        const fn = c.bindVideoElement(localVideo, nextSessionId, 'videoTrack');
        if (typeof fn === 'function') unbindLocal = fn;
        localSessionId = nextSessionId;
        tryPlay(localVideo);
    };

    const bindRemote = (c, participants) => {
        const remote = pickRemoteParticipant(participants);
        const displayName = remote?.name || remote?.userId || 'Remote';
        setText(remoteNameEl, remote ? `Remote: ${displayName}` : 'Remote');
        const nextSessionId = remote?.sessionId ?? null;
        if (!nextSessionId || !remoteVideo) {
            setRemoteEmpty(true, participants?.length ? 'Remote is connected without video.' : 'Waiting for participant to join...');
            return;
        }
        if (nextSessionId === remoteSessionId) {
            setRemoteEmpty(false);
            return void tryPlay(remoteVideo);
        }
        if (typeof unbindRemote === 'function') unbindRemote();
        const fn = c.bindVideoElement(remoteVideo, nextSessionId, 'videoTrack');
        if (typeof fn === 'function') unbindRemote = fn;
        remoteSessionId = nextSessionId;
        setRemoteEmpty(false);
        tryPlay(remoteVideo);
    };

    const cleanup = async () => {
        subs.splice(0).forEach((u) => u.unsubscribe?.());
        releaseBindings();
        try { await call?.leave?.(); } catch {}
        call = null;
        try { await client?.disconnectUser?.(); } catch {}
        client = null;
    };

    const leave = async () => {
        setStatus(statusEl, 'Leaving call...', 'connecting');
        await cleanup();
        if (dashboardUrl) window.location.assign(dashboardUrl);
    };

    btnLeave?.addEventListener('click', () => { leave().catch(() => {}); });

    (async () => {
        try {
            const data = await postJson(tokenUrl);
            const user = { id: data.user.id, name: data.user.name, type: 'authenticated' };
            const videoClient = new StreamVideoClient({ apiKey: data.apiKey, user, token: data.token });
            await videoClient.connectUser(user, data.token);
            const c = videoClient.call(callType, callId);
            await c.join({ create: true });
            if (uiEl) unbindViewport = c.setViewport(uiEl);
            await Promise.all([c.microphone.enable().catch(() => {}), c.camera.enable().catch(() => {})]);

            client = videoClient;
            call = c;
            clearError(errEl);
            setStatus(statusEl, 'Connected', 'ready');
            updateControlStates();
            bindLocal(c, c.state.localParticipant);
            bindRemote(c, c.state.remoteParticipants);
            subs.push(c.state.localParticipant$.subscribe((p) => bindLocal(c, p)));
            subs.push(c.state.remoteParticipants$.subscribe((parts) => bindRemote(c, parts)));
            subs.push(c.microphone.state.status$.subscribe(updateControlStates));
            subs.push(c.camera.state.status$.subscribe(updateControlStates));

            btnMic?.addEventListener('click', () => c.microphone.toggle().catch(() => {}));
            btnCam?.addEventListener('click', () => c.camera.toggle().catch(() => {}));
        } catch (e) {
            const msg = e?.message || 'Could not join the call.';
            showError(errEl, msg);
            setStatus(statusEl, 'Connection issue', 'warn');
            setRemoteEmpty(true, 'Unable to subscribe to remote video.');
        }
    })();

    window.addEventListener('beforeunload', () => { cleanup().catch(() => {}); });
}

boot();
