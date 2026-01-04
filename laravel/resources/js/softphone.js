/**
 * Softphone Module - WebRTC SIP Client
 * Integrates with CRM for click-to-call functionality
 */

import { UserAgent, Registerer, Inviter, SessionState } from 'sip.js';

class Softphone {
    constructor() {
        this.ua = null;
        this.registerer = null;
        this.session = null;
        this.config = null;
        this.isRegistered = false;
        this.currentCall = null;
        this.callStartTime = null;
        this.callTimer = null;

        // Audio elements
        this.remoteAudio = null;
        this.ringtone = null;

        // Callbacks
        this.onStateChange = null;
        this.onCallStart = null;
        this.onCallEnd = null;
        this.onRegistrationChange = null;
        this.onError = null;

        this._initAudioElements();
    }

    /**
     * Initialize audio elements
     */
    _initAudioElements() {
        // Remote audio (incoming audio from call)
        this.remoteAudio = document.createElement('audio');
        this.remoteAudio.id = 'softphone-remote-audio';
        this.remoteAudio.autoplay = true;
        document.body.appendChild(this.remoteAudio);

        // Ringtone
        this.ringtone = document.createElement('audio');
        this.ringtone.id = 'softphone-ringtone';
        this.ringtone.loop = true;
        this.ringtone.src = '/sounds/ringtone.mp3';
        document.body.appendChild(this.ringtone);
    }

    /**
     * Initialize with SIP configuration
     */
    async init(config) {
        this.config = config;

        const uri = UserAgent.makeURI(config.uri);
        if (!uri) {
            throw new Error('Invalid SIP URI');
        }

        const transportOptions = {
            server: config.wsServer,
            traceSip: false,
        };

        const userAgentOptions = {
            uri: uri,
            transportOptions: transportOptions,
            authorizationPassword: config.authorizationPassword,
            authorizationUsername: config.authorizationUsername,
            displayName: config.displayName || config.authorizationUsername,
            sessionDescriptionHandlerFactoryOptions: {
                constraints: {
                    audio: true,
                    video: false
                },
                peerConnectionConfiguration: {
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' }
                    ]
                }
            }
        };

