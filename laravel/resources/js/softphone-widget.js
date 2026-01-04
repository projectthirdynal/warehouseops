/**
 * Softphone Widget - Floating UI Component
 * Provides click-to-call interface for CRM
 */

import { softphone } from './softphone.js';

class SoftphoneWidget {
    constructor() {
        this.isMinimized = true;
        this.isConfigured = false;
        this.currentLead = null;

        this._createWidget();
        this._attachEventListeners();
        this._initSoftphone();
    }

    /**
     * Create the widget HTML
     */
    _createWidget() {
        const widget = document.createElement('div');
        widget.id = 'softphone-widget';
        widget.innerHTML = `
            <style>
                #softphone-widget {
                    position: fixed;
                    bottom: 24px;
                    right: 24px;
                    z-index: 9999;
                    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
                }

                .sp-fab {
                    width: 56px;
                    height: 56px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    border: none;
                    color: white;
                    font-size: 22px;
                    cursor: pointer;
                    box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .sp-fab:hover {
                    transform: scale(1.05);
                    box-shadow: 0 6px 30px rgba(99, 102, 241, 0.5);
                }

                .sp-fab.active-call {
                    background: linear-gradient(135deg, #22c55e, #16a34a);
                    animation: pulse-green 2s infinite;
                }

                @keyframes pulse-green {
                    0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
                    50% { box-shadow: 0 0 0 15px rgba(34, 197, 94, 0); }
                }

                .sp-fab.not-registered {
                    background: linear-gradient(135deg, #64748b, #475569);
                }

                .sp-panel {
                    position: absolute;
                    bottom: 70px;
                    right: 0;
                    width: 320px;
                    background: #12151c;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 16px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                    display: none;
                    overflow: hidden;
                }

                .sp-panel.open {
                    display: block;
                    animation: slideUp 0.3s ease;
                }

                @keyframes slideUp {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .sp-header {
                    padding: 16px 20px;
                    background: rgba(255, 255, 255, 0.03);
                    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .sp-header-left {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .sp-status-dot {
                    width: 10px;
                    height: 10px;
                    border-radius: 50%;
                    background: #6366f1;
                }

                .sp-status-dot.registered { background: #22c55e; }
                .sp-status-dot.disconnected { background: #ef4444; }
                .sp-status-dot.connecting { background: #eab308; animation: blink 1s infinite; }

                @keyframes blink {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.3; }
                }

                .sp-title {
                    font-size: 14px;
                    font-weight: 600;
                    color: #f1f3f5;
                }

                .sp-close {
                    width: 28px;
                    height: 28px;
                    border-radius: 8px;
                    background: rgba(255, 255, 255, 0.05);
                    border: none;
                    color: #8b919e;
                    cursor: pointer;
                    font-size: 12px;
                }

                .sp-close:hover {
                    background: rgba(255, 255, 255, 0.1);
                    color: #f1f3f5;
                }

                .sp-body {
                    padding: 20px;
                }

                /* Dialer */
                .sp-dialer {
                    margin-bottom: 16px;
                }

                .sp-input-group {
                    display: flex;
                    gap: 8px;
                }

                .sp-phone-input {
                    flex: 1;
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 10px;
                    padding: 12px 14px;
                    color: #f1f3f5;
                    font-size: 16px;
                    font-family: 'SF Mono', monospace;
                }

                .sp-phone-input:focus {
                    outline: none;
                    border-color: #6366f1;
                }

                .sp-call-btn {
                    width: 48px;
                    height: 48px;
                    border-radius: 10px;
                    background: #22c55e;
                    border: none;
                    color: white;
                    font-size: 18px;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .sp-call-btn:hover {
                    background: #16a34a;
                }

                .sp-call-btn:disabled {
                    background: #475569;
                    cursor: not-allowed;
                }

                /* In Call UI */
                .sp-incall {
                    display: none;
                    text-align: center;
                    padding: 20px 0;
                }

                .sp-incall.active {
                    display: block;
                }

                .sp-call-info {
                    margin-bottom: 20px;
                }

                .sp-call-number {
                    font-size: 22px;
                    font-weight: 700;
                    color: #f1f3f5;
                    font-family: 'SF Mono', monospace;
                    margin-bottom: 4px;
                }

                .sp-call-status {
                    font-size: 13px;
                    color: #8b919e;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }

                .sp-call-status.ringing {
                    color: #eab308;
                }

                .sp-call-status.answered {
                    color: #22c55e;
                }

                .sp-call-timer {
                    font-size: 28px;
                    font-weight: 700;
                    color: #22c55e;
                    font-family: 'SF Mono', monospace;
                    margin: 16px 0;
                }

                .sp-call-actions {
                    display: flex;
                    justify-content: center;
                    gap: 16px;
                }

                .sp-action-btn {
                    width: 52px;
                    height: 52px;
                    border-radius: 50%;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .sp-mute-btn {
                    background: rgba(255, 255, 255, 0.1);
                    color: #f1f3f5;
                }

                .sp-mute-btn.muted {
                    background: #ef4444;
                    color: white;
                }

                .sp-hangup-btn {
                    background: #ef4444;
                    color: white;
                }

                .sp-hangup-btn:hover {
                    background: #dc2626;
                }

                /* Lead Context */
                .sp-lead-context {
                    display: none;
                    padding: 12px 16px;
                    background: rgba(99, 102, 241, 0.1);
                    border: 1px solid rgba(99, 102, 241, 0.2);
                    border-radius: 10px;
                    margin-bottom: 16px;
                }

                .sp-lead-context.active {
                    display: block;
                }

                .sp-lead-name {
                    font-size: 14px;
                    font-weight: 600;
                    color: #f1f3f5;
                    margin-bottom: 4px;
                }

                .sp-lead-product {
                    font-size: 12px;
                    color: #8b919e;
                }

                /* Message */
                .sp-message {
                    display: none;
                    padding: 12px 16px;
                    background: rgba(239, 68, 68, 0.1);
                    border: 1px solid rgba(239, 68, 68, 0.2);
                    border-radius: 10px;
                    margin-bottom: 16px;
                    font-size: 12px;
                    color: #f87171;
                }

                .sp-message.active {
                    display: block;
                }
            </style>

            <!-- Floating Action Button -->
            <button class="sp-fab not-registered" id="sp-fab">
                <i class="fas fa-phone"></i>
            </button>

            <!-- Panel -->
            <div class="sp-panel" id="sp-panel">
                <div class="sp-header">
                    <div class="sp-header-left">
                        <div class="sp-status-dot" id="sp-status-dot"></div>
                        <span class="sp-title">Softphone</span>
                    </div>
                    <button class="sp-close" id="sp-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="sp-body">
                    <!-- Error/Message -->
                    <div class="sp-message" id="sp-message"></div>

                    <!-- Lead Context -->
                    <div class="sp-lead-context" id="sp-lead-context">
                        <div class="sp-lead-name" id="sp-lead-name">Lead Name</div>
                        <div class="sp-lead-product" id="sp-lead-product">Product</div>
                    </div>

                    <!-- Dialer (idle state) -->
                    <div class="sp-dialer" id="sp-dialer">
                        <div class="sp-input-group">
                            <input type="tel" class="sp-phone-input" id="sp-phone-input" placeholder="Enter phone number">
                            <button class="sp-call-btn" id="sp-dial-btn" disabled>
                                <i class="fas fa-phone"></i>
                            </button>
                        </div>
                    </div>

                    <!-- In Call UI -->
                    <div class="sp-incall" id="sp-incall">
                        <div class="sp-call-info">
                            <div class="sp-call-number" id="sp-call-number">---</div>
                            <div class="sp-call-status" id="sp-call-status">
                                <i class="fas fa-phone-volume"></i>
                                <span>Connecting...</span>
                            </div>
                        </div>
                        <div class="sp-call-timer" id="sp-call-timer">00:00</div>
                        <div class="sp-call-actions">
                            <button class="sp-action-btn sp-mute-btn" id="sp-mute-btn">
                                <i class="fas fa-microphone"></i>
                            </button>
                            <button class="sp-action-btn sp-hangup-btn" id="sp-hangup-btn">
                                <i class="fas fa-phone-slash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(widget);
    }

    /**
     * Attach event listeners
     */
    _attachEventListeners() {
        // FAB click
        document.getElementById('sp-fab').addEventListener('click', () => {
            this.togglePanel();
        });

        // Close panel
        document.getElementById('sp-close').addEventListener('click', () => {
            this.closePanel();
        });

        // Phone input
        const phoneInput = document.getElementById('sp-phone-input');
        phoneInput.addEventListener('input', () => {
            const hasValue = phoneInput.value.trim().length > 0;
            document.getElementById('sp-dial-btn').disabled = !hasValue || !softphone.isRegistered;
        });

        phoneInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this._makeCall();
            }
        });

        // Dial button
        document.getElementById('sp-dial-btn').addEventListener('click', () => {
            this._makeCall();
        });

        // Mute button
        document.getElementById('sp-mute-btn').addEventListener('click', () => {
            const muted = softphone.toggleMute();
            const btn = document.getElementById('sp-mute-btn');
            btn.classList.toggle('muted', muted);
            btn.innerHTML = `<i class="fas fa-${muted ? 'microphone-slash' : 'microphone'}"></i>`;
        });

        // Hangup button
        document.getElementById('sp-hangup-btn').addEventListener('click', () => {
            softphone.hangup();
        });

        // Softphone callbacks
        softphone.onRegistrationChange = (registered, state) => {
            this._updateRegistrationStatus(registered, state);
        };

        softphone.onStateChange = (state) => {
            this._updateCallState(state);
        };

        softphone.onCallStart = (call) => {
            this._showInCallUI(call);
        };

        softphone.onCallEnd = (call) => {
            this._showDialerUI();
        };

        softphone.onError = (type, message) => {
            this._showMessage(message);
        };
    }

    /**
     * Initialize softphone connection
     */
    async _initSoftphone() {
        try {
            // Fetch SIP config from backend
            const response = await fetch('/api/sip/config', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            if (!response.ok) {
                const data = await response.json();
                this._showMessage(data.message || 'SIP not configured');
                return;
            }

            const data = await response.json();
            if (!data.configured) {
                this._showMessage('SIP not configured. Contact admin.');
                return;
            }

            this.isConfigured = true;
            await softphone.init(data.config);

        } catch (error) {
            console.error('[Widget] Init failed:', error);
            this._showMessage('Failed to connect to phone system');
        }
    }

    /**
     * Toggle panel visibility
     */
    togglePanel() {
        const panel = document.getElementById('sp-panel');
        panel.classList.toggle('open');
        this.isMinimized = !panel.classList.contains('open');
    }

    /**
     * Open panel
     */
    openPanel() {
        document.getElementById('sp-panel').classList.add('open');
        this.isMinimized = false;
    }

    /**
     * Close panel
     */
    closePanel() {
        document.getElementById('sp-panel').classList.remove('open');
        this.isMinimized = true;
    }

    /**
     * Set lead context for next call
     */
    setLead(lead) {
        this.currentLead = lead;

        if (lead) {
            document.getElementById('sp-lead-name').textContent = lead.name || 'Unknown';
            document.getElementById('sp-lead-product').textContent = lead.previous_item || lead.product_name || 'No product';
            document.getElementById('sp-lead-context').classList.add('active');
            document.getElementById('sp-phone-input').value = lead.phone || '';
            document.getElementById('sp-dial-btn').disabled = !softphone.isRegistered;
        } else {
            document.getElementById('sp-lead-context').classList.remove('active');
        }
    }

    /**
     * Call a specific lead
     */
    callLead(lead) {
        this.setLead(lead);
        this.openPanel();

        if (softphone.isRegistered && lead.phone) {
            this._makeCall();
        }
    }

    /**
     * Make the call
     */
    async _makeCall() {
        const phoneInput = document.getElementById('sp-phone-input');
        const phone = phoneInput.value.trim();

        if (!phone) return;

        try {
            await softphone.call(phone, this.currentLead?.id);
        } catch (error) {
            this._showMessage(error.message);
        }
    }

    /**
     * Update registration status UI
     */
    _updateRegistrationStatus(registered, state) {
        const fab = document.getElementById('sp-fab');
        const dot = document.getElementById('sp-status-dot');
        const dialBtn = document.getElementById('sp-dial-btn');

        fab.classList.remove('not-registered');
        dot.classList.remove('registered', 'disconnected', 'connecting');

        if (registered) {
            dot.classList.add('registered');
            const hasValue = document.getElementById('sp-phone-input').value.trim().length > 0;
            dialBtn.disabled = !hasValue;
        } else if (state === 'Unregistered' || state === 'Disconnected') {
            fab.classList.add('not-registered');
            dot.classList.add('disconnected');
            dialBtn.disabled = true;
        } else {
            dot.classList.add('connecting');
            dialBtn.disabled = true;
        }
    }

    /**
     * Update call state UI
     */
    _updateCallState(state) {
        const statusEl = document.getElementById('sp-call-status');
        const timerEl = document.getElementById('sp-call-timer');

        statusEl.className = 'sp-call-status';

        if (state.status === 'ringing') {
            statusEl.classList.add('ringing');
            statusEl.innerHTML = '<i class="fas fa-phone-volume"></i><span>Ringing...</span>';
        } else if (state.status === 'answered') {
            statusEl.classList.add('answered');
            statusEl.innerHTML = '<i class="fas fa-phone-alt"></i><span>Connected</span>';
            if (state.formattedDuration) {
                timerEl.textContent = state.formattedDuration;
            }
        }
    }

    /**
     * Show in-call UI
     */
    _showInCallUI(call) {
        document.getElementById('sp-dialer').style.display = 'none';
        document.getElementById('sp-incall').classList.add('active');
        document.getElementById('sp-call-number').textContent = call.phoneNumber;
        document.getElementById('sp-call-timer').textContent = '00:00';
        document.getElementById('sp-fab').classList.add('active-call');

        // Reset mute button
        const muteBtn = document.getElementById('sp-mute-btn');
        muteBtn.classList.remove('muted');
        muteBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    }

    /**
     * Show dialer UI
     */
    _showDialerUI() {
        document.getElementById('sp-dialer').style.display = 'block';
        document.getElementById('sp-incall').classList.remove('active');
        document.getElementById('sp-fab').classList.remove('active-call');

        // Clear lead context
        this.currentLead = null;
        document.getElementById('sp-lead-context').classList.remove('active');
        document.getElementById('sp-phone-input').value = '';
    }

    /**
     * Show error message
     */
    _showMessage(message) {
        const el = document.getElementById('sp-message');
        el.textContent = message;
        el.classList.add('active');

        setTimeout(() => {
            el.classList.remove('active');
        }, 5000);
    }
}

// Auto-initialize widget when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.softphoneWidget = new SoftphoneWidget();
});

export default SoftphoneWidget;
