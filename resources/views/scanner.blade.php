@extends('layouts.app')

@section('title', 'Scanner - Waybill System')

@push('styles')
@push('styles')
    <!-- Styles merged into style.css -->
@endpush
@endpush

@section('content')
    <!-- Upload Section (Visible when no batch is ready) -->
    <div id="uploadSection" style="{{ $batchReadyCount > 0 ? 'display: none;' : '' }}">
        <div class="upload-container">
            <div class="upload-form">
                <h2>Upload Excel File for Batch Scanning</h2>
                
                <div class="upload-notice">
                    <strong>📌 Note:</strong> Waybills uploaded here will be immediately available for batch scanning.
                </div>
                
                <div id="uploadResult" class="upload-result"></div>
                
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="file-upload-area" id="dropZone">
                        <input type="file" id="fileInput" name="waybill_file" accept=".xlsx,.xls" hidden>
                        <div class="upload-icon">📁</div>
                        <p class="upload-text">Drag & drop your XLSX file here</p>
                        <p class="upload-subtext">or</p>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('fileInput').click()">
                            Choose File
                        </button>
                        <p class="file-name" id="fileName"></p>
                    </div>

                    <div class="upload-info">
                        <h3>File Format Requirements</h3>
                        <ul>
                            <li>File type: Excel (.xlsx or .xls)</li>
                            <li>Maximum size: 50MB</li>
                            <li>First row must contain column headers</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
                        <span>⬆️ Upload for Batch Scanning</span>
                    </button>

                    <div class="progress-bar" id="progressBar" style="display:none;">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scanner Section (Visible when batch is ready) -->
    <div id="scannerSection" style="{{ $batchReadyCount > 0 ? '' : 'display: none;' }}">
        <!-- Batch Counters -->
        <div class="batch-counters">
            <div class="counter-card valid">
                <div class="counter-icon">✅</div>
                <div class="counter-content">
                    <h3 id="validCount">0</h3>
                    <p>Valid Scans</p>
                </div>
            </div>

            <div class="counter-card duplicate">
                <div class="counter-icon">🔄</div>
                <div class="counter-content">
                    <h3 id="duplicateCount">0</h3>
                    <p>Duplicates</p>
                </div>
            </div>

            <div class="counter-card error">
                <div class="counter-icon"></div>
                <div class="counter-content">
                    <h3 id="errorCount">0</h3>
                    <p>Errors (Not Listed)</p>
                </div>
            </div>

            <div class="counter-card total">
                <div class="counter-icon"></div>
                <div class="counter-content">
                    <h3 id="totalCount">0</h3>
                    <p>Total (Excl. Duplicates)</p>
                </div>
            </div>
        </div>

        <div class="scanner-container">
            <!-- Left Panel: Scan Form -->
            <div class="scan-panel">
                <div class="panel-header" style="text-align: center; margin-bottom: 30px;">
                    <h2 style="font-size: 2rem; color: #6366f1; margin: 0;">Waybill Scanner</h2>
                    <p style="color: #94a3b8; margin-top: 10px;">Scan or enter waybill number to track shipment</p>
                    <div class="session-status" style="margin-top: 10px;">
                        <span id="sessionStatus" class="status-indicator inactive">No Active Session</span>
                    </div>
                </div>

                <div id="scanResult" class="scan-result"></div>
                
                <form id="scanForm" style="max-width: 600px; margin: 0 auto;">
                    <input type="hidden" id="sessionId" value="">
                    
                    <div class="form-group" style="position: relative; margin-bottom: 30px;">
                        <label for="waybillInput" style="display: none;">Waybill Number</label> <!-- Hidden label for cleaner look -->
                        <div style="display: flex; gap: 10px;">
                            <input 
                                type="text" 
                                id="waybillInput" 
                                name="waybill_number" 
                                placeholder="Enter waybill number (e.g. WB-001...)"
                                autocomplete="off"
                                autofocus
                                required
                                style="padding: 15px 20px; font-size: 1.1rem; flex: 1; border: 2px solid #334155; background: #0f172a; color: white; border-radius: 8px;"
                            >
                            <button type="submit" id="scanBtn" class="btn btn-primary" style="padding: 0 30px; font-size: 1.1rem;" disabled>
                                <span>Scan</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="scannedBy" style="color: #94a3b8; font-size: 0.9rem;">Scanned By</label>
                        <input 
                            type="text" 
                            id="scannedBy" 
                            name="scanned_by" 
                            value="Scanner"
                            required
                            style="padding: 10px; background: #1e293b; border: 1px solid #334155; color: #cbd5e1; border-radius: 6px;"
                        >
                    </div>

                    <div class="scan-actions-grid">
                        <button type="button" id="startBatchBtn" class="btn btn-primary action-btn">
                            <span class="btn-icon-large">🚀</span>
                            <div class="btn-text">
                                <span class="btn-title">Start Batch</span>
                                <span class="btn-desc">Begin new session</span>
                            </div>
                        </button>

                        <button type="button" id="markPendingBtn" class="btn btn-warning action-btn" disabled>
                            <span class="btn-icon-large">⚠️</span>
                            <div class="btn-text">
                                <span class="btn-title">Mark Issue</span>
                                <span class="btn-desc">Flag problem</span>
                            </div>
                        </button>

                        <button type="button" id="dispatchBtn" class="btn btn-success action-btn" disabled>
                            <span class="btn-icon-large">✅</span>
                            <div class="btn-text">
                                <span class="btn-title">Dispatch</span>
                                <span class="btn-desc">Finalize batch</span>
                            </div>
                        </button>
                    </div>
                </form>

                <div class="action-divider">
                    <span>OR</span>
                </div>

                <div class="secondary-actions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 20px;">
                    <button type="button" id="addMoreDataBtn" class="btn btn-secondary" onclick="document.getElementById('uploadSection').style.display = 'block'; window.scrollTo(0,0);">
                        <span>➕ Add Data</span>
                    </button>
                    
                    <form action="{{ route('upload.batch.cancel') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all pending batch waybills?');" style="display:contents;">
                        @csrf
                        <button type="submit" class="btn btn-danger-outline">
                            <span>🗑️ Clear Batch</span>
                        </button>
                    </form>
                </div>

                <!-- Recent Scans -->
                <div class="recent-scans-list">
                    <h3>Recent Scans</h3>
                    <div id="recentScans" class="scans-container">
                        <p class="empty-state">Start a batch session to begin scanning</p>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Pending Waybills & Issues -->
            <div class="pending-panel">
                <div class="panel-tabs">
                    <button class="tab-btn active" onclick="switchTab('pending')">Pending</button>
                    <button class="tab-btn" onclick="switchTab('issues')">Issues / On Hold</button>
                </div>

                <div id="pendingTab" class="tab-content" style="display: block;">
                    <div class="panel-header">
                        <h2>Pending for Batch</h2>
                        <button id="refreshPendingBtn" class="btn-icon" title="Refresh">🔄</button>
                    </div>

                    <div class="panel-description">
                        <small>Waybills pending dispatch</small>
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

                    <div class="pagination-controls" id="paginationControls" style="display:none;">
                        <button id="prevPageBtn" class="btn-page" disabled>←</button>
                        <span class="page-info">
                            <span id="currentPage">1</span> / <span id="totalPages">1</span>
                        </span>
                        <button id="nextPageBtn" class="btn-page" disabled>→</button>
                    </div>
                </div>

                <!-- Issues Tab -->
                <div id="issuesTab" class="tab-content" style="display: none;">
                    <div class="panel-header">
                        <h2>Issues / On Hold</h2>
                        <button id="refreshIssuesBtn" class="btn-icon" title="Refresh">🔄</button>
                    </div>
                    
                    <div class="panel-description">
                        <small>Waybills needing attention</small>
                    </div>

                    <div id="issuesList" class="pending-list">
                        <p class="loading">Loading issues...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Pass route to JS
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
        }
    </script>
    <script src="{{ asset('assets/js/upload-batch.js') }}"></script>
    <script src="{{ asset('assets/js/scanner-enhanced.js') }}"></script>
@endpush
