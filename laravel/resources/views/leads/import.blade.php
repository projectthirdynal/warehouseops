@extends('layouts.app')

@section('content')
<div class="container-fluid px-0 leads-import-wrapper">
    {{-- Header --}}
    <div class="leads-header d-flex justify-content-between align-items-center px-4 py-4 border-bottom border-white border-opacity-10">
        <div class="d-flex align-items-center">
            <a href="{{ route('leads.index') }}" class="btn btn-outline-light border-white border-opacity-10 me-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="h4 text-white mb-0 fw-bold">Import & Mine Leads</h1>
                <p class="text-white-50 small mb-0">Expand your leads repository via file upload or database mining.</p>
            </div>
        </div>
    </div>

    <div class="px-4 py-5">
        <div class="row g-4">
            {{-- File Import Card --}}
            <div class="col-xl-6">
                <div class="glass-card h-100 p-4 p-md-5 rounded-4 border border-white border-opacity-10 shadow-lg">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-circle bg-primary bg-opacity-20 text-primary me-3">
                            <i class="fas fa-file-excel h4 mb-0"></i>
                        </div>
                        <h2 class="h5 text-white mb-0 fw-bold">Upload Spreadsheet</h2>
                    </div>

                    <p class="text-white-50 mb-5">
                        Import leads from an Excel or CSV file. The system will automatically skip duplicate phone numbers to maintain data integrity.
                    </p>

                    <form action="{{ route('leads.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="upload-zone mb-4" id="dropZone">
                            <input type="file" name="file" id="fileInput" class="d-none" required accept=".xlsx,.xls,.csv">
                            <label for="fileInput" class="w-100 py-5 border border-2 border-dashed border-white border-opacity-10 rounded-4 d-flex flex-column align-items-center justify-content-center cursor-pointer hover-bg-white-5">
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary opacity-50 mb-3"></i>
                                <span class="text-white fw-bold" id="fileName">Select or drag & drop file</span>
                                <span class="text-white-50 small mt-1">Supports XLSX, XLS, and CSV</span>
                            </label>
                        </div>

                        <div class="requirements-box bg-dark bg-opacity-50 p-4 rounded-4 mb-4 border border-white border-opacity-5">
                            <h6 class="text-white-50 small fw-bold text-uppercase tracking-wider mb-2">Column Requirements</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-3 py-2">name</span>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-3 py-2">phone</span>
                                <span class="badge bg-white bg-opacity-5 text-white-50 border border-white border-opacity-10 px-3 py-2 opacity-50">city (opt)</span>
                                <span class="badge bg-white bg-opacity-5 text-white-50 border border-white border-opacity-10 px-3 py-2 opacity-50">address (opt)</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-3 shadow-lg fw-bold">
                            <i class="fas fa-check-circle me-2"></i> Process Import
                        </button>
                    </form>
                </div>
            </div>

            {{-- Database Mining Card --}}
            <div class="col-xl-6">
                <div class="glass-card h-100 p-4 p-md-5 rounded-4 border border-white border-opacity-10 shadow-lg">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-circle bg-success bg-opacity-20 text-success me-3">
                            <i class="fas fa-microchip h4 mb-0"></i>
                        </div>
                        <h2 class="h5 text-white mb-0 fw-bold">Internal Lead Mining</h2>
                    </div>

                    <p class="text-white-50 mb-5">
                        Extract customer data directly from existing Waybill history. This is ideal for generating reorder lists or targeting specific regions.
                    </p>

                    <form action="{{ route('leads.mine') }}" method="POST">
                        @csrf
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small fw-bold text-uppercase tracking-wider">Start Date</label>
                                <input type="date" name="start_date" class="form-control bg-dark border-white border-opacity-10 text-white rounded-3 py-3" required value="{{ date('Y-m-01') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small fw-bold text-uppercase tracking-wider">End Date</label>
                                <input type="date" name="end_date" class="form-control bg-dark border-white border-opacity-10 text-white rounded-3 py-3" required value="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-white-50 small fw-bold text-uppercase tracking-wider">Product Filter</label>
                            <select name="previous_item" class="form-select bg-dark border-white border-opacity-10 text-white rounded-3 py-3">
                                <option value="">Extract All Products</option>
                                @foreach($productOptions as $product)
                                    <option value="{{ $product }}">{{ $product }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-5">
                            <label class="form-label text-white-50 small fw-bold text-uppercase tracking-wider">Target Waybill Status</label>
                            <select name="status" class="form-select bg-dark border-white border-opacity-10 text-white rounded-3 py-3">
                                <option value="Delivered">Delivered (Recommended for Reorders)</option>
                                <option value="Returned">Returned</option>
                                <option value="In Transit">In Transit</option>
                            </select>
                            <div class="form-text text-white-50 opacity-50 mt-2">The system will only extract unique phone numbers that aren't already leads.</div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 py-3 rounded-3 shadow-lg fw-bold border-0" style="background: #2e7d32;">
                            <i class="fas fa-bolt me-2"></i> Start Mining Process
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --bg-main: #131722;
}

.leads-import-wrapper {
    background-color: var(--bg-main);
    color: #f2f3f7;
    min-height: 100vh;
}

.glass-card {
    background: rgba(255, 255, 255, 0.02);
    backdrop-filter: blur(10px);
}

.icon-circle {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
}

.hover-bg-white-5:hover {
    background: rgba(255, 255, 255, 0.05);
}

.cursor-pointer {
    cursor: pointer;
}

.tracking-wider {
    letter-spacing: 0.08em;
}

/* Custom Date Inputs & Selects */
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
}

.form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff80' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
}

.leads-import-wrapper { animation: fadeIn 0.4s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const fileName = document.getElementById('fileName');
    const dropZone = document.getElementById('dropZone');

    fileInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            fileName.textContent = this.files[0].name;
            fileName.classList.add('text-primary');
        }
    });

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('opacity-100'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('opacity-100'), false);
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        if (files[0]) {
            fileName.textContent = files[0].name;
            fileName.classList.add('text-primary');
        }
    });
});
</script>
@endsection
