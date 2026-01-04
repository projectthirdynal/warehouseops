@extends('layouts.app')

@section('title', 'Import Leads - Waybill System')
@section('page-title', 'Import Leads')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ============================================
   LEADS IMPORT - FRESH REDESIGN
   ============================================ */

:root {
    --import-bg: #0a0c10;
    --import-surface: #12151c;
    --import-surface-2: #1a1e28;
    --import-border: rgba(255, 255, 255, 0.06);
    --import-text: #f1f3f5;
    --import-text-muted: #8b919e;
    --import-text-dim: #5a5f6d;
    --import-accent: #6366f1;
    --import-accent-soft: rgba(99, 102, 241, 0.12);
    --import-cyan: #22d3ee;
    --import-emerald: #34d399;
    --import-radius: 16px;
    --import-transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.import-module {
    font-family: 'Plus Jakarta Sans', sans-serif;
    min-height: 100vh;
    background: var(--import-bg);
    padding: 32px;
}

/* --- Page Header --- */
.import-header {
    text-align: center;
    max-width: 600px;
    margin: 0 auto 48px;
}

.import-header-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--import-accent), #818cf8);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 24px;
    color: white;
    box-shadow: 0 10px 40px rgba(99, 102, 241, 0.3);
}

.import-header h1 {
    font-size: 28px;
    font-weight: 800;
    color: var(--import-text);
    margin: 0 0 12px 0;
    letter-spacing: -0.02em;
}

.import-header p {
    font-size: 15px;
    color: var(--import-text-muted);
    margin: 0;
    line-height: 1.6;
}

/* --- Cards Grid --- */
.import-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 28px;
    max-width: 1000px;
    margin: 0 auto;
}

@media (max-width: 900px) {
    .import-grid {
        grid-template-columns: 1fr;
    }
}

/* --- Import Card --- */
.import-card {
    background: var(--import-surface);
    border: 1px solid var(--import-border);
    border-radius: var(--import-radius);
    padding: 32px;
    position: relative;
    overflow: hidden;
}

.import-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--import-accent), #818cf8);
    opacity: 0;
    transition: opacity var(--import-transition);
}

.import-card:hover::before {
    opacity: 1;
}

.import-card.mining::before {
    background: linear-gradient(90deg, var(--import-emerald), #4ade80);
}

/* Card Header */
.card-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 24px;
}

.card-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.card-icon.upload {
    background: var(--import-accent-soft);
    color: var(--import-accent);
}

.card-icon.mining {
    background: rgba(52, 211, 153, 0.12);
    color: var(--import-emerald);
}

.card-header-text h2 {
    font-size: 18px;
    font-weight: 700;
    color: var(--import-text);
    margin: 0 0 6px 0;
}

.card-header-text p {
    font-size: 13px;
    color: var(--import-text-muted);
    margin: 0;
    line-height: 1.5;
}

/* --- Upload Zone --- */
.upload-zone {
    margin-bottom: 24px;
}

.upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 24px;
    border: 2px dashed var(--import-border);
    border-radius: 14px;
    cursor: pointer;
    transition: all var(--import-transition);
    background: var(--import-surface-2);
}

.upload-label:hover {
    border-color: var(--import-accent);
    background: rgba(99, 102, 241, 0.05);
}

.upload-label.active {
    border-color: var(--import-accent);
    border-style: solid;
    background: rgba(99, 102, 241, 0.08);
}

.upload-icon {
    width: 56px;
    height: 56px;
    background: var(--import-accent-soft);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: var(--import-accent);
    margin-bottom: 16px;
}

.upload-text {
    font-size: 14px;
    font-weight: 600;
    color: var(--import-text);
    margin-bottom: 6px;
}

.upload-hint {
    font-size: 12px;
    color: var(--import-text-dim);
}

/* --- Requirements Box --- */
.requirements-box {
    background: var(--import-surface-2);
    border: 1px solid var(--import-border);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
}

.requirements-title {
    font-size: 10px;
    font-weight: 700;
    color: var(--import-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 12px;
}

.requirements-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.req-tag {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}

.req-tag.required {
    background: rgba(52, 211, 153, 0.12);
    color: var(--import-emerald);
    border: 1px solid rgba(52, 211, 153, 0.2);
}

.req-tag.optional {
    background: var(--import-surface);
    color: var(--import-text-muted);
    border: 1px solid var(--import-border);
}

/* --- Form Styles --- */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: var(--import-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 10px;
}

.form-input,
.form-select {
    width: 100%;
    background: var(--import-surface-2);
    border: 1px solid var(--import-border);
    border-radius: 10px;
    padding: 12px 14px;
    color: var(--import-text);
    font-size: 13px;
    font-family: inherit;
    transition: all var(--import-transition);
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: var(--import-accent);
    box-shadow: 0 0 0 3px var(--import-accent-soft);
}

.form-hint {
    display: block;
    font-size: 11px;
    color: var(--import-text-dim);
    margin-top: 8px;
}

/* --- Submit Button --- */
.submit-btn {
    width: 100%;
    padding: 14px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all var(--import-transition);
}

.submit-btn.primary {
    background: var(--import-accent);
    color: white;
}

.submit-btn.primary:hover {
    background: #5558e3;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.35);
}

.submit-btn.success {
    background: var(--import-emerald);
    color: #052e16;
}

.submit-btn.success:hover {
    background: #2ed891;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(52, 211, 153, 0.35);
}

.submit-btn i {
    font-size: 14px;
}

/* --- Divider --- */
.import-divider {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 40px 0;
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
}

.import-divider::before,
.import-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--import-border);
}

