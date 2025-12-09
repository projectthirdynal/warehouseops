@extends('layouts.app')

@section('title', 'Upload - Waybill System')

@section('content')
    <!-- Page Header with Icon -->
    <div class="section-header">
        <h2>
            <svg class="section-header-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            Upload Waybills
        </h2>
        <p>Upload CSV or Excel files containing waybill data</p>
    </div>

    <div class="upload-container">
        <div class="upload-form">
            <div id="uploadResult" class="upload-result" role="alert" aria-live="polite"></div>

            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <!-- Drop Zone -->
                <div class="file-upload-area" id="dropZone" role="button" tabindex="0" aria-label="File upload area - click or drag to upload">
                    <input type="file" id="fileInput" name="waybill_file" accept=".xlsx,.xls,.csv" hidden>
                    <div class="upload-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <p class="upload-text">Drop your file here or click to browse</p>
                    <p class="upload-subtext">Supported formats: CSV, XLSX, XLS</p>
                    <p class="file-name" id="fileName"></p>
                </div>

                <!-- Blue Info Box - File Format Requirements -->
                <div class="info-box info-box-blue">
                    <div class="info-box-header">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <h3>File Format Requirements</h3>
                    </div>
                    <div class="info-box-content">
                        <div class="info-section">
                            <h4>Required Columns</h4>
                            <ul>
                                <li>Waybill Number</li>
                                <li>Sender Name</li>
                                <li>Sender Phone</li>
                                <li>Receiver Name</li>
                                <li>Receiver Phone</li>
                                <li>Destination</li>
                            </ul>
                        </div>
                        <div class="info-section">
                            <h4>Optional Columns</h4>
                            <ul>
                                <li>Sender Address</li>
                                <li>Receiver Address</li>
                                <li>Weight (kg)</li>
                                <li>Quantity</li>
                                <li>Service Type</li>
                                <li>COD Amount</li>
                                <li>Remarks</li>
                            </ul>
                        </div>
                        <div class="info-section">
                            <h4>File Constraints</h4>
                            <ul>
                                <li>File formats: CSV, XLSX, XLS</li>
                                <li>First row must contain column headers</li>
                                <li>Maximum file size: <strong>10 MB</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="uploadBtn" disabled>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Upload Waybills
                </button>

                <div class="progress-bar" id="progressBar" style="display:none;">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .info-box {
        border-radius: var(--radius-lg);
        padding: var(--space-5);
        margin-bottom: var(--space-5);
    }

    .info-box-blue {
        background-color: rgba(59, 130, 246, 0.1);
        border: 1px solid var(--accent-blue);
    }

    .info-box-header {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        margin-bottom: var(--space-4);
        color: var(--accent-blue);
    }

    .info-box-header h3 {
        font-size: var(--text-base);
        font-weight: var(--font-semibold);
        margin: 0;
        color: var(--accent-blue);
    }

    .info-box-content {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-5);
    }

    @media (max-width: 768px) {
        .info-box-content {
            grid-template-columns: 1fr;
        }
    }

    .info-section h4 {
        font-size: var(--text-sm);
        font-weight: var(--font-semibold);
        color: var(--text-primary);
        margin-bottom: var(--space-2);
    }

    .info-section ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .info-section li {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        padding: var(--space-1) 0;
        padding-left: var(--space-4);
        position: relative;
    }

    .info-section li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        background-color: var(--accent-blue);
        border-radius: 50%;
    }

    .info-section li strong {
        color: var(--text-primary);
    }
</style>
@endpush

@push('scripts')
<script>
    // Pass route to JS
    const uploadRoute = "{{ route('upload.store') }}";

    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');
        const uploadResult = document.getElementById('uploadResult');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');

        // Drag and drop handlers
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateFileName();
            }
        });

        dropZone.addEventListener('click', (e) => {
            if (e.target.tagName !== 'BUTTON') {
                fileInput.click();
            }
        });

        dropZone.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', updateFileName);

        function updateFileName() {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                fileName.textContent = `${file.name} (${sizeMB} MB)`;
                fileName.style.color = 'var(--accent-primary)';
                uploadBtn.disabled = false;

                // Check file size
                if (file.size > 10 * 1024 * 1024) {
                    showResult('error', 'File size exceeds 10 MB limit');
                    uploadBtn.disabled = true;
                }
            } else {
                fileName.textContent = '';
                uploadBtn.disabled = true;
            }
        }

        // Form submission
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!fileInput.files.length) {
                showResult('error', 'Please select a file to upload');
                return;
            }

            const formData = new FormData(uploadForm);
            uploadBtn.disabled = true;
            progressBar.style.display = 'block';
            progressFill.style.width = '0%';

            try {
                const xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progressFill.style.width = percent + '%';
                    }
                });

                xhr.addEventListener('load', () => {
                    progressBar.style.display = 'none';
                    uploadBtn.disabled = false;

                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showResult('success', response.message || 'Upload successful!');
                            fileInput.value = '';
                            fileName.textContent = '';
                            uploadBtn.disabled = true;
                        } else {
                            showResult('error', response.message || 'Upload failed');
                        }
                    } else {
                        showResult('error', 'Upload failed. Please try again.');
                    }
                });

                xhr.addEventListener('error', () => {
                    progressBar.style.display = 'none';
                    uploadBtn.disabled = false;
                    showResult('error', 'Network error. Please try again.');
                });

                xhr.open('POST', uploadRoute);
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
                xhr.send(formData);

            } catch (error) {
                progressBar.style.display = 'none';
                uploadBtn.disabled = false;
                showResult('error', 'An error occurred. Please try again.');
            }
        });

        function showResult(type, message) {
            uploadResult.className = 'scan-result ' + type;
            uploadResult.innerHTML = message;
            uploadResult.style.display = 'block';

            if (type === 'success') {
                setTimeout(() => {
                    uploadResult.style.display = 'none';
                }, 5000);
            }
        }
    });
</script>
@endpush
