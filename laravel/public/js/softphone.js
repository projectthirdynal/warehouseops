/**
 * Click-to-Call Widget - Manual Dialing with Call Logging
 * Works with external softphones like MicroSIP
 * 
 * Flow:
 * 1. Agent clicks "Call" on lead → Phone copied to clipboard
 * 2. Agent dials in MicroSIP manually
 * 3. CRM shows call timer
 * 4. Agent clicks "End Call" → Duration logged, notes saved
 */

(function () {
    'use strict';

    // State
    let currentCall = null;
    let callTimer = null;
    let callStartTime = null;
    let isCallActive = false;

    // Create widget HTML
    function createWidget() {
        const widget = document.createElement('div');
        widget.id = 'call-widget';
        widget.innerHTML = `
            <style>
                #call-widget {
                    position: fixed;
                    bottom: 24px;
                    right: 24px;
                    z-index: 9999;
                    font-family: inherit;
                }

                .cw-fab {
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
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .cw-fab:hover {
                    transform: scale(1.05);
                    box-shadow: 0 6px 30px rgba(99, 102, 241, 0.5);
                }

                .cw-fab.active {
                    background: linear-gradient(135deg, #22c55e, #16a34a);
                    animation: pulse-active 2s infinite;
                }

                @keyframes pulse-active {
                    0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
                    50% { box-shadow: 0 0 0 15px rgba(34, 197, 94, 0); }
                }

                .cw-panel {
                    position: absolute;
                    bottom: 68px;
                    right: 0;
                    width: 320px;
                    background: #12151c;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 16px;
                    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
                    display: none;
                    overflow: hidden;
                }

                .cw-panel.open {
                    display: block;
                    animation: slideUp 0.25s ease;
                }

                @keyframes slideUp {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .cw-header {
                    padding: 14px 18px;
                    background: rgba(255, 255, 255, 0.03);
                    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .cw-header-left {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .cw-icon {
                    width: 32px;
                    height: 32px;
                    border-radius: 8px;
                    background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 14px;
                }

                .cw-title {
                    font-size: 14px;
                    font-weight: 600;
                    color: #f1f3f5;
                }

                .cw-close {
                    width: 28px;
                    height: 28px;
                    border-radius: 8px;
                    background: rgba(255, 255, 255, 0.05);
                    border: none;
                    color: #8b919e;
                    cursor: pointer;
                    font-size: 12px;
                }

                .cw-close:hover {
                    background: rgba(255, 255, 255, 0.1);
                    color: #f1f3f5;
                }

                .cw-body {
                    padding: 20px;
                }

                /* Idle State */
                .cw-idle {
                    text-align: center;
                    padding: 20px 0;
                    color: #8b919e;
                }

                .cw-idle-icon {
                    font-size: 36px;
                    margin-bottom: 12px;
                    opacity: 0.5;
                }

                .cw-idle-text {
                    font-size: 13px;
                }

                /* Call Ready */
                .cw-ready {
                    display: none;
                }

                .cw-ready.show {
                    display: block;
                }

                .cw-lead-card {
                    background: rgba(99, 102, 241, 0.08);
                    border: 1px solid rgba(99, 102, 241, 0.2);
                    border-radius: 12px;
                    padding: 16px;
                    margin-bottom: 16px;
                }

                .cw-lead-name {
                    font-size: 16px;
                    font-weight: 700;
                    color: #f1f3f5;
                    margin-bottom: 4px;
                }

                .cw-lead-phone {
                    font-size: 18px;
                    font-weight: 700;
                    color: #22d3ee;
                    font-family: 'SF Mono', monospace;
                    margin-bottom: 8px;
                }

                .cw-lead-product {
                    font-size: 12px;
                    color: #8b919e;
                }

                .cw-start-btn {
                    width: 100%;
                    padding: 14px;
                    background: linear-gradient(135deg, #22c55e, #16a34a);
                    border: none;
                    border-radius: 12px;
                    color: white;
                    font-size: 14px;
                    font-weight: 700;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    transition: all 0.2s;
                }

                .cw-start-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
                }

                .cw-tip {
                    font-size: 11px;
                    color: #64748b;
                    text-align: center;
                    margin-top: 12px;
                }

                /* Active Call */
                .cw-active {
                    display: none;
                }

                .cw-active.show {
                    display: block;
                }

                .cw-call-info {
                    text-align: center;
                    margin-bottom: 20px;
                }

                .cw-call-status {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 6px 14px;
                    background: rgba(34, 197, 94, 0.1);
                    border: 1px solid rgba(34, 197, 94, 0.2);
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                    color: #22c55e;
                    margin-bottom: 12px;
                }

                .cw-call-status .dot {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    background: #22c55e;
                    animation: blink 1s infinite;
                }

                @keyframes blink {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.3; }
                }

                .cw-call-number {
                    font-size: 20px;
                    font-weight: 700;
                    color: #f1f3f5;
                    font-family: 'SF Mono', monospace;
                    margin-bottom: 4px;
                }

                .cw-call-name {
                    font-size: 14px;
                    color: #8b919e;
                }

                .cw-timer {
                    font-size: 36px;
                    font-weight: 700;
                    color: #22c55e;
                    font-family: 'SF Mono', monospace;
                    text-align: center;
                    margin: 20px 0;
                }

                .cw-actions {
                    display: flex;
                    gap: 12px;
                }

                .cw-end-btn {
                    flex: 1;
                    padding: 14px;
                    background: #ef4444;
                    border: none;
                    border-radius: 12px;
                    color: white;
                    font-size: 14px;
                    font-weight: 700;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    transition: all 0.2s;
                }

                .cw-end-btn:hover {
                    background: #dc2626;
                }

                .cw-copy-btn {
                    width: 48px;
                    padding: 14px;
                    background: rgba(255, 255, 255, 0.1);
                    border: none;
                    border-radius: 12px;
                    color: #f1f3f5;
                    font-size: 16px;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .cw-copy-btn:hover {
                    background: rgba(255, 255, 255, 0.15);
                }

                /* Result Form */
                .cw-result {
                    display: none;
                }

                .cw-result.show {
                    display: block;
                }

                .cw-result-header {
                    text-align: center;
                    margin-bottom: 20px;
                }

                .cw-result-icon {
                    width: 48px;
                    height: 48px;
                    border-radius: 50%;
                    background: rgba(34, 197, 94, 0.1);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #22c55e;
                    font-size: 20px;
                    margin: 0 auto 12px;
                }

                .cw-result-title {
                    font-size: 16px;
                    font-weight: 700;
                    color: #f1f3f5;
                }

                .cw-result-duration {
                    font-size: 14px;
                    color: #8b919e;
                }

                .cw-form-group {
                    margin-bottom: 16px;
                }

                .cw-form-label {
                    display: block;
                    font-size: 11px;
                    font-weight: 600;
                    color: #64748b;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    margin-bottom: 6px;
                }

                .cw-outcome-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 8px;
                }

                .cw-outcome-btn {
                    padding: 10px;
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 8px;
                    color: #8b919e;
                    font-size: 12px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .cw-outcome-btn:hover {
                    background: rgba(255, 255, 255, 0.08);
                    color: #f1f3f5;
                }

                .cw-outcome-btn.selected {
                    border-color: #6366f1;
                    background: rgba(99, 102, 241, 0.1);
                    color: #818cf8;
                }

                .cw-outcome-btn.sale.selected {
                    border-color: #22c55e;
                    background: rgba(34, 197, 94, 0.1);
                    color: #22c55e;
                }

                .cw-notes {
                    width: 100%;
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 10px;
                    padding: 12px;
                    color: #f1f3f5;
                    font-size: 13px;
                    font-family: inherit;
                    min-height: 80px;
                    resize: vertical;
                }

                .cw-notes:focus {
                    outline: none;
                    border-color: #6366f1;
                }

                .cw-notes::placeholder {
                    color: #475569;
                }

                .cw-save-btn {
                    width: 100%;
                    padding: 14px;
                    background: #6366f1;
                    border: none;
                    border-radius: 12px;
                    color: white;
                    font-size: 14px;
                    font-weight: 700;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    transition: all 0.2s;
                }

                .cw-save-btn:hover {
                    background: #5558e3;
                }

                /* Toast */
                .cw-toast {
                    position: fixed;
                    bottom: 100px;
                    right: 24px;
                    padding: 12px 20px;
                    background: #1e293b;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 10px;
                    color: #f1f3f5;
                    font-size: 13px;
                    display: none;
                    align-items: center;
                    gap: 10px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                    z-index: 10000;
                }

                .cw-toast.show {
                    display: flex;
                    animation: toastIn 0.3s ease;
                }

                @keyframes toastIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .cw-toast i {
                    color: #22c55e;
                }
            </style>

            <!-- FAB -->
            <button class="cw-fab" id="cw-fab">
                <i class="fas fa-phone"></i>
            </button>

            <!-- Panel -->
            <div class="cw-panel" id="cw-panel">
                <div class="cw-header">
                    <div class="cw-header-left">
                        <div class="cw-icon"><i class="fas fa-phone-alt"></i></div>
                        <span class="cw-title">Call Center</span>
                    </div>
                    <button class="cw-close" id="cw-close"><i class="fas fa-times"></i></button>
                </div>

                <div class="cw-body">
                    <!-- Idle State -->
                    <div class="cw-idle" id="cw-idle">
                        <div class="cw-idle-icon"><i class="fas fa-headset"></i></div>
                        <div class="cw-idle-text">Click a lead's call button to start</div>
                    </div>

                    <!-- Ready to Call -->
                    <div class="cw-ready" id="cw-ready">
                        <div class="cw-lead-card">
                            <div class="cw-lead-name" id="cw-lead-name">Customer Name</div>
                            <div class="cw-lead-phone" id="cw-lead-phone">09XX XXX XXXX</div>
                            <div class="cw-lead-product" id="cw-lead-product">Previous: Product Name</div>
                        </div>
                        <button class="cw-start-btn" id="cw-start">
                            <i class="fas fa-phone"></i>
                            Start Call & Copy Number
                        </button>
                        <div class="cw-tip">Number will be copied. Dial in MicroSIP.</div>
                    </div>

                    <!-- Active Call -->
                    <div class="cw-active" id="cw-active">
                        <div class="cw-call-info">
                            <div class="cw-call-status">
                                <span class="dot"></span>
                                Call in Progress
                            </div>
                            <div class="cw-call-number" id="cw-active-phone">09XX XXX XXXX</div>
                            <div class="cw-call-name" id="cw-active-name">Customer Name</div>
                        </div>
                        <div class="cw-timer" id="cw-timer">00:00</div>
                        <div class="cw-actions">
                            <button class="cw-end-btn" id="cw-end">
                                <i class="fas fa-phone-slash"></i>
                                End Call
                            </button>
                            <button class="cw-copy-btn" id="cw-recopy" title="Copy number again">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Result Form -->
                    <div class="cw-result" id="cw-result">
                        <div class="cw-result-header">
                            <div class="cw-result-icon"><i class="fas fa-check"></i></div>
                            <div class="cw-result-title">Call Ended</div>
                            <div class="cw-result-duration" id="cw-final-duration">Duration: 00:00</div>
                        </div>

                        <div class="cw-form-group">
                            <label class="cw-form-label">Call Outcome</label>
                            <div class="cw-outcome-grid" id="cw-outcomes">
                                <button class="cw-outcome-btn" data-status="NO_ANSWER">No Answer</button>
                                <button class="cw-outcome-btn" data-status="CALLBACK">Callback</button>
                                <button class="cw-outcome-btn" data-status="REJECT">Rejected</button>
                                <button class="cw-outcome-btn sale" data-status="SALE">Sale!</button>
                            </div>
                        </div>

                        <div class="cw-form-group">
                            <label class="cw-form-label">Notes</label>
                            <textarea class="cw-notes" id="cw-notes" placeholder="Call summary, customer feedback..."></textarea>
                        </div>

                        <button class="cw-save-btn" id="cw-save">
                            <i class="fas fa-save"></i>
                            Save & Update Lead
                        </button>
                    </div>
                </div>
            </div>

            <!-- Toast -->
            <div class="cw-toast" id="cw-toast">
                <i class="fas fa-check-circle"></i>
                <span id="cw-toast-msg">Phone number copied!</span>
            </div>
        `;
        document.body.appendChild(widget);
    }

    // Initialize
    function init() {
        createWidget();
        attachEvents();
    }

    function attachEvents() {
        // FAB toggle
        document.getElementById('cw-fab').addEventListener('click', () => {
            document.getElementById('cw-panel').classList.toggle('open');
        });

        // Close panel
        document.getElementById('cw-close').addEventListener('click', () => {
            document.getElementById('cw-panel').classList.remove('open');
        });

        // Start call
        document.getElementById('cw-start').addEventListener('click', startCall);

        // End call
        document.getElementById('cw-end').addEventListener('click', endCall);

        // Re-copy number
        document.getElementById('cw-recopy').addEventListener('click', () => {
            if (currentCall) {
                copyToClipboard(currentCall.phone);
                showToast('Number copied again!');
            }
        });

        // Outcome selection
        document.getElementById('cw-outcomes').addEventListener('click', (e) => {
            const btn = e.target.closest('.cw-outcome-btn');
            if (btn) {
                document.querySelectorAll('.cw-outcome-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
            }
        });

        // Save result
        document.getElementById('cw-save').addEventListener('click', saveCallResult);
    }

    // Copy to clipboard
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).catch(() => {
            // Fallback
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
        });
    }

    // Show toast notification
    function showToast(message) {
        const toast = document.getElementById('cw-toast');
        document.getElementById('cw-toast-msg').textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // Start call
    async function startCall() {
        if (!currentCall) return;

        // Copy phone to clipboard
        copyToClipboard(currentCall.phone);
        showToast('Phone number copied! Dial in MicroSIP');

        // Log call start
        try {
            const resp = await fetch('/api/calls/initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    phone_number: currentCall.phone,
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
            console.log('Call log failed:', e);
        }

        // Switch to active call UI
        callStartTime = Date.now();
        isCallActive = true;

        document.getElementById('cw-ready').classList.remove('show');
        document.getElementById('cw-active').classList.add('show');
        document.getElementById('cw-active-phone').textContent = currentCall.phone;
        document.getElementById('cw-active-name').textContent = currentCall.name;
        document.getElementById('cw-fab').classList.add('active');

        // Start timer
        callTimer = setInterval(updateTimer, 1000);
    }

    // Update timer display
    function updateTimer() {
        if (!callStartTime) return;
        const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
        const mins = Math.floor(elapsed / 60).toString().padStart(2, '0');
        const secs = (elapsed % 60).toString().padStart(2, '0');
        document.getElementById('cw-timer').textContent = `${mins}:${secs}`;
    }

    // End call
    async function endCall() {
        if (callTimer) {
            clearInterval(callTimer);
            callTimer = null;
        }

        const duration = callStartTime ? Math.floor((Date.now() - callStartTime) / 1000) : 0;
        currentCall.duration = duration;

        // Log answered status
        if (currentCall.callId) {
            try {
                await fetch('/api/calls/' + currentCall.callId + '/status', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: 'answered' }),
                    credentials: 'same-origin'
                });
            } catch (e) { }
        }

        isCallActive = false;

        // Show result form
        const mins = Math.floor(duration / 60).toString().padStart(2, '0');
        const secs = (duration % 60).toString().padStart(2, '0');
        document.getElementById('cw-final-duration').textContent = `Duration: ${mins}:${secs}`;

        document.getElementById('cw-active').classList.remove('show');
        document.getElementById('cw-result').classList.add('show');
        document.getElementById('cw-fab').classList.remove('active');

        // Reset selections
        document.querySelectorAll('.cw-outcome-btn').forEach(b => b.classList.remove('selected'));
        document.getElementById('cw-notes').value = '';
    }

    // Save call result
    async function saveCallResult() {
        const selectedOutcome = document.querySelector('.cw-outcome-btn.selected');
        const notes = document.getElementById('cw-notes').value.trim();

        if (!selectedOutcome) {
            showToast('Please select an outcome');
            return;
        }

        const status = selectedOutcome.dataset.status;

        // Update call log
        if (currentCall.callId) {
            try {
                await fetch('/api/calls/' + currentCall.callId + '/status', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        status: 'ended',
                        notes: notes
                    }),
                    credentials: 'same-origin'
                });
            } catch (e) { }
        }

        // Update lead status
        if (currentCall.leadId) {
            try {
                await fetch('/leads/' + currentCall.leadId + '/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        status: status,
                        note: notes
                    }),
                    credentials: 'same-origin'
                });
            } catch (e) { }
        }

        showToast('Call saved successfully!');

        // Reset UI
        document.getElementById('cw-result').classList.remove('show');
        document.getElementById('cw-idle').style.display = 'block';
        currentCall = null;
        callStartTime = null;

        // Close panel after delay
        setTimeout(() => {
            document.getElementById('cw-panel').classList.remove('open');
        }, 1500);
    }

    // Global function to initiate call from lead cards
    window.callLead = function (lead) {
        currentCall = {
            leadId: lead.id,
            name: lead.name || 'Unknown',
            phone: lead.phone,
            product: lead.previous_item || lead.product_name || ''
        };

        // Update UI
        document.getElementById('cw-lead-name').textContent = currentCall.name;
        document.getElementById('cw-lead-phone').textContent = currentCall.phone;
        document.getElementById('cw-lead-product').textContent = currentCall.product ? 'Previous: ' + currentCall.product : '';

        // Show ready state
        document.getElementById('cw-idle').style.display = 'none';
        document.getElementById('cw-ready').classList.add('show');
        document.getElementById('cw-active').classList.remove('show');
        document.getElementById('cw-result').classList.remove('show');

        // Open panel
        document.getElementById('cw-panel').classList.add('open');
    };

    // Init when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
