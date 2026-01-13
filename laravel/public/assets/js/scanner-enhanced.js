/**
 * Enhanced Scanner JavaScript - Batch Scanning
 */

let currentSession = null;
let counters = { valid: 0, duplicate: 0, error: 0, total: 0 };


let currentPage = 1;
let rowsPerPage = 100;

// Make functions globally available


let historyLoading = false;

function loadHistory() {
    if (historyLoading) return;

    const listContainer = document.getElementById('historyList');
    if (!listContainer) return;

    historyLoading = true;
    listContainer.innerHTML = '<p class="loading">Loading history...</p>';

    fetch('/batch-scan/history?limit=20')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderHistoryList(data.sessions);
            } else {
                listContainer.innerHTML = '<p class="error-state">Failed to load history</p>';
            }
        })
        .catch(error => {
            console.error('History Error:', error);
            listContainer.innerHTML = '<p class="error-state">Error loading history</p>';
        })
        .finally(() => {
            historyLoading = false;
        });
}

function renderHistoryList(sessions) {
    const listContainer = document.getElementById('historyList');

    if (sessions.length === 0) {
        listContainer.innerHTML = '<p class="empty-state">No past batch sessions found</p>';
        return;
    }

    let html = '<div class="table-responsive"><table class="issues-table"><thead><tr><th>Date</th><th>Scanner</th><th>Scanned</th><th>Status</th><th>Action</th></tr></thead><tbody>';

    sessions.forEach(session => {
        const date = new Date(session.end_time || session.updated_at).toLocaleString();

        html += `
            <tr>
                <td>${date}</td>
                <td>${session.scanned_by}</td>
                <td>${session.total_scanned}</td>
                <td><span class="badge ${session.status}">${session.status}</span></td>
                <td>
                    <a href="/batch/manifest/${session.id}" target="_blank" class="btn-sm btn-success" style="text-decoration:none;">
                       🖨️ Print
                    </a>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table></div>';
    listContainer.innerHTML = html;
}
const scanForm = document.getElementById('scanForm');
const waybillInput = document.getElementById('waybillInput');
const scannedByInput = document.getElementById('scannedBy');
const scanResult = document.getElementById('scanResult');
const startBatchBtn = document.getElementById('startBatchBtn');
const scanBtn = document.getElementById('scanBtn');
const dispatchBtn = document.getElementById('dispatchBtn');
const sessionStatus = document.getElementById('sessionStatus');
const refreshPendingBtn = document.getElementById('refreshPendingBtn');
const rowsPerPageSelect = document.getElementById('rowsPerPage');
const prevPageBtn = document.getElementById('prevPageBtn');
const nextPageBtn = document.getElementById('nextPageBtn');

// Load pending waybills
loadPendingWaybills();

// Check for persisted manifest
const lastSummary = localStorage.getItem('last_manifest_session');
if (lastSummary) {
    const manifestBtn = document.getElementById('printManifestBtn');
    const manifestAction = document.getElementById('manifestAction');
    if (manifestBtn && manifestAction) {
        manifestBtn.href = `/batch/manifest/${lastSummary}`;
        manifestAction.style.display = 'block';
    }
}

// Rows per page changed
rowsPerPageSelect.addEventListener('change', function () {
    rowsPerPage = parseInt(this.value);
    currentPage = 1;
    loadPendingWaybills();
});

// Pagination buttons
prevPageBtn.addEventListener('click', function () {
    if (currentPage > 1) {
        currentPage--;
        loadPendingWaybills();
    }
});

nextPageBtn.addEventListener('click', function () {
    currentPage++;
    loadPendingWaybills();
});

// Start batch session
startBatchBtn.addEventListener('click', async function () {
    const scannedBy = scannedByInput.value.trim();

    try {
        const response = await fetch('/api/batch-scan/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ scanned_by: scannedBy })
        });

        const result = await response.json();

        if (result.success) {
            currentSession = result.session_id;
            document.getElementById('sessionId').value = result.session_id;

            // Update UI
            sessionStatus.textContent = 'Session Active';
            sessionStatus.className = 'status-indicator active';
            startBatchBtn.disabled = true;
            scanBtn.disabled = false;
            dispatchBtn.disabled = false;
            document.getElementById('markPendingBtn').disabled = false;
            waybillInput.focus();

            // Reset counters
            resetCounters();

            showResult('success', '✅ Batch session started!');
        } else {
            showResult('error', `❌ ${result.message}`);
        }
    } catch (error) {
        showResult('error', `❌ Error: ${error.message}`);
    }
});

// Scan waybill
scanForm.addEventListener('submit', async function (e) {
    e.preventDefault();

    if (!currentSession) {
        showResult('error', '❌ Please start a batch session first');
        return;
    }

    const waybillNumber = waybillInput.value.trim();
    const sessionId = currentSession;

    try {
        const response = await fetch('/api/batch-scan/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                waybill_number: waybillNumber,
                session_id: sessionId
            })
        });

        const result = await response.json();

        if (result.scan_type === 'valid') {
            counters.valid++;
            counters.total = counters.valid + counters.error;
            updateCounters();
            showResult('success', `✅ ${result.message}`, result.waybill);
            playBeep(true);
            addToRecentScans(result);
        } else if (result.scan_type === 'duplicate') {
            counters.duplicate++;
            updateCounters();
            showResult('warning', `🔄 ${result.message}`, result.waybill);
            playBeep(false, 600);
            addToRecentScans(result);
        } else if (result.scan_type === 'error') {
            counters.error++;
            counters.total = counters.valid + counters.error;
            updateCounters();
            showResult('error', `❌ ${result.message}`);
            playBeep(false, 300);
            addToRecentScans(result);
        }

        waybillInput.value = '';
        waybillInput.focus();

        // Refresh pending list
        loadPendingWaybills();

    } catch (error) {
        showResult('error', `❌ Error: ${error.message}`);
        playBeep(false, 300);
    }
});

// Dispatch batch
dispatchBtn.addEventListener('click', async function () {
    if (!currentSession) {
        showResult('error', '❌ No active session');
        return;
    }

    if (counters.valid === 0) {
        showResult('error', '❌ No valid scans to dispatch');
        return;
    }

    const confirmed = confirm(`Dispatch ${counters.valid} waybills?\n\nThis will finalize the batch and mark all scanned waybills as dispatched.`);

    if (!confirmed) return;

    try {
        dispatchBtn.disabled = true;
        dispatchBtn.innerHTML = '<span>⏳ Processing...</span>';

        const response = await fetch('/api/batch-scan/dispatch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                session_id: currentSession,
                scanned_by: scannedByInput.value.trim()
            })
        });

        const result = await response.json();

        if (result.success) {
            showResult('success', `✅ ${result.message}`, null, 8000);

            // Reset session immediately but keep visual feedback
            resetSession();

            // Show Manifest Button
            const manifestBtn = document.getElementById('printManifestBtn');
            const manifestAction = document.getElementById('manifestAction');
            manifestBtn.href = `/batch/manifest/${result.session_id}`;
            manifestAction.style.display = 'block';

            // Save to localStorage so it persists on refresh
            localStorage.setItem('last_manifest_session', result.session_id);

            // Update status text to confirm dispatch
            sessionStatus.textContent = "Batch Dispatched Successfully";
            sessionStatus.className = 'status-indicator completed';

            loadPendingWaybills();

            loadPendingWaybills();

            playBeep(true);
        } else {
            showResult('error', `❌ ${result.message}`);
            dispatchBtn.disabled = false;
            dispatchBtn.innerHTML = '<span>✅ Dispatch Batch</span>';
        }
    } catch (error) {
        showResult('error', `❌ Error: ${error.message}`);
        dispatchBtn.disabled = false;
        dispatchBtn.innerHTML = '<span>✅ Dispatch Batch</span>';
    }
});

// Refresh pending
refreshPendingBtn.addEventListener('click', function () {
    loadPendingWaybills();
});

// Auto-focus on waybill input
waybillInput.focus();

/**
 * Update counters display
 */
function updateCounters() {
    document.getElementById('validCount').textContent = counters.valid;
    document.getElementById('duplicateCount').textContent = counters.duplicate;
    document.getElementById('errorCount').textContent = counters.error;
    document.getElementById('totalCount').textContent = counters.total;
}

/**
 * Reset counters
 */
function resetCounters() {
    counters = { valid: 0, duplicate: 0, error: 0, total: 0 };
    updateCounters();
}

/**
 * Reset session
 */
function resetSession() {
    currentSession = null;
    sessionStatus.textContent = 'No Active Session';
    sessionStatus.className = 'status-indicator inactive';
    startBatchBtn.disabled = false;
    scanBtn.disabled = true;
    dispatchBtn.disabled = true;
    dispatchBtn.innerHTML = '<span>✅ Dispatch Batch</span>';
    document.getElementById('recentScans').innerHTML = '<p class="empty-state">Start a batch session to begin scanning</p>';

    // Hide manifest button on reset (unless we just finished a dispatch, handled separately)
    // Actually, we want to keep it visible until the next session starts.
    // But resetSession is called by 'Start Batch' implicitly via logic? No.
    // Let's hide it when starting a NEW batch.
    // validation: resetSession is usually called when finishing or cancelling.
    // For now, let's just ensure we don't clear it here if we want it to persist?
    // No, standard behavior is reset clears state.
    document.getElementById('manifestAction').style.display = 'none';

    resetCounters();
}

/**
 * Show scan result message
 */
function showResult(type, message, waybill = null, duration = 5000) {
    scanResult.className = `scan-result ${type}`;

    let html = `<strong>${message}</strong>`;

    if (waybill) {
        html += `<br><small>Receiver: ${waybill.receiver_name || 'N/A'} | Destination: ${waybill.destination || 'N/A'}</small>`;
    }

    scanResult.innerHTML = html;

    setTimeout(() => {
        scanResult.className = 'scan-result';
    }, duration);
}

/**
 * Add to recent scans list
 */
function addToRecentScans(result) {
    const recentScans = document.getElementById('recentScans');

    if (recentScans.querySelector('.empty-state')) {
        recentScans.innerHTML = '';
    }

    const scanItem = document.createElement('div');
    scanItem.className = `scan-item ${result.scan_type}`;

    const icon = result.scan_type === 'valid' ? '✅' : result.scan_type === 'duplicate' ? '🔄' : '❌';
    const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    scanItem.innerHTML = `
            <div class="scan-icon">${icon}</div>
            <div class="scan-details">
                <strong>${result.waybill_number}</strong>
                ${result.waybill ? `<br><small>${result.waybill.receiver_name || 'N/A'} - ${result.waybill.destination || 'N/A'}</small>` : ''}
                <br><small class="scan-time">${time}</small>
            </div>
        `;

    recentScans.insertBefore(scanItem, recentScans.firstChild);

    // Keep only last 20 scans
    while (recentScans.children.length > 20) {
        recentScans.removeChild(recentScans.lastChild);
    }
}

/**
 * Load pending waybills with pagination
 */
async function loadPendingWaybills() {
    const pendingList = document.getElementById('pendingList');
    const pendingCount = document.getElementById('pendingCount');
    const totalPendingCount = document.getElementById('totalPendingCount');
    const paginationControls = document.getElementById('paginationControls');
    const currentPageSpan = document.getElementById('currentPage');
    const totalPagesSpan = document.getElementById('totalPages');

    try {
        const response = await fetch(`/api/batch-scan/pending?limit=${rowsPerPage}&page=${currentPage}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await response.json();

        if (data.success) {
            pendingCount.textContent = data.count;
            totalPendingCount.textContent = data.total_count;
            currentPageSpan.textContent = data.page;
            totalPagesSpan.textContent = data.total_pages;

            // Show/hide pagination
            if (data.total_pages > 1) {
                paginationControls.style.display = 'flex';
            } else {
                paginationControls.style.display = 'none';
            }

            // Update pagination buttons
            prevPageBtn.disabled = !data.has_prev;
            nextPageBtn.disabled = !data.has_next;

            if (data.pending_waybills.length === 0) {
                pendingList.innerHTML = '<p class="empty-state">No pending waybills</p>';
                return;
            }

            pendingList.innerHTML = data.pending_waybills.map(waybill => `
                    <div class="pending-item" onclick="copyWaybill('${waybill.waybill_number}')">
                        <div class="waybill-number">${waybill.waybill_number}</div>
                        <div class="waybill-details">
                            <strong>${waybill.receiver_name || 'N/A'}</strong>
                            <small>${waybill.destination || 'N/A'}</small>
                        </div>
                    </div>
                `).join('');
        }
    } catch (error) {
        pendingList.innerHTML = '<p class="empty-state">Error loading pending waybills</p>';
    }
}

/**
 * Copy waybill number to input (global function)
 */
window.copyWaybill = function (waybillNumber) {
    waybillInput.value = waybillNumber;
    waybillInput.focus();
};

/**
 * Play beep sound
 */
/**
 * Play enhanced sound effects
 */
function playBeep(type) {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.connect(gain);
    gain.connect(ctx.destination);

    const now = ctx.currentTime;

    if (type === 'valid') {
        // Success: High-pitched Ding (Sine wave)
        osc.type = 'sine';
        osc.frequency.setValueAtTime(1000, now);
        osc.frequency.exponentialRampToValueAtTime(500, now + 0.1);

        gain.gain.setValueAtTime(0.5, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.3);

        osc.start(now);
        osc.stop(now + 0.3);

    } else if (type === 'duplicate') {
        // Duplicate: Double Beep (Square wave)
        osc.type = 'square';

        // First beep
        osc.frequency.setValueAtTime(600, now);
        gain.gain.setValueAtTime(0.3, now);
        gain.gain.linearRampToValueAtTime(0, now + 0.1);

        // Second beep
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'square';
        osc2.connect(gain2);
        gain2.connect(ctx.destination);

        osc2.frequency.setValueAtTime(600, now + 0.15);
        gain2.gain.setValueAtTime(0.3, now + 0.15);
        gain2.gain.linearRampToValueAtTime(0, now + 0.25);

        osc.start(now);
        osc.stop(now + 0.1);

        osc2.start(now + 0.15);
        osc2.stop(now + 0.25);

    } else if (type === 'error') {
        // Error: Low-pitched Buzz (Sawtooth)
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(150, now);
        osc.frequency.linearRampToValueAtTime(100, now + 0.4);

        gain.gain.setValueAtTime(0.5, now);
        gain.gain.linearRampToValueAtTime(0, now + 0.4);

        osc.start(now);
        osc.stop(now + 0.4);
    }
}
const markPendingBtn = document.getElementById('markPendingBtn');
const refreshIssuesBtn = document.getElementById('refreshIssuesBtn');

// ... existing code ...

// Mark Pending
markPendingBtn.addEventListener('click', async function () {
    if (!currentSession) return;

    const waybillNumber = prompt("Enter waybill number to mark as pending issue:");
    if (!waybillNumber) return;

    try {
        const response = await fetch('/api/batch-scan/mark-pending', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                waybill_number: waybillNumber,
                session_id: currentSession
            })
        });

        const result = await response.json();

        if (result.success) {
            showResult('warning', `⚠️ ${result.message}`);
            loadPendingWaybills();
            loadIssues();
        } else {
            showResult('error', `❌ ${result.message}`);
        }
    } catch (error) {
        showResult('error', `❌ Error: ${error.message}`);
    }
});

