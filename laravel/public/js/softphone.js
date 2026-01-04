/**
 * WebRTC Softphone Widget - Asterisk Gateway Version
 * Connects to local Asterisk via WebSocket (ws://host:8088/ws)
 * Asterisk bridges to SIP Provider via UDP
 */

(function () {
    'use strict';

    // Configuration - Hardcoded for Test (User 1001)
    const SIP_CONFIG = {
        uri: 'sip:1001@' + window.location.hostname,
        wsServers: ['ws://' + window.location.hostname + ':8088/ws'],
        authorizationUser: '1001',
        password: 'webrtc_secret',
        displayName: 'Agent 1001'
    };

    // State
    let userAgent = null;
    let session = null;
    let isRegistered = false;
    let currentCall = null;
    let callTimer = null;
    let callStartTime = null;

    // Load SIP.js from CDN if not present
    if (typeof SIP === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sip.js@0.21.2/dist/sip.min.js';
        script.onload = init;
        document.head.appendChild(script);
    } else {
        init();
    }

    function createWidget() {
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
                }
                .sp-fab.online { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
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
                .sp-title { color: #f1f3f5; font-size: 14px; font-weight: 600; display: flex; align-items: center; }
                
                .sp-body { padding: 20px; color: #f1f3f5; }
                
                .sp-input-group { display: flex; gap: 8px; margin-bottom: 15px; }
                .sp-input { flex: 1; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 10px; border-radius: 8px; }
                .sp-dial-btn { width: 42px; background: #22c55e; border: none; border-radius: 8px; color: white; cursor: pointer; }
                
                .sp-incall-ui { display: none; text-align: center; }
                .sp-incall-ui.active { display: block; }
                .sp-number-display { font-size: 20px; font-weight: bold; margin-bottom: 5px; }
                .sp-timer { font-size: 32px; font-family: monospace; color: #22c55e; margin-bottom: 20px; }
                
                .sp-controls { display: flex; justify-content: center; gap: 15px; }
                .sp-ctrl-btn { width: 50px; height: 50px; border-radius: 50%; border: none; color: white; font-size: 18px; cursor: pointer; }
                .sp-btn-mute { background: rgba(255,255,255,0.1); }
                .sp-btn-hangup { background: #ef4444; }
                
                .sp-keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 15px; }
                .sp-key { background: rgba(255,255,255,0.05); border: none; padding: 10px; color: white; border-radius: 6px; cursor: pointer; }
                .sp-key:hover { background: rgba(255,255,255,0.1); }

                /* Audio elements hidden */
                audio { display: none; }
            </style>
            
            <button class="sp-fab" id="sp-fab"><i class="fas fa-phone"></i></button>
            
            <div class="sp-panel" id="sp-panel">
                <div class="sp-header">
                    <div class="sp-title">
                        <div class="sp-status-dot" id="sp-dot"></div>
                        <span id="sp-status-text">Disconnected</span>
                    </div>
                    <i class="fas fa-times" style="cursor:pointer; color:#94a3b8" id="sp-close"></i>
                </div>
                
                <div class="sp-body">
                    <div id="sp-dialpad-ui">
                        <div class="sp-input-group">
                            <input type="text" class="sp-input" id="sp-number-input" placeholder="Enter number...">
                            <button class="sp-dial-btn" id="sp-btn-dial"><i class="fas fa-phone"></i></button>
                        </div>
                        <div class="sp-keypad">
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '1'">1</button>
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '2'">2</button>
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '3'">3</button>
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '4'">4</button>
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '5'">5</button>
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '6'">6</button>
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '7'">7</button>
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '8'">8</button>
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '9'">9</button>
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '*'">*</button>
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '0'">0</button>
                            <button class="sp-key" onclick="document.getElementById('sp-number-input').value += '#'">#</button>
                        </div>
                    </div>
                    
                    <div class="sp-incall-ui" id="sp-incall-ui">
                        <div class="sp-number-display" id="sp-active-number">...</div>
                        <div class="sp-timer" id="sp-timer">00:00</div>
                        <div class="sp-controls">
                            <button class="sp-ctrl-btn sp-btn-mute" id="sp-btn-mute"><i class="fas fa-microphone"></i></button>
                            <button class="sp-ctrl-btn sp-btn-hangup" id="sp-btn-hangup"><i class="fas fa-phone-slash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            
            <audio id="sp-remote-audio" autoplay></audio>
            <audio id="sp-ringtone" loop src="https://upload.wikimedia.org/wikipedia/commons/e/e4/Old_Telephone_Ringing_Sound_Effect.ogg"></audio>
        `;
        document.body.appendChild(widget);

        // Event Listeners
        document.getElementById('sp-fab').onclick = () => document.getElementById('sp-panel').classList.toggle('open');
        document.getElementById('sp-close').onclick = () => document.getElementById('sp-panel').classList.remove('open');
        document.getElementById('sp-btn-dial').onclick = () => makeCall(document.getElementById('sp-number-input').value);
        document.getElementById('sp-btn-hangup').onclick = hangupCall;

        // Global hook for click-to-call
        window.callLead = function (lead) {
            document.getElementById('sp-panel').classList.add('open');
            document.getElementById('sp-number-input').value = lead.phone;
            makeCall(lead.phone);
        };
    }

    function init() {
        createWidget();
        setupSIP();
    }

    function setupSIP() {
        updateStatus('Connecting...', false);

        userAgent = new SIP.UserAgent({
            uri: SIP.UserAgent.makeURI(SIP_CONFIG.uri),
            transportOptions: {
                server: SIP_CONFIG.wsServers[0]
            },
            authorizationUsername: SIP_CONFIG.authorizationUser,
            authorizationPassword: SIP_CONFIG.password,
            displayName: SIP_CONFIG.displayName,
            register: true
        });

        userAgent.start().then(() => {
            isRegistered = true;
            updateStatus('Online', true);
        }).catch(err => {
            console.error('SIP Connect Error:', err);
            updateStatus('Connection Failed', false);
        });

        userAgent.delegate = {
            onInvite(invitation) {
                // Auto-answer logic or UI prompt could go here
                // For now, simple reject or manual answer
                // invitation.accept();
            }
        };
    }

    function makeCall(number) {
        if (!number || !isRegistered) return;

        const target = SIP.UserAgent.makeURI('sip:' + number + '@' + window.location.hostname);
        if (!target) return;

        const options = {
            sessionDescriptionHandlerOptions: {
                constraints: { audio: true, video: false }
            }
        };

        session = userAgent.invite(target, options);
        setupSession(session);
        currentCall = { number: number };

        showInCallUI(number);
    }

    function setupSession(newSession) {
        session = newSession;

        session.stateChange.addListener((state) => {
            console.log('Session State:', state);
            switch (state) {
                case SIP.SessionState.Establishing:
                    document.getElementById('sp-active-number').innerText = 'Calling...';
                    break;
                case SIP.SessionState.Established:
                    const remoteStream = new MediaStream();
                    session.sessionDescriptionHandler.peerConnection.getReceivers().forEach((receiver) => {
                        if (receiver.track) remoteStream.addTrack(receiver.track);
                    });
                    document.getElementById('sp-remote-audio').srcObject = remoteStream;
                    document.getElementById('sp-remote-audio').play();
                    startTimer();
                    break;
                case SIP.SessionState.Terminated:
                    stopTimer();
                    showDialpadUI();
                    session = null;
                    break;
            }
        });
    }

    function hangupCall() {
        if (session) {
            session.bye(); // or session.terminate()
        }
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

    function updateStatus(text, online) {
        document.getElementById('sp-status-text').innerText = text;
        const dot = document.getElementById('sp-dot');
        if (online) {
            dot.classList.add('connected');
            document.getElementById('sp-fab').classList.add('online');
        } else {
            dot.classList.remove('connected');
            document.getElementById('sp-fab').classList.remove('online');
        }
    }

    function startTimer() {
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

})();