.import-divider span {
    font-size: 11px;
    font-weight: 700;
    color: var(--import-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.15em;
}

/* --- Back Link --- */
.back-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 32px;
    font-size: 13px;
    color: var(--import-text-muted);
    text-decoration: none;
    transition: color var(--import-transition);
}

.back-link:hover {
    color: var(--import-text);
}

/* Responsive */
@media (max-width: 600px) {
    .import-module {
        padding: 20px;
    }
    
    .import-card {
        padding: 24px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@section('content')
<div class="import-module">
    <!-- Page Header -->
    <div class="import-header">
        <div class="import-header-icon">
            <i class="fas fa-file-import"></i>
        </div>
        <h1>Import Leads</h1>
        <p>Expand your leads repository via file upload or mine existing customer data from waybill history</p>
    </div>

    <!-- Cards Grid -->
    <div class="import-grid">
        <!-- File Upload Card -->
        <div class="import-card">
            <div class="card-header">
                <div class="card-icon upload">
                    <i class="fas fa-file-excel"></i>
                </div>
                <div class="card-header-text">
                    <h2>Upload Spreadsheet</h2>
                    <p>Import leads from Excel or CSV. Duplicates are automatically skipped.</p>
                </div>
            </div>

            <form action="{{ route('leads.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="upload-zone">
                    <input type="file" name="file" id="fileInput" class="d-none" required accept=".xlsx,.xls,.csv">
                    <label for="fileInput" class="upload-label" id="dropZone">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <span class="upload-text" id="fileName">Select or drag file here</span>
                        <span class="upload-hint">XLSX, XLS, CSV supported</span>
                    </label>
                </div>

                <div class="requirements-box">
                    <div class="requirements-title">Required Columns</div>
                    <div class="requirements-tags">
                        <span class="req-tag required">name</span>
                        <span class="req-tag required">phone</span>
                        <span class="req-tag optional">city</span>
                        <span class="req-tag optional">address</span>
                        <span class="req-tag optional">previous_item</span>
                    </div>
                </div>

                <button type="submit" class="submit-btn primary">
                    <i class="fas fa-check-circle"></i>
                    Process Import
                </button>
            </form>
        </div>

        <!-- Database Mining Card -->
        <div class="import-card mining">
            <div class="card-header">
                <div class="card-icon mining">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="card-header-text">
                    <h2>Internal Mining</h2>
                    <p>Extract customer data from waybill history for reorder campaigns.</p>
                </div>
            </div>

            <form action="{{ route('leads.mine') }}" method="POST">
                @csrf
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-input" required value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-input" required value="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Product Filter</label>
                    <select name="previous_item" class="form-select">
                        <option value="">All Products</option>
                        @foreach($productOptions as $product)
                            <option value="{{ $product }}">{{ $product }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Target Status</label>
                    <select name="status" class="form-select">
                        <option value="Delivered">Delivered (Recommended)</option>
                        <option value="Returned">Returned</option>
                        <option value="In Transit">In Transit</option>
                    </select>
                    <small class="form-hint">Only unique phone numbers not already in leads will be extracted.</small>
                </div>

                <button type="submit" class="submit-btn success">
                    <i class="fas fa-bolt"></i>
                    Start Mining
                </button>
            </form>
        </div>
    </div>

    <!-- Back Link -->
    <a href="{{ route('leads.index') }}" class="back-link">
        <i class="fas fa-arrow-left"></i>
        Back to Leads
    </a>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const fileName = document.getElementById('fileName');
    const dropZone = document.getElementById('dropZone');

    // File selection
    fileInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            fileName.textContent = this.files[0].name;
            dropZone.classList.add('active');
        }
    });

    // Drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('active');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            if (!fileInput.files || !fileInput.files[0]) {
                dropZone.classList.remove('active');
            }
        }, false);
    });

    dropZone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        fileInput.files = files;
        if (files[0]) {
            fileName.textContent = files[0].name;
            dropZone.classList.add('active');
        }
    });
});
</script>
@endpush
