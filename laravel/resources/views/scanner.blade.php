@extends('layouts.app')

@section('title', 'Scanner - Waybill System')
@section('page-title', 'Scanner')

@section('content')
    <!-- Upload Section -->
    <div id="uploadSection" class="{{ $batchReadyCount > 0 ? 'hidden' : '' }}">
        <div class="max-w-2xl mx-auto">
            <x-card>
                <div class="text-center mb-5">
                    <h2 class="text-xl font-semibold text-white">Upload Excel for Batch Scanning</h2>
                    <p class="text-dark-100 text-sm mt-2">Upload a file to prepare waybills for batch scanning</p>
                </div>

                <x-alert type="info" class="mb-5">
                    Waybills uploaded here will be immediately available for batch scanning.
                </x-alert>

                <div id="uploadResult" class="mb-4 hidden"></div>

                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="border-2 border-dashed border-dark-400 rounded-xl p-12 text-center transition-all duration-200 cursor-pointer hover:border-info-500 hover:bg-info-50 mb-6" id="dropZone">
                        <input type="file" id="fileInput" name="waybill_file" accept=".xlsx,.xls" hidden>
                        <div class="text-5xl text-info-500 mb-4">
                            <i class="fas fa-cloud-arrow-up"></i>
                        </div>
                        <p class="text-lg font-medium text-white mb-2">Drag & drop your Excel file here</p>
                        <p class="text-sm text-dark-100 mb-4">or</p>
                        <x-button type="button" variant="secondary" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-folder-open"></i>
                            Choose File
                        </x-button>
                        <p class="text-sm font-semibold text-cyan-500 mt-4" id="fileName"></p>
                    </div>

                    <div class="bg-info-50 border border-info-200 rounded-xl p-4 mb-5">
                        <h3 class="text-sm font-semibold text-info-500 mb-2">
                            <i class="fas fa-file-excel text-success-500 mr-2"></i>File Requirements
                        </h3>
                        <ul class="text-xs text-slate-300 space-y-1">
                            <li>Excel format: .xlsx or .xls</li>
                            <li>Maximum file size: 50MB</li>
                            <li>First row must contain column headers</li>
                        </ul>
                    </div>

                    <x-button type="submit" variant="primary" size="lg" class="w-full" id="uploadBtn" disabled>
                        <i class="fas fa-upload"></i>
                        Upload for Batch Scanning
                    </x-button>

                    <div class="w-full h-1.5 bg-dark-950 rounded-full overflow-hidden mt-4 hidden" id="progressBar">
                        <div class="h-full bg-gradient-to-r from-info-500 to-cyan-500 rounded-full transition-all duration-300" id="progressFill" style="width: 0%"></div>
                    </div>
                </form>
            </x-card>
        </div>
    </div>

    <!-- Scanner Section -->
    <div id="scannerSection" class="{{ $batchReadyCount > 0 ? '' : 'hidden' }}">
        <!-- Batch Counters -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-dark-700 border border-dark-500 rounded-xl p-4 flex items-center gap-3 hover:border-success-500 transition-colors">
                <div class="w-11 h-11 flex items-center justify-center bg-dark-800 rounded-lg border border-dark-600 text-success-500 text-xl">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <h3 id="validCount" class="text-2xl font-bold text-success-500">0</h3>
                    <p class="text-xs text-dark-100 uppercase tracking-wider">Valid Scans</p>
                </div>
            </div>

            <div class="bg-dark-700 border border-dark-500 rounded-xl p-4 flex items-center gap-3 hover:border-warning-500 transition-colors">
                <div class="w-11 h-11 flex items-center justify-center bg-dark-800 rounded-lg border border-dark-600 text-warning-500 text-xl">
                    <i class="fas fa-clone"></i>
                </div>
                <div>
                    <h3 id="duplicateCount" class="text-2xl font-bold text-warning-500">0</h3>
                    <p class="text-xs text-dark-100 uppercase tracking-wider">Duplicates</p>
                </div>
            </div>

            <div class="bg-dark-700 border border-dark-500 rounded-xl p-4 flex items-center gap-3 hover:border-error-500 transition-colors">
                <div class="w-11 h-11 flex items-center justify-center bg-dark-800 rounded-lg border border-dark-600 text-error-500 text-xl">
                    <i class="fas fa-xmark"></i>
                </div>
                <div>
                    <h3 id="errorCount" class="text-2xl font-bold text-error-500">0</h3>
                    <p class="text-xs text-dark-100 uppercase tracking-wider">Not Found</p>
                </div>
            </div>

            <div class="bg-dark-700 border border-dark-500 rounded-xl p-4 flex items-center gap-3 hover:border-cyan-500 transition-colors">
                <div class="w-11 h-11 flex items-center justify-center bg-dark-800 rounded-lg border border-dark-600 text-cyan-500 text-xl">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <h3 id="totalCount" class="text-2xl font-bold text-cyan-500">0</h3>
                    <p class="text-xs text-dark-100 uppercase tracking-wider">Total Scanned</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
            <!-- Left Panel: Scan Form (3 columns) -->
            <div class="lg:col-span-3 bg-dark-700 border border-dark-500 rounded-xl p-5">
                <div class="text-center mb-4 py-4">
                    <h2 class="text-2xl font-bold text-cyan-500 mb-2">
                        <i class="fas fa-barcode mr-3"></i>Waybill Scanner
                    </h2>
                    <p class="text-dark-100 text-sm">Scan or enter waybill numbers to process shipments</p>
                    <div class="mt-3">
                        <span id="sessionStatus" class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-dark-600 text-dark-100 border border-dark-500">
                            No Active Session
                        </span>
                    </div>
                </div>

                <div id="scanResult" class="mb-4 hidden"></div>

                <form id="scanForm">
                    <input type="hidden" id="sessionId" value="">

                    <div class="flex flex-col sm:flex-row gap-3 max-w-xl mx-auto mb-5">
                        <input
                            type="text"
                            id="waybillInput"
                            name="waybill_number"
                            placeholder="Enter or scan waybill number..."
                            autocomplete="off"
                            autofocus
                            required
                            class="flex-1 h-12 px-4 text-base bg-dark-800 border-2 border-dark-400 rounded-lg text-white placeholder-dark-100 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all"
                        >
                        <x-button type="submit" variant="primary" id="scanBtn" disabled class="h-12 px-6">
                            <i class="fas fa-barcode"></i>
                            Scan
                        </x-button>
                    </div>

                    <div class="max-w-xs mx-auto mb-6 text-center">
                        <label for="scannedBy" class="text-xs text-dark-100 block mb-1">Operator Name</label>
                        <input type="text" id="scannedBy" name="scanned_by" value="Scanner" required class="w-full h-9 text-center text-sm bg-dark-800 border border-dark-400 rounded-lg text-white">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-xl mx-auto mb-5">
                        <x-button type="button" id="startBatchBtn" variant="primary" class="flex-col py-5 min-h-[100px] rounded-xl">
                            <span class="text-2xl mb-1"><i class="fas fa-play"></i></span>
                            <span class="font-semibold">Start Batch</span>
                            <span class="text-[10px] opacity-70">Begin session</span>
                        </x-button>

                        <x-button type="button" id="markPendingBtn" variant="warning" class="flex-col py-5 min-h-[100px] rounded-xl" disabled>
                            <span class="text-2xl mb-1"><i class="fas fa-flag"></i></span>
                            <span class="font-semibold">Mark Issue</span>
                            <span class="text-[10px] opacity-70">Flag problem</span>
                        </x-button>

                        <x-button type="button" id="dispatchBtn" variant="success" class="flex-col py-5 min-h-[100px] rounded-xl" disabled>
                            <span class="text-2xl mb-1"><i class="fas fa-truck-fast"></i></span>
                            <span class="font-semibold">Dispatch</span>
                            <span class="text-[10px] opacity-70">Finalize batch</span>
                        </x-button>
                    </div>
                </form>

                <div id="manifestAction" class="text-center mt-4 hidden">
                    <a id="printManifestBtn" href="#" target="_blank" class="inline-flex items-center gap-2 px-5 py-3 bg-success-100 border border-success-200 rounded-lg text-success-500 font-semibold text-sm hover:bg-success-100/80 transition-colors">
                        <i class="fas fa-print"></i>
                        Print Last Manifest
                    </a>
                </div>

                <div class="flex flex-col sm:flex-row justify-center gap-3 mt-5 pt-5 border-t border-dark-600">
                    <x-button type="button" variant="secondary" onclick="document.getElementById('uploadSection').classList.remove('hidden'); window.scrollTo(0,0);">
                        <i class="fas fa-plus"></i>
                        Add More Data
                    </x-button>

                    <form action="{{ route('upload.batch.cancel') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all pending batch waybills?');" class="contents">
                        @csrf
                        <x-button type="submit" variant="danger-outline">
                            <i class="fas fa-trash-can"></i>
                            Clear Batch
                        </x-button>
                    </form>
                </div>

                <!-- Recent Scans -->
                <div class="mt-5 pt-4 border-t border-dark-600">
                    <h3 class="text-sm font-semibold text-white mb-3">
                        <i class="fas fa-clock-rotate-left mr-2 opacity-50"></i>Recent Scans
                    </h3>
                    <div id="recentScans" class="max-h-72 overflow-y-auto">
                        <p class="text-center py-8 text-dark-100 text-sm">Start a batch session to begin scanning</p>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Pending & Issues (2 columns) -->
            <div class="lg:col-span-2 bg-dark-700 border border-dark-500 rounded-xl overflow-hidden flex flex-col max-h-[calc(100vh-200px)]">
                <div class="flex bg-dark-800 border-b border-dark-500 rounded-t-xl gap-1 p-1">
                    <button type="button" class="tab-btn flex-1 px-3 py-2 text-xs font-medium rounded-md transition-colors bg-info-500/10 text-info-500" onclick="switchTab('pending', this)">Pending</button>
                    <button type="button" class="tab-btn flex-1 px-3 py-2 text-xs font-medium rounded-md transition-colors text-dark-100 hover:bg-dark-700" onclick="switchTab('issues', this)">Issues</button>
                    <button type="button" class="tab-btn flex-1 px-3 py-2 text-xs font-medium rounded-md transition-colors text-dark-100 hover:bg-dark-700" onclick="switchTab('history', this)">History</button>
                </div>

                <div id="pendingTab" class="tab-content flex-1 flex flex-col p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-white">Pending for Batch</h2>
                        <button type="button" id="refreshPendingBtn" class="p-2 text-dark-100 hover:text-white hover:bg-dark-600 rounded-lg transition-colors" aria-label="Refresh list">
                            <i class="fas fa-arrows-rotate"></i>
                        </button>
                    </div>

                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-dark-100">
                            <span id="pendingCount">0</span> / <span id="totalPendingCount">0</span> showing
                        </div>
                        <select id="rowsPerPage" class="h-9 px-3 bg-dark-800 border border-dark-400 rounded-lg text-sm text-white min-w-[80px]">
                            <option value="10">10</option>
                            <option value="50">50</option>
                            <option value="100" selected>100</option>
                        </select>
                    </div>

                    <div id="pendingList" class="flex-1 overflow-y-auto">
                        <p class="text-center py-8 text-dark-100 text-sm">Loading...</p>
                    </div>

                    <div class="flex items-center justify-center gap-3 pt-3 border-t border-dark-600 mt-auto hidden" id="paginationControls">
                        <x-button type="button" id="prevPageBtn" variant="secondary" size="icon-sm" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </x-button>
                        <span class="text-sm text-dark-100">
                            <span id="currentPage">1</span> / <span id="totalPages">1</span>
                        </span>
                        <x-button type="button" id="nextPageBtn" variant="secondary" size="icon-sm" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </x-button>
                    </div>
                </div>

                <!-- Issues Tab -->
                <div id="issuesTab" class="tab-content hidden flex-1 flex flex-col p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-white">Issues / On Hold</h2>
                        <button type="button" id="refreshIssuesBtn" class="p-2 text-dark-100 hover:text-white hover:bg-dark-600 rounded-lg transition-colors" aria-label="Refresh issues">
                            <i class="fas fa-arrows-rotate"></i>
                        </button>
                    </div>
                    <div id="issuesList" class="flex-1 overflow-y-auto">
                        <p class="text-center py-8 text-dark-100 text-sm">Loading issues...</p>
                    </div>
                </div>

                <!-- History Tab -->
                <div id="historyTab" class="tab-content hidden flex-1 flex flex-col p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-white">Batch History</h2>
                        <button type="button" id="refreshHistoryBtn" class="p-2 text-dark-100 hover:text-white hover:bg-dark-600 rounded-lg transition-colors" aria-label="Refresh history">
                            <i class="fas fa-arrows-rotate"></i>
                        </button>
                    </div>
                    <div id="historyList" class="flex-1 overflow-y-auto">
                        <p class="text-center py-8 text-dark-100 text-sm">Loading history...</p>
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

        function switchTab(tab, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('bg-info-500/10', 'text-info-500');
                el.classList.add('text-dark-100');
            });

            document.getElementById(tab + 'Tab').classList.remove('hidden');
            btn.classList.remove('text-dark-100');
            btn.classList.add('bg-info-500/10', 'text-info-500');

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