        try {
            this.ua = new UserAgent(userAgentOptions);

            this.ua.delegate = {
                onInvite: (invitation) => this._handleIncomingCall(invitation),
                onDisconnect: (error) => this._handleDisconnect(error),
            };

            await this.ua.start();
            console.log('[Softphone] UserAgent started');

            // Register with SIP server
            this.registerer = new Registerer(this.ua);
            this.registerer.stateChange.addListener((state) => {
                this.isRegistered = state === 'Registered';
                console.log('[Softphone] Registration state:', state);
                if (this.onRegistrationChange) {
                    this.onRegistrationChange(this.isRegistered, state);
                }
            });

            await this.registerer.register();
            console.log('[Softphone] Registration request sent');

            return true;
        } catch (error) {
            console.error('[Softphone] Init error:', error);
            if (this.onError) {
                this.onError('init_failed', error.message);
            }
            throw error;
        }
    }

    /**
     * Make an outbound call
     */
    async call(phoneNumber, leadId = null) {
        if (!this.ua || !this.isRegistered) {
            throw new Error('Softphone not registered');
        }

        if (this.session) {
            throw new Error('Call already in progress');
        }

        // Format phone number for SIP
        const target = this._formatPhoneNumber(phoneNumber);
        const targetUri = UserAgent.makeURI(`sip:${target}@${this._getDomain()}`);

        if (!targetUri) {
            throw new Error('Invalid phone number');
        }

        // Create inviter (outbound call)
        const inviter = new Inviter(this.ua, targetUri, {
            sessionDescriptionHandlerOptions: {
                constraints: {
                    audio: true,
                    video: false
                }
            }
        });

        this.session = inviter;
        this.currentCall = {
            phoneNumber: phoneNumber,
            leadId: leadId,
            direction: 'outbound',
            callId: null,
            status: 'initiated'
        };

        // Set up session state change handler
        inviter.stateChange.addListener((state) => {
            this._handleSessionState(state);
        });

        try {
            // Log call initiation to backend
            const logResult = await this._logCallInitiate(phoneNumber, leadId);
            this.currentCall.callId = logResult.call?.call_id;

            // Send INVITE
            await inviter.invite({
                requestDelegate: {
                    onProgress: () => {
                        console.log('[Softphone] Call ringing...');
                        this._updateCallStatus('ringing');
                    },
                    onAccept: () => {
                        console.log('[Softphone] Call answered');
                        this._onCallAnswered();
                    },
                    onReject: () => {
                        console.log('[Softphone] Call rejected');
                        this._updateCallStatus('failed');
                    }
                }
            });

            if (this.onCallStart) {
                this.onCallStart(this.currentCall);
            }

            return this.currentCall;
        } catch (error) {
            console.error('[Softphone] Call failed:', error);
            this.session = null;
            this.currentCall = null;
            if (this.onError) {
                this.onError('call_failed', error.message);
            }
            throw error;
        }
    }

    /**
     * Hang up current call
     */
    async hangup() {
        if (!this.session) {
            return;
        }

        try {
            const state = this.session.state;

            if (state === SessionState.Establishing || state === SessionState.Established) {
                if (this.session.bye) {
                    await this.session.bye();
                } else if (this.session.cancel) {
                    await this.session.cancel();
                }
            }
        } catch (error) {
            console.error('[Softphone] Hangup error:', error);
        }

        this._endCall('ended');
    }

    /**
     * Toggle mute
     */
    toggleMute() {
        if (!this.session) return false;

        const pc = this.session.sessionDescriptionHandler?.peerConnection;
        if (!pc) return false;

        const audioTrack = pc.getSenders().find(s => s.track?.kind === 'audio');
        if (audioTrack?.track) {
            audioTrack.track.enabled = !audioTrack.track.enabled;
            return !audioTrack.track.enabled; // Return muted state
        }
        return false;
    }

    /**
     * Check if muted
     */
    isMuted() {
        if (!this.session) return false;

        const pc = this.session.sessionDescriptionHandler?.peerConnection;
        if (!pc) return false;

        const audioTrack = pc.getSenders().find(s => s.track?.kind === 'audio');
        return audioTrack?.track ? !audioTrack.track.enabled : false;
    }

    /**
     * Send DTMF tone
     */
    sendDTMF(tone) {
        if (!this.session) return;

        try {
            const options = {
                requestOptions: {
                    body: {
                        contentDisposition: 'render',
                        contentType: 'application/dtmf-relay',
                        content: `Signal=${tone}\nDuration=160`
                    }
                }
            };
            this.session.info(options);
        } catch (error) {
            console.error('[Softphone] DTMF error:', error);
        }
    }

    /**
     * Get current call duration in seconds
     */
    getCallDuration() {
        if (!this.callStartTime) return 0;
        return Math.floor((Date.now() - this.callStartTime) / 1000);
    }

    /**
     * Get formatted call duration (MM:SS)
     */
    getFormattedDuration() {
        const seconds = this.getCallDuration();
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    /**
     * Disconnect and cleanup
     */
    async disconnect() {
        await this.hangup();

        if (this.registerer) {
            try {
                await this.registerer.unregister();
            } catch (e) { }
        }

        if (this.ua) {
            try {
                await this.ua.stop();
            } catch (e) { }
        }

        this.ua = null;
        this.registerer = null;
        this.isRegistered = false;
    }

    // --- Private Methods ---

    _getDomain() {
        // Extract domain from config URI
        const match = this.config.uri.match(/@(.+)$/);
        return match ? match[1] : '';
    }

    _formatPhoneNumber(number) {
        // Remove non-digits, handle country code
        return number.replace(/\D/g, '');
    }

    _handleSessionState(state) {
        console.log('[Softphone] Session state:', state);

        switch (state) {
            case SessionState.Establishing:
                this._updateCallStatus('ringing');
                break;
            case SessionState.Established:
                this._onCallAnswered();
                break;
            case SessionState.Terminating:
            case SessionState.Terminated:
                this._endCall('ended');
                break;
        }
    }

    _onCallAnswered() {
        this.callStartTime = Date.now();
        this._updateCallStatus('answered');

        // Set up remote audio
        if (this.session?.sessionDescriptionHandler?.peerConnection) {
            const pc = this.session.sessionDescriptionHandler.peerConnection;
            pc.ontrack = (event) => {
                if (event.streams && event.streams[0]) {
                    this.remoteAudio.srcObject = event.streams[0];
                }
            };
        }

        // Start call timer
        this._startCallTimer();
    }

    _startCallTimer() {
        if (this.callTimer) {
            clearInterval(this.callTimer);
        }

        this.callTimer = setInterval(() => {
            if (this.onStateChange) {
                this.onStateChange({
                    status: 'answered',
                    duration: this.getCallDuration(),
                    formattedDuration: this.getFormattedDuration()
                });
            }
        }, 1000);
    }

    _endCall(reason) {
        if (this.callTimer) {
            clearInterval(this.callTimer);
            this.callTimer = null;
        }

        const duration = this.getCallDuration();

        // Log to backend
        if (this.currentCall?.callId) {
            this._logCallEnd(this.currentCall.callId, reason);
        }

        if (this.onCallEnd) {
            this.onCallEnd({
                ...this.currentCall,
                duration: duration,
                reason: reason
            });
        }

        // Cleanup
        this.session = null;
        this.currentCall = null;
        this.callStartTime = null;
        this.remoteAudio.srcObject = null;
    }

    async _updateCallStatus(status) {
        if (this.currentCall) {
            this.currentCall.status = status;
        }

        if (this.currentCall?.callId) {
            try {
                await fetch(`/api/calls/${this.currentCall.callId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ status: status })
                });
            } catch (e) {
                console.error('[Softphone] Status update failed:', e);
            }
        }

        if (this.onStateChange) {
            this.onStateChange({ status: status });
        }
    }

    async _logCallInitiate(phoneNumber, leadId) {
        try {
            const response = await fetch('/api/calls/initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    phone_number: phoneNumber,
                    lead_id: leadId,
                    direction: 'outbound'
                })
            });
            return await response.json();
        } catch (e) {
            console.error('[Softphone] Call log failed:', e);
            return { call: { call_id: 'local-' + Date.now() } };
        }
    }

    async _logCallEnd(callId, reason) {
        try {
            await fetch(`/api/calls/${callId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ status: reason === 'ended' ? 'ended' : 'failed' })
            });
        } catch (e) {
            console.error('[Softphone] End call log failed:', e);
        }
    }

    _handleIncomingCall(invitation) {
        console.log('[Softphone] Incoming call:', invitation);
        // For now, auto-reject incoming calls (agents make outbound only)
        // Future: implement incoming call UI
        invitation.reject();
    }

    _handleDisconnect(error) {
        console.log('[Softphone] Disconnected:', error);
        this.isRegistered = false;
        if (this.onRegistrationChange) {
            this.onRegistrationChange(false, 'Disconnected');
        }
    }
}

// Export singleton instance
export const softphone = new Softphone();
export default Softphone;
