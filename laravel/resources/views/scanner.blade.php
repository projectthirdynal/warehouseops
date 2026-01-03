@extends('layouts.app')

@section('title', 'Scanner - Waybill System')
@section('page-title', 'Scanner')

@push('styles')
<style>
    /* Scanner Page Specific Styles */
    .scanner-hero {
        text-align: center;
        margin-bottom: var(--space-4);
        padding: var(--space-4) 0;
    }

    .scanner-hero h2 {
        font-size: var(--text-2xl);
        color: var(--accent-cyan);
        font-weight: var(--font-bold);
        margin-bottom: var(--space-2);
        letter-spacing: -0.02em;
    }

    .scanner-hero p {
        color: var(--text-tertiary);
        font-size: var(--text-sm);
    }

    /* Scan Input Area */
    .scan-input-wrapper {
        display: flex;
        gap: var(--space-3);
        max-width: 600px;
        margin: 0 auto var(--space-5);
    }

    .scan-input-wrapper input {
        flex: 1;
        height: 48px;
        font-size: var(--text-md);
        padding: 0 var(--space-4);
        border: 2px solid var(--border-input);
        background: var(--bg-tertiary);
    }

    .scan-input-wrapper input:focus {
        border-color: var(--accent-cyan);
        box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.1);
    }

    .scan-input-wrapper .btn {
        height: 48px;
        padding: 0 var(--space-6);
        font-size: var(--text-md);
    }

    /* Scanner By Field */
    .scanned-by-field {
        max-width: 280px;
        margin: 0 auto var(--space-6);
    }

    .scanned-by-field label {
        font-size: var(--text-xs);
        color: var(--text-tertiary);
        display: block;
        margin-bottom: var(--space-1);
        text-align: center;
    }

    .scanned-by-field input {
        text-align: center;
        height: 36px;
        font-size: var(--text-sm);
    }

    /* Refined Action Buttons */
    .action-buttons-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-3);
        max-width: 600px;
        margin: 0 auto var(--space-5);
    }

    .action-buttons-grid .btn {
        flex-direction: column;
        padding: var(--space-5) var(--space-4);
        min-height: 100px;
        border-radius: var(--radius-xl);
        gap: var(--space-2);
    }

    .action-buttons-grid .btn-icon-lg {
        font-size: 24px;
    }

    .action-buttons-grid .btn-label {
        font-size: var(--text-sm);
        font-weight: var(--font-semibold);
    }

    .action-buttons-grid .btn-sublabel {
        font-size: var(--text-2xs);
        opacity: 0.7;
    }

    /* Manifest Link */
    .manifest-action {
        text-align: center;
        margin-top: var(--space-4);
    }

    .manifest-action a {
        display: inline-flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-3) var(--space-5);
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.3);
        border-radius: var(--radius-lg);
        color: var(--accent-green);
        font-weight: var(--font-semibold);
        font-size: var(--text-sm);
        text-decoration: none;
        transition: all var(--transition-base);
    }

    .manifest-action a:hover {
        background: rgba(34, 197, 94, 0.15);
        transform: translateY(-1px);
    }

    /* Secondary Actions Row */
    .secondary-actions-row {
        display: flex;
        justify-content: center;
        gap: var(--space-3);
        margin-top: var(--space-5);
        padding-top: var(--space-5);
        border-top: 1px solid var(--border-subtle);
    }

    .secondary-actions-row .btn {
        min-width: 140px;
    }

    /* Pending Panel Refinements */
    .pending-panel {
        max-height: calc(100vh - 200px);
        display: flex;
        flex-direction: column;
    }

    .pending-panel .panel-header {
        flex-shrink: 0;
    }

    .pending-panel .pending-list {
        flex: 1;
        overflow-y: auto;
    }

    /* Improved Tab Buttons */
    .panel-tabs {
        flex-shrink: 0;
    }

    @media (max-width: 768px) {
        .action-buttons-grid {
            grid-template-columns: 1fr;
        }

        .scan-input-wrapper {
            flex-direction: column;
        }

        .secondary-actions-row {
            flex-direction: column;
        }

        .secondary-actions-row .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
    <!-- Upload Section -->
    <div id="uploadSection" style="{{ $batchReadyCount > 0 ? 'display: none;' : '' }}">
        <div class="upload-container">
            <div class="upload-form">
                <h2>Upload Excel for Batch Scanning</h2>
                <p style="color: var(--text-tertiary); margin-bottom: var(--space-5);">
                    Upload a file to prepare waybills for batch scanning
                </p>
                
                <div class="upload-notice">
                    <i class="fas fa-info-circle" style="margin-right: var(--space-2);"></i>
                    Waybills uploaded here will be immediately available for batch scanning.
                </div>
                
                <div id="uploadResult" class="upload-result"></div>
                
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="file-upload-area" id="dropZone">
                        <input type="file" id="fileInput" name="waybill_file" accept=".xlsx,.xls" hidden>
                        <div class="upload-icon">
                            <i class="fas fa-cloud-arrow-up"></i>
                        </div>
                        <p class="upload-text">Drag & drop your Excel file here</p>
                        <p class="upload-subtext">or</p>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-folder-open"></i>
                            Choose File
                        </button>
                        <p class="file-name" id="fileName"></p>
                    </div>

                    <div class="upload-info">
                        <h3><i class="fas fa-file-excel" style="margin-right: var(--space-2); color: var(--accent-green);"></i>File Requirements</h3>
                        <ul>
                            <li>Excel format: .xlsx or .xls</li>
                            <li>Maximum file size: 50MB</li>
                            <li>First row must contain column headers</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100" id="uploadBtn" disabled>
                        <i class="fas fa-upload"></i>
                        Upload for Batch Scanning
                    </button>

                    <div class="progress-bar" id="progressBar" style="display:none;">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scanner Section -->
    <div id="scannerSection" style="{{ $batchReadyCount > 0 ? '' : 'display: none;' }}">
        <!-- Batch Counters -->
        <div class="batch-counters">
            <div class="counter-card valid">
                <div class="counter-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="counter-content">
                    <h3 id="validCount">0</h3>
                    <p>Valid Scans</p>
                </div>
            </div>

            <div class="counter-card duplicate">
                <div class="counter-icon">
                    <i class="fas fa-clone"></i>
                </div>
                <div class="counter-content">
                    <h3 id="duplicateCount">0</h3>
                    <p>Duplicates</p>
                </div>
            </div>

            <div class="counter-card error">
                <div class="counter-icon">
                    <i class="fas fa-xmark"></i>
                </div>
                <div class="counter-content">
                    <h3 id="errorCount">0</h3>
                    <p>Not Found</p>
                </div>
            </div>

            <div class="counter-card total">
                <div class="counter-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="counter-content">
                    <h3 id="totalCount">0</h3>
                    <p>Total Scanned</p>
                </div>
            </div>
        </div>

        <div class="scanner-container">
            <!-- Left Panel: Scan Form -->
            <div class="scan-panel">
                <div class="scanner-hero">
                    <h2><i class="fas fa-barcode" style="margin-right: var(--space-3);"></i>Waybill Scanner</h2>
                    <p>Scan or enter waybill numbers to process shipments</p>
                    <div class="session-status" style="margin-top: var(--space-3);">
                        <span id="sessionStatus" class="status-indicator inactive">No Active Session</span>
                    </div>
                </div>

                <div id="scanResult" class="scan-result"></div>
                
                <form id="scanForm">
                    <input type="hidden" id="sessionId" value="">
                    
                    <div class="scan-input-wrapper">
                        <input 
                            type="text" 
                            id="waybillInput" 
                            name="waybill_number" 
                            placeholder="Enter or scan waybill number..."
                            autocomplete="off"
                            autofocus
                            required
                        >
                        <button type="submit" id="scanBtn" class="btn btn-primary" disabled>
                            <i class="fas fa-barcode"></i>
                            Scan
                        </button>
                    </div>

                    <div class="scanned-by-field">
                        <label for="scannedBy">Operator Name</label>
                        <input type="text" id="scannedBy" name="scanned_by" value="Scanner" required>
                    </div>

                    <div class="action-buttons-grid">
                        <button type="button" id="startBatchBtn" class="btn btn-primary">
                            <span class="btn-icon-lg"><i class="fas fa-play"></i></span>
                            <span class="btn-label">Start Batch</span>
                            <span class="btn-sublabel">Begin session</span>
                        </button>

                        <button type="button" id="markPendingBtn" class="btn btn-warning" disabled>
                            <span class="btn-icon-lg"><i class="fas fa-flag"></i></span>
                            <span class="btn-label">Mark Issue</span>
                            <span class="btn-sublabel">Flag problem</span>
                        </button>

                        <button type="button" id="dispatchBtn" class="btn btn-success" disabled>
                            <span class="btn-icon-lg"><i class="fas fa-truck-fast"></i></span>
                            <span class="btn-label">Dispatch</span>
                            <span class="btn-sublabel">Finalize batch</span>
                        </button>
                    </div>
                </form>

                <div id="manifestAction" class="manifest-action" style="display:none;">
                    <a id="printManifestBtn" href="#" target="_blank">
                        <i class="fas fa-print"></i>
                        Print Last Manifest
                    </a>
                </div>

                <div class="secondary-actions-row">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('uploadSection').style.display = 'block'; window.scrollTo(0,0);">
                        <i class="fas fa-plus"></i>
                        Add More Data
                    </button>
                    
                    <form action="{{ route('upload.batch.cancel') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all pending batch waybills?');" style="display:contents;">
                        @csrf
                        <button type="submit" class="btn btn-danger-outline">
                            <i class="fas fa-trash-can"></i>
                            Clear Batch
                        </button>
                    </form>
                </div>

                <!-- Recent Scans -->
                <div class="recent-scans-list">
                    <h3><i class="fas fa-clock-rotate-left" style="margin-right: var(--space-2); opacity: 0.5;"></i>Recent Scans</h3>
                    <div id="recentScans" class="scans-container">
                        <p class="empty-state">Start a batch session to begin scanning</p>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Pending & Issues -->
            <div class="pending-panel">
                <div class="panel-tabs">
                    <button class="tab-btn active" onclick="switchTab('pending')">Pending</button>
                    <button class="tab-btn" onclick="switchTab('issues')">Issues</button>
                    <button class="tab-btn" onclick="switchTab('history')">History</button>
                </div>

                <div id="pendingTab" class="tab-content" style="display: block; padding: var(--space-4);">
                    <div class="panel-header" style="border: none; padding: 0; margin-bottom: var(--space-3);">
                        <h2 style="font-size: var(--text-md);">Pending for Batch</h2>
                        <button id="refreshPendingBtn" class="btn-icon" title="Refresh">
                            <i class="fas fa-arrows-rotate"></i>
                        </button>
                    </div>

                    <div class="pending-controls">
                        <div class="pending-stats">
                            <span id="pendingCount">0</span> / <span id="totalPendingCount">0</span> showing
                        </div>
                        
                        <div class="rows-selector">
                            <select id="rowsPerPage">
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100" selected>100</option>
                            </select>
                        </div>
                    </div>

                    <div id="pendingList" class="pending-list">
                        <p class="loading">Loading...</p>
                    </div>

                    <div class="pagination-controls" id="paginationControls" style="display:none; border: none; padding-top: var(--space-3);">
                        <button id="prevPageBtn" class="btn-page" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="page-info">
                            <span id="currentPage">1</span> / <span id="totalPages">1</span>
                        </span>
                        <button id="nextPageBtn" class="btn-page" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Issues Tab -->
                <div id="issuesTab" class="tab-content" style="display: none; padding: var(--space-4);">
                    <div class="panel-header" style="border: none; padding: 0; margin-bottom: var(--space-3);">
                        <h2 style="font-size: var(--text-md);">Issues / On Hold</h2>
                        <button id="refreshIssuesBtn" class="btn-icon" title="Refresh">
                            <i class="fas fa-arrows-rotate"></i>
                        </button>
                    </div>

                    <div id="issuesList" class="pending-list">
                        <p class="loading">Loading issues...</p>
                    </div>
                </div>
                
                <!-- History Tab -->
                <div id="historyTab" class="tab-content" style="display: none; padding: var(--space-4);">
                    <div class="panel-header" style="border: none; padding: 0; margin-bottom: var(--space-3);">
                        <h2 style="font-size: var(--text-md);">Batch History</h2>
                        <button id="refreshHistoryBtn" class="btn-icon" title="Refresh">
                            <i class="fas fa-arrows-rotate"></i>
                        </button>
                    </div>

                    <div id="historyList" class="pending-list">
                        <p class="loading">Loading history...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const uploadBatchRoute = "{{ route('upload.batch.store') }}";
        const scannerRoute = "{{ route('scanner') }}";

        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tab + 'Tab').style.display = 'block';
            event.target.classList.add('active');
            
            if (tab === 'issues' && typeof loadIssues === 'function') {
                loadIssues();
            }
            if (tab === 'history' && typeof loadHistory === 'function') {
                loadHistory();
            }
        }
    </script>
    <script src="{{ asset('assets/js/upload-batch.js') }}"></script>
    <script src="{{ asset('assets/js/scanner-enhanced.js') }}"></script>
@endpush