// Refresh Issues
if (refreshIssuesBtn) {
    refreshIssuesBtn.addEventListener('click', function () {
        loadIssues();
    });
}

// Enable Mark Pending button when session starts
// (Update inside startBatchBtn click handler)

// ...

/**
 * Load pending issues
 */
window.loadIssues = async function () {
    const issuesList = document.getElementById('issuesList');

    try {
        const response = await fetch('/api/batch-scan/issues', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();

        if (data.success) {
            if (data.issues.length === 0) {
                issuesList.innerHTML = '<p class="empty-state">No pending issues</p>';
                return;
            }

            issuesList.innerHTML = data.issues.map(waybill => `
                    <div class="pending-item issue-item" onclick="copyWaybill('${waybill.waybill_number}')">
                        <div class="waybill-number">${waybill.waybill_number}</div>
                        <div class="waybill-details">
                            <strong>${waybill.receiver_name || 'N/A'}</strong>
                            <small>Pending since: ${new Date(waybill.marked_pending_at).toLocaleDateString()}</small>
                        </div>
                        <div class="action-badge">Scan to Dispatch</div>
                    </div>
                `).join('');
        }
    } catch (error) {
        issuesList.innerHTML = '<p class="empty-state">Error loading issues</p>';
    }
};

/**
 * Resume pending issue
 */
window.resumePending = async function (waybillNumber) {
    if (!confirm(`Resume waybill ${waybillNumber} to batch?`)) return;

    try {
        const response = await fetch('/api/batch-scan/resume-pending', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ waybill_number: waybillNumber })
        });

        const result = await response.json();

        if (result.success) {
            showResult('success', `✅ ${result.message}`);
            loadIssues();
            loadPendingWaybills();
        } else {
            showResult('error', `❌ ${result.message}`);
        }
    } catch (error) {
        showResult('error', `❌ Error: ${error.message}`);
    }
};

// Initial load
// loadIssues(); // Only load when tab is clicked
