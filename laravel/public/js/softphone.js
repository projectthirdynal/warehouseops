/**
 * Hybrid Softphone Widget
 * 1. Tries WebRTC (Asterisk Gateway) first.
 * 2. Falls back to Manual Mode if WebRTC fails (e.g. no HTTPS).
 */

(function () {
    'use strict';

    // Configuration
    let SIP_CONFIG = null;
    let sipUser = 'Unknown';

    if (window.sipConfig) {
        SIP_CONFIG = window.sipConfig;

        sipUser = SIP_CONFIG.authorizationUsername;

        sipUser = SIP_CONFIG.authorizationUsername;

        // Start Heartbeat
        startHeartbeat();
    } else {
        console.warn('Softphone: No SIP Config found for this user.');
    }

    let mode = 'manual'; // 'webrtc' or 'manual'
    let userAgent = null;
    let session = null;
    let isRegistered = false;
    let currentCall = null;
    let callTimer = null;
    let callStartTime = null;

    // Initialize UI immediately
    initWidget();

    // Load SIP.js
    if (typeof SIP === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sip.js@0.21.2/dist/sip.min.js';
        script.onload = checkCapability;
        script.onerror = () => {
            console.error('SIP Library Failed to Load');
            updateStatus('Library Error', 'manual');
            mode = 'manual';
        };
        document.head.appendChild(script);
    } else {
        checkCapability();
    }

    function checkCapability() {
        // WebRTC requires HTTPS or Localhost
        const isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';

        if (isSecure && navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            console.log('Softphone: WebRTC supported. Attempting connection...');
            mode = 'webrtc';
            updateStatus('Connecting...', false);
            setupSIP();
        } else {
            console.warn('Softphone: WebRTC not supported (Need HTTPS). Falling back to Manual Mode.');
            mode = 'manual';
            updateStatus('Manual Mode', 'manual');

            // Show hint immediately in manual mode
            if (document.getElementById('sp-info-text')) {
                document.getElementById('sp-info-text').innerText = 'WebRTC Disabled (HTTPS required). Using Manual Logging.';
                document.getElementById('sp-manual-hint').style.display = 'block';
                document.getElementById('sp-keypad').style.display = 'none';
            }
        }
    }
}

    function initWidget() {
    if (document.getElementById('softphone-widget')) return;

    const widget = document.createElement('div');
    widget.id = 'softphone-widget';
    widget.innerHTML = `
            <style>
                #softphone-widget {
                    position: fixed;
                    bottom: 24px;
                    right: 24px;
                    z-index: 9999;
                    font-family: inherit;
                }
                .sp-fab {
                    width: 56px;
                    height: 56px;
                    border-radius: 50%;
                    background: #64748b;
                    border: none;
                    color: white;
                    font-size: 20px;
                    cursor: pointer;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .sp-fab.online { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
                .sp-fab.manual { background: linear-gradient(135deg, #0ea5e9, #38bdf8); }
                .sp-fab.incall { background: linear-gradient(135deg, #22c55e, #16a34a); animation: sp-pulse 2s infinite; }
                @keyframes sp-pulse { 0% { box-shadow: 0 0 0 0 rgba(34,197,94,0.4); } 70% { box-shadow: 0 0 0 15px rgba(34,197,94,0); } 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); } }
                
                .sp-panel {
                    position: absolute;
                    bottom: 70px;
                    right: 0;
                    width: 300px;
                    background: #1e293b;
                    border: 1px solid rgba(255,255,255,0.1);
                    border-radius: 12px;
                    display: none;
                    overflow: hidden;
                    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
                }
                .sp-panel.open { display: block; }
                
                .sp-header {
                    padding: 15px;
                    background: rgba(255,255,255,0.05);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 1px solid rgba(255,255,255,0.1);
                }
                .sp-status-dot { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; margin-right: 8px; }
                .sp-status-dot.connected { background: #22c55e; }
                .sp-status-dot.manual { background: #38bdf8; }
                
                .sp-title { color: #f1f3f5; font-size: 14px; font-weight: 600; display: flex; align-items: center; }
                
                .sp-body { padding: 20px; color: #f1f3f5; }
                
                .sp-info { font-size: 12px; color: #94a3b8; text-align: center; margin-bottom: 15px; }
                
                .sp-input-group { display: flex; gap: 8px; margin-bottom: 15px; }
                .sp-input { flex: 1; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 10px; border-radius: 8px; }
                .sp-dial-btn { width: 42px; background: #22c55e; border: none; border-radius: 8px; color: white; cursor: pointer; }
                
                .sp-incall-ui { display: none; text-align: center; }
                .sp-incall-ui.active { display: block; }
                .sp-number-display { font-size: 20px; font-weight: bold; margin-bottom: 5px; }
                .sp-timer { font-size: 32px; font-family: monospace; color: #22c55e; margin-bottom: 20px; }
                .sp-manual-hint { font-size: 11px; color: #cbd5e1; margin-bottom: 10px; display: none; }
                
                .sp-controls { display: flex; justify-content: center; gap: 15px; }
                .sp-ctrl-btn { width: 50px; height: 50px; border-radius: 50%; border: none; color: white; font-size: 18px; cursor: pointer; }
                .sp-btn-mute { background: rgba(255,255,255,0.1); }
                .sp-btn-hangup { background: #ef4444; }
                
                .sp-keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 15px; }
                .sp-key { background: rgba(255,255,255,0.05); border: none; padding: 10px; color: white; border-radius: 6px; cursor: pointer; }
                .sp-key:hover { background: rgba(255,255,255,0.1); }
                
                audio { display: none; }
            </style>
            
            <button class="sp-fab" id="sp-fab"><i class="fas fa-phone"></i></button>
            
            <div class="sp-panel" id="sp-panel">
                <div class="sp-header">
                    <div class="sp-title">
                        <div class="sp-status-dot" id="sp-dot"></div>
                        <span id="sp-status-text">Initializing...</span>
                    </div>
                    <i class="fas fa-times" style="cursor:pointer; color:#94a3b8" id="sp-close"></i>
                </div>
                
                <div class="sp-body">
                    <div class="sp-info" id="sp-info-text"></div>
                    
                    <div id="sp-dialpad-ui">
                        <div class="sp-input-group">
                            <input type="text" class="sp-input" id="sp-number-input" placeholder="Enter number...">
                            <button class="sp-dial-btn" id="sp-btn-dial"><i class="fas fa-phone"></i></button>
                        </div>
                        <div class="sp-keypad" id="sp-keypad">
                            <button class="sp-key">1</button><button class="sp-key">2</button><button class="sp-key">3</button>
                            <button class="sp-key">4</button><button class="sp-key">5</button><button class="sp-key">6</button>
                            <button class="sp-key">7</button><button class="sp-key">8</button><button class="sp-key">9</button>
                            <button class="sp-key">*</button><button class="sp-key">0</button><button class="sp-key">#</button>
                        </div>
                    </div>
                    
                    <div class="sp-incall-ui" id="sp-incall-ui">
                        <div class="sp-number-display" id="sp-active-number">...</div>
                        <div class="sp-manual-hint" id="sp-manual-hint">Dial this number on MicroSIP</div>
                        <div class="sp-timer" id="sp-timer">00:00</div>
                        <div class="sp-controls">
                            <button class="sp-ctrl-btn sp-btn-mute" id="sp-btn-mute"><i class="fas fa-microphone"></i></button>
                            <button class="sp-ctrl-btn sp-btn-hangup" id="sp-btn-hangup"><i class="fas fa-phone-slash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            
            <audio id="sp-remote-audio" autoplay></audio>
        `;
    document.body.appendChild(widget);

    // Events
    document.getElementById('sp-fab').onclick = () => document.getElementById('sp-panel').classList.toggle('open');
    document.getElementById('sp-close').onclick = () => document.getElementById('sp-panel').classList.remove('open');
    document.getElementById('sp-btn-dial').onclick = () => startCall(document.getElementById('sp-number-input').value);
    document.getElementById('sp-btn-hangup').onclick = endCall;

    document.querySelectorAll('.sp-key').forEach(btn => {
        btn.onclick = () => document.getElementById('sp-number-input').value += btn.innerText;
    });

    if (mode === 'manual') {
        updateStatus('Manual Mode', 'manual');
        document.getElementById('sp-info-text').innerText = 'WebRTC Disabled (HTTPS required). Using Manual Logging.';
        document.getElementById('sp-manual-hint').style.display = 'block';
        document.getElementById('sp-keypad').style.display = 'none'; // No keys needed for manual
    }

    // Expose global
    window.callLead = function (lead) {
        document.getElementById('sp-panel').classList.add('open');
        document.getElementById('sp-number-input').value = lead.phone;
        startCall(lead.phone);
    };
}

function setupSIP() {
    updateStatus('Connecting SIP...', false);
    try {
        userAgent = new SIP.UserAgent({
            uri: SIP.UserAgent.makeURI(SIP_CONFIG.uri),
            transportOptions: { server: SIP_CONFIG.wsServers[0] },
            authorizationUsername: SIP_CONFIG.authorizationUser,
            authorizationPassword: SIP_CONFIG.password,
            displayName: SIP_CONFIG.displayName,
            register: true
        });

        userAgent.start().then(() => {
            isRegistered = true;
            updateStatus('Online (' + sipUser + ')', true);
        }).catch(err => {
            console.error('SIP Connect Error:', err);
            updateStatus('SIP Error - Local Mode', 'manual');
            mode = 'manual';
        });
    } catch (e) {
        console.error('SIP Init Error:', e);
        updateStatus('SIP Failed', 'manual');
        mode = 'manual';
    }
}

function startCall(number) {
    if (!number) return;
    currentCall = { number: number };

    showInCallUI(number);
    startTimer(); // Start timer immediately for UX

    if (mode === 'webrtc' && isRegistered) {
        // SIP Logic
        const target = SIP.UserAgent.makeURI('sip:' + number + '@' + window.location.hostname);
        const options = { sessionDescriptionHandlerOptions: { constraints: { audio: true, video: false } } };
        session = userAgent.invite(target, options);
        setupSession(session);
    } else {
        // Manual Logic
        copyToClipboard(number);
        console.log('Manual mode: Copied ' + number);
    }
}

function setupSession(newSession) {
    session = newSession;
    session.stateChange.addListener((state) => {
        if (state === SIP.SessionState.Terminated) {
            endCall();
        } else if (state === SIP.SessionState.Established) {
            const remoteStream = new MediaStream();
            session.sessionDescriptionHandler.peerConnection.getReceivers().forEach((receiver) => {
                if (receiver.track) remoteStream.addTrack(receiver.track);
            });
            document.getElementById('sp-remote-audio').srcObject = remoteStream;
            document.getElementById('sp-remote-audio').play();
        }
    });
}

function endCall() {
    if (session && mode === 'webrtc') {
        session.bye();
        session = null;
    }

    stopTimer();
    showDialpadUI();
    // Here we could prompt for outcome
}

function showInCallUI(number) {
    document.getElementById('sp-fab').classList.add('incall');
    document.getElementById('sp-dialpad-ui').style.display = 'none';
    document.getElementById('sp-incall-ui').classList.add('active');
    document.getElementById('sp-active-number').innerText = number;
}

function showDialpadUI() {
    document.getElementById('sp-fab').classList.remove('incall');
    document.getElementById('sp-incall-ui').classList.remove('active');
    document.getElementById('sp-dialpad-ui').style.display = 'block';
}

function updateStatus(text, type) {
    document.getElementById('sp-status-text').innerText = text;
    const dot = document.getElementById('sp-dot');
    dot.className = 'sp-status-dot';
    document.getElementById('sp-fab').className = 'sp-fab';

    if (type === true) {
        dot.classList.add('connected');
        document.getElementById('sp-fab').classList.add('online');
    } else if (type === 'manual') {
        dot.classList.add('manual');
        document.getElementById('sp-fab').classList.add('manual');
    }
}

function startTimer() {
    if (callTimer) clearInterval(callTimer);
    callStartTime = Date.now();
    callTimer = setInterval(() => {
        const delta = Math.floor((Date.now() - callStartTime) / 1000);
        const m = Math.floor(delta / 60).toString().padStart(2, '0');
        const s = (delta % 60).toString().padStart(2, '0');
        document.getElementById('sp-timer').innerText = `${m}:${s}`;
    }, 1000);
}

function stopTimer() {
    if (callTimer) clearInterval(callTimer);
    callTimer = null;
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
    } else {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }
}
    }

function startHeartbeat() {
    setInterval(() => {
        const status = currentCall ? 'busy' : 'online';
        const lead = currentCall ? (currentCall.name || currentCall.number) : null;

        fetch('/monitoring/heartbeat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status, lead })
        }).catch(e => console.error('Heartbeat failed', e));
    }, 30000); // 30 seconds
}
}) ();
