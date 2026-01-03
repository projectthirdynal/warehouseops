@extends('layouts.app')

@section('title', 'Import Leads - Waybill System')
@section('page-title', 'Import Leads')

@section('content')
<div class="leads-import-wrapper">
    <!-- Page Header -->
    <div class="section-header mb-5">
        <h2>
            <i class="fas fa-file-import section-header-icon" style="width: 22px; height: 22px;"></i>
            Import & Mine Leads
        </h2>
        <p>Expand your leads repository via file upload or database mining</p>
    </div>

    <div class="import-grid">
        <!-- File Import Card -->
        <div class="import-card">
            <div class="card-icon-header">
                <div class="icon-circle icon-primary">
                    <i class="fas fa-file-excel"></i>
                </div>
                <h3>Upload Spreadsheet</h3>
            </div>

            <p class="card-description">
                Import leads from an Excel or CSV file. The system will automatically skip duplicate phone numbers.
            </p>

            <form action="{{ route('leads.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="upload-zone" id="dropZone">
                    <input type="file" name="file" id="fileInput" class="d-none" required accept=".xlsx,.xls,.csv">
                    <label for="fileInput" class="upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span id="fileName">Select or drag & drop file</span>
                        <small>Supports XLSX, XLS, CSV</small>
                    </label>
                </div>

                <div class="requirements-box">
                    <h6>Required Columns</h6>
                    <div class="badge-group">
                        <span class="req-badge required">name</span>
                        <span class="req-badge required">phone</span>
                        <span class="req-badge optional">city</span>
                        <span class="req-badge optional">address</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-check-circle"></i>
                    Process Import
                </button>
            </form>
        </div>

        <!-- Database Mining Card -->
        <div class="import-card">
            <div class="card-icon-header">
                <div class="icon-circle icon-success">
                    <i class="fas fa-microchip"></i>
                </div>
                <h3>Internal Lead Mining</h3>
            </div>

            <p class="card-description">
                Extract customer data from existing Waybill history. Ideal for generating reorder lists.
            </p>

            <form action="{{ route('leads.mine') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" required value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" required value="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Product Filter</label>
                    <select name="previous_item">
                        <option value="">Extract All Products</option>
                        @foreach($productOptions as $product)
                            <option value="{{ $product }}">{{ $product }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Target Waybill Status</label>
                    <select name="status">
                        <option value="Delivered">Delivered (Recommended)</option>
                        <option value="Returned">Returned</option>
                        <option value="In Transit">In Transit</option>
                    </select>
                    <small class="form-hint">Only unique phone numbers not already leads will be extracted.</small>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100">
                    <i class="fas fa-bolt"></i>
                    Start Mining
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.leads-import-wrapper {
    max-width: 1100px;
    margin: 0 auto;
}

.import-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-5);
}

@media (max-width: 900px) {
    .import-grid {
        grid-template-columns: 1fr;
    }
}

.import-card {
    background: var(--bg-card);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6);
}

.card-icon-header {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    margin-bottom: var(--space-4);
}

.icon-circle {
    width: 56px;
    height: 56px;
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-xl);
}

.icon-primary {
    background: rgba(59, 130, 246, 0.15);
    color: var(--accent-blue);
}

.icon-success {
    background: rgba(34, 197, 94, 0.15);
    color: var(--accent-green);
}

.card-icon-header h3 {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    margin: 0;
}

.card-description {
    color: var(--text-tertiary);
    font-size: var(--text-sm);
    margin-bottom: var(--space-5);
    line-height: 1.6;
}

.upload-zone {
    margin-bottom: var(--space-4);
}

.upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--space-8) var(--space-6);
    border: 2px dashed var(--border-default);
    border-radius: var(--radius-xl);
    cursor: pointer;
    transition: all var(--transition-base);
    background: var(--bg-tertiary);
}

.upload-label:hover {
    border-color: var(--accent-blue);
    background: rgba(59, 130, 246, 0.05);
}

.upload-label i {
    font-size: 36px;
    color: var(--accent-blue);
    margin-bottom: var(--space-3);
    opacity: 0.6;
}

.upload-label span {
    font-weight: var(--font-medium);
    color: var(--text-primary);
    margin-bottom: var(--space-1);
}

.upload-label small {
    color: var(--text-muted);
    font-size: var(--text-xs);
}

.requirements-box {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    margin-bottom: var(--space-5);
}

.requirements-box h6 {
    font-size: var(--text-2xs);
    font-weight: var(--font-semibold);
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: var(--space-2);
}

.badge-group {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.req-badge {
    padding: 4px 12px;
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
}

.req-badge.required {
    background: rgba(34, 197, 94, 0.15);
    color: var(--accent-green);
    border: 1px solid rgba(34, 197, 94, 0.25);
}

.req-badge.optional {
    background: var(--bg-input);
    color: var(--text-tertiary);
    border: 1px solid var(--border-subtle);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-3);
}

.form-group {
    margin-bottom: var(--space-4);
}

.form-group label {
    display: block;
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    color: var(--text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--space-2);
}

.form-hint {
    display: block;
    font-size: var(--text-xs);
    color: var(--text-muted);
    margin-top: var(--space-1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const fileName = document.getElementById('fileName');
    const dropZone = document.getElementById('dropZone');

    fileInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            fileName.textContent = this.files[0].name;
            fileName.style.color = 'var(--accent-cyan)';
        }
    });

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.querySelector('.upload-label').style.borderColor = 'var(--accent-blue)', false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.querySelector('.upload-label').style.borderColor = '', false);
    });

    dropZone.addEventListener('drop', e => {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        if (files[0]) {
            fileName.textContent = files[0].name;
            fileName.style.color = 'var(--accent-cyan)';
        }
    });
});
</script>
@endsection
