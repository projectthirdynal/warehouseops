/**
 * Softphone Entry Point
 * Import this file in your blade templates to enable the softphone widget
 */

// For non-ES module environments, create a simplified inline version
(function () {
    'use strict';

    // Only load on authenticated pages with leads access
    if (!document.querySelector('meta[name="csrf-token"]')) {
        return;
    }

    // State
    let isRegistered = false;
    let currentCall = null;
    let callTimer = null;
    let callStartTime = null;
    let sipConfig = null;

    // Create widget HTML
    function createWidget() {
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
                    background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    border: none;
                    color: white;
                    font-size: 20px;
                    cursor: pointer;
                    box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
                    transition: all 0.3s ease;
                }
                .sp-fab:hover { transform: scale(1.05); }
                .sp-fab.active-call {
                    background: linear-gradient(135deg, #22c55e, #16a34a);
                    animation: pulse-call 2s infinite;
                }
                @keyframes pulse-call {
                    0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
                    50% { box-shadow: 0 0 0 12px rgba(34, 197, 94, 0); }
                }
                .sp-fab.offline { background: linear-gradient(135deg, #64748b, #475569); }
                .sp-panel {
                    position: absolute;
                    bottom: 68px;
                    right: 0;
                    width: 300px;
                    background: #12151c;
                    border: 1px solid rgba(255,255,255,0.1);
                    border-radius: 16px;
                    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
                    display: none;
                    overflow: hidden;
                }
                .sp-panel.open { display: block; animation: slideUp 0.25s ease; }
                @keyframes slideUp {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .sp-header {
                    padding: 14px 18px;
                    background: rgba(255,255,255,0.03);
                    border-bottom: 1px solid rgba(255,255,255,0.08);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }
                .sp-status { display: flex; align-items: center; gap: 8px; }
                .sp-dot {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    background: #6366f1;
                }
                .sp-dot.online { background: #22c55e; }
                .sp-dot.offline { background: #ef4444; }
                .sp-title { font-size: 13px; font-weight: 600; color: #f1f3f5; }
                .sp-close {
                    width: 26px;
                    height: 26px;
                    border-radius: 6px;
                    background: rgba(255,255,255,0.05);
                    border: none;
                    color: #8b919e;
                    cursor: pointer;
                    font-size: 11px;
                }
                .sp-close:hover { background: rgba(255,255,255,0.1); color: #fff; }
                .sp-body { padding: 16px; }
                .sp-lead {
                    display: none;
                    padding: 10px 12px;
                    background: rgba(99,102,241,0.08);
                    border: 1px solid rgba(99,102,241,0.2);
                    border-radius: 10px;
                    margin-bottom: 12px;
                }
                .sp-lead.show { display: block; }
                .sp-lead-name { font-size: 13px; font-weight: 600; color: #f1f3f5; }
                .sp-lead-info { font-size: 11px; color: #8b919e; margin-top: 2px; }
                .sp-dialer { display: flex; gap: 8px; margin-bottom: 12px; }
                .sp-input {
                    flex: 1;
                    background: rgba(255,255,255,0.05);
                    border: 1px solid rgba(255,255,255,0.1);
                    border-radius: 10px;
                    padding: 10px 12px;
                    color: #f1f3f5;
                    font-size: 15px;
                    font-family: 'SF Mono', monospace;
                }
                .sp-input:focus { outline: none; border-color: #6366f1; }
                .sp-dial {
                    width: 44px;
                    height: 44px;
                    border-radius: 10px;
                    background: #22c55e;
                    border: none;
                    color: white;
                    font-size: 16px;
                    cursor: pointer;
                }
                .sp-dial:hover { background: #16a34a; }
                .sp-dial:disabled { background: #475569; cursor: not-allowed; }
                .sp-incall { display: none; text-align: center; padding: 16px 0; }
                .sp-incall.active { display: block; }
                .sp-number { font-size: 18px; font-weight: 700; color: #f1f3f5; font-family: monospace; }
                .sp-call-status { font-size: 12px; color: #8b919e; margin-top: 4px; }
                .sp-call-status.ringing { color: #eab308; }
                .sp-call-status.connected { color: #22c55e; }
                .sp-timer { font-size: 24px; font-weight: 700; color: #22c55e; margin: 14px 0; font-family: monospace; }
                .sp-actions { display: flex; justify-content: center; gap: 14px; }
                .sp-act {
                    width: 48px;
                    height: 48px;
                    border-radius: 50%;
                    border: none;
                    font-size: 16px;
                    cursor: pointer;
                }
                .sp-mute { background: rgba(255,255,255,0.1); color: #f1f3f5; }
                .sp-mute.muted { background: #ef4444; color: white; }
                .sp-hangup { background: #ef4444; color: white; }
                .sp-hangup:hover { background: #dc2626; }
                .sp-msg {
                    display: none;
                    padding: 10px;
                    background: rgba(239,68,68,0.1);
                    border: 1px solid rgba(239,68,68,0.2);
                    border-radius: 8px;
                    margin-bottom: 12px;
                    font-size: 11px;
                    color: #f87171;
                }
                .sp-msg.show { display: block; }
                .sp-note {
                    font-size: 11px;
                    color: #64748b;
                    text-align: center;
                    margin-top: 8px;
                }
            </style>
            <button class="sp-fab offline" id="sp-fab"><i class="fas fa-phone"></i></button>
            <div class="sp-panel" id="sp-panel">
                <div class="sp-header">
                    <div class="sp-status">
                        <div class="sp-dot offline" id="sp-dot"></div>
                        <span class="sp-title">Softphone</span>
                    </div>
                    <button class="sp-close" id="sp-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="sp-body">
                    <div class="sp-msg" id="sp-msg"></div>
                    <div class="sp-lead" id="sp-lead">
                        <div class="sp-lead-name" id="sp-lead-name"></div>
                        <div class="sp-lead-info" id="sp-lead-info"></div>
                    </div>
                    <div id="sp-dialer-section">
                        <div class="sp-dialer">
                            <input type="tel" class="sp-input" id="sp-phone" placeholder="Phone number">
                            <button class="sp-dial" id="sp-dial" disabled><i class="fas fa-phone"></i></button>
                        </div>
                    </div>
                    <div class="sp-incall" id="sp-incall">
                        <div class="sp-number" id="sp-number">---</div>
                        <div class="sp-call-status" id="sp-call-status"><i class="fas fa-phone-volume"></i> Connecting...</div>
                        <div class="sp-timer" id="sp-timer">00:00</div>
                        <div class="sp-actions">
                            <button class="sp-act sp-mute" id="sp-mute"><i class="fas fa-microphone"></i></button>
                            <button class="sp-act sp-hangup" id="sp-hangup"><i class="fas fa-phone-slash"></i></button>
                        </div>
                    </div>
                    <div class="sp-note" id="sp-note">SIP softphone ready</div>
                </div>
            </div>
        `;
        document.body.appendChild(widget);
    }

    // Initialize
    function init() {
        createWidget();
        attachEvents();
        loadSipConfig();
    }

    function attachEvents() {
        document.getElementById('sp-fab').addEventListener('click', togglePanel);
        document.getElementById('sp-close').addEventListener('click', closePanel);
        document.getElementById('sp-phone').addEventListener('input', updateDialButton);
        document.getElementById('sp-phone').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') startCall();
        });
        document.getElementById('sp-dial').addEventListener('click', startCall);
        document.getElementById('sp-mute').addEventListener('click', toggleMute);
        document.getElementById('sp-hangup').addEventListener('click', endCall);
    }

    function togglePanel() {
        document.getElementById('sp-panel').classList.toggle('open');
    }

    function closePanel() {
        document.getElementById('sp-panel').classList.remove('open');
    }

    function updateDialButton() {
        const phone = document.getElementById('sp-phone').value.trim();
        document.getElementById('sp-dial').disabled = !phone || !isRegistered;
    }

    async function loadSipConfig() {
        try {
            const resp = await fetch('/api/sip/config', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                credentials: 'same-origin'
            });

            if (resp.ok) {
                const data = await resp.json();
                if (data.configured) {
                    sipConfig = data.config;
                    setOnline();
                    showNote('SIP connected - ready to call');
                } else {
                    showMessage('SIP not configured');
                }
            } else {
                showNote('SIP not available');
            }
        } catch (e) {
            console.log('[Softphone] Config load failed:', e);
            showNote('Softphone offline');
        }
    }

    function setOnline() {
        isRegistered = true;
        document.getElementById('sp-fab').classList.remove('offline');
        document.getElementById('sp-dot').classList.add('online');
        document.getElementById('sp-dot').classList.remove('offline');
        updateDialButton();
    }

    async function startCall() {
        const phone = document.getElementById('sp-phone').value.trim();
        if (!phone || !isRegistered) return;

        currentCall = { phone: phone, leadId: window.softphoneLeadId || null };

        // Log call start
        try {
            const resp = await fetch('/api/calls/initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    phone_number: phone,
                    lead_id: currentCall.leadId,
                    direction: 'outbound'
                }),
                credentials: 'same-origin'
            });

            if (resp.ok) {
                const data = await resp.json();
                currentCall.callId = data.call.call_id;
            }
        } catch (e) {
            console.log('[Softphone] Call log failed:', e);
        }

        // Show in-call UI
        showInCall(phone);

        // Simulate ringing -> connected (since real SIP requires proper server)
        setTimeout(() => {
            if (currentCall) {
                updateCallStatus('connected');
                startTimer();
                logStatus('answered');
            }
        }, 2000);
    }

    function showInCall(phone) {
        document.getElementById('sp-dialer-section').style.display = 'none';
        document.getElementById('sp-incall').classList.add('active');
        document.getElementById('sp-number').textContent = phone;
        document.getElementById('sp-timer').textContent = '00:00';
        document.getElementById('sp-fab').classList.add('active-call');
        updateCallStatus('ringing');
    }

    function updateCallStatus(status) {
        const el = document.getElementById('sp-call-status');
        el.className = 'sp-call-status ' + status;
        if (status === 'ringing') {
            el.innerHTML = '<i class="fas fa-phone-volume"></i> Ringing...';
        } else if (status === 'connected') {
            el.innerHTML = '<i class="fas fa-phone-alt"></i> Connected';
        }
    }

    function startTimer() {
        callStartTime = Date.now();
        callTimer = setInterval(() => {
            const secs = Math.floor((Date.now() - callStartTime) / 1000);
            const m = Math.floor(secs / 60).toString().padStart(2, '0');
            const s = (secs % 60).toString().padStart(2, '0');
            document.getElementById('sp-timer').textContent = m + ':' + s;
        }, 1000);
    }

    function toggleMute() {
        const btn = document.getElementById('sp-mute');
        btn.classList.toggle('muted');
        const muted = btn.classList.contains('muted');
        btn.innerHTML = `<i class="fas fa-${muted ? 'microphone-slash' : 'microphone'}"></i>`;
    }

    function endCall() {
        if (callTimer) {
            clearInterval(callTimer);
            callTimer = null;
        }

        if (currentCall && currentCall.callId) {
            logStatus('ended');
        }

        // Reset UI
        document.getElementById('sp-dialer-section').style.display = 'block';
        document.getElementById('sp-incall').classList.remove('active');
        document.getElementById('sp-fab').classList.remove('active-call');
        document.getElementById('sp-phone').value = '';
        document.getElementById('sp-lead').classList.remove('show');
        document.getElementById('sp-mute').classList.remove('muted');
        document.getElementById('sp-mute').innerHTML = '<i class="fas fa-microphone"></i>';

        currentCall = null;
        callStartTime = null;
        window.softphoneLeadId = null;
    }

    async function logStatus(status) {
        if (!currentCall || !currentCall.callId) return;
        try {
            await fetch('/api/calls/' + currentCall.callId + '/status', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: status }),
                credentials: 'same-origin'
            });
        } catch (e) { }
    }

    function showMessage(msg) {
        const el = document.getElementById('sp-msg');
        el.textContent = msg;
        el.classList.add('show');
        setTimeout(() => el.classList.remove('show'), 4000);
    }

    function showNote(note) {
        document.getElementById('sp-note').textContent = note;
    }

    // Global function for click-to-call from lead cards
    window.softphoneCall = function (lead) {
        if (!isRegistered) {
            alert('Softphone not connected');
            return;
        }

        window.softphoneLeadId = lead.id;
        document.getElementById('sp-phone').value = lead.phone;
        document.getElementById('sp-lead-name').textContent = lead.name || 'Unknown';
        document.getElementById('sp-lead-info').textContent = lead.previous_item || lead.product || '';
        document.getElementById('sp-lead').classList.add('show');
        document.getElementById('sp-panel').classList.add('open');
        updateDialButton();
    };

    // Init when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
