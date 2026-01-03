@extends('layouts.app')

@section('title', 'Upload - Waybill System')
@section('page-title', 'Upload')

@section('content')
    <!-- Page Header -->
    <div class="section-header">
        <h2>
            <svg class="section-header-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            Upload Waybills
        </h2>
        <p>Upload Excel or CSV files containing waybill data</p>
    </div>

    <div class="upload-container">
        <div class="upload-form">
            <div id="uploadResult" class="upload-result" role="alert" aria-live="polite"></div>

            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <!-- Drop Zone -->
                <div class="file-upload-area" id="dropZone" role="button" tabindex="0" aria-label="File upload area">
                    <input type="file" id="fileInput" name="waybill_file" accept=".xlsx,.xls,.csv" hidden>
                    <div class="upload-icon">
                        <i class="fas fa-cloud-arrow-up"></i>
                    </div>
                    <p class="upload-text">Drag & drop your file here</p>
                    <p class="upload-subtext">or click to browse • .xlsx, .xls, .csv</p>
                    <p class="file-name" id="fileName"></p>
                </div>

                <!-- File Format Info -->
                <div class="info-box">
                    <div class="info-box-header">
                        <i class="fas fa-circle-info"></i>
                        <h3>File Requirements</h3>
                    </div>
                    <div class="info-box-content">
                        <div class="info-section">
                            <h4>Required Columns</h4>
                            <ul>
                                <li>Waybill Number</li>
                                <li>Sender Name</li>
                                <li>Receiver Name</li>
                                <li>Receiver Phone</li>
                                <li>Destination</li>
                            </ul>
                        </div>
                        <div class="info-section">
                            <h4>Optional Columns</h4>
                            <ul>
                                <li>Receiver Address</li>
                                <li>Weight (kg)</li>
                                <li>COD Amount</li>
                                <li>Remarks</li>
                                <li>Service Type</li>
                            </ul>
                        </div>
                        <div class="info-section">
                            <h4>Constraints</h4>
                            <ul>
                                <li>Max file size: <strong>100 MB</strong></li>
                                <li>First row = headers</li>
                                <li>Large files processed async</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100" id="uploadBtn" disabled>
                    <i class="fas fa-upload"></i>
                    Upload Waybills
                </button>

                <div class="progress-bar" id="progressBar" style="display:none;">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                
                <div id="progressStatus" class="progress-status" style="display:none;"></div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .info-box {
        background: rgba(59, 130, 246, 0.06);
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: var(--radius-xl);
        padding: var(--space-5);
        margin-bottom: var(--space-5);
    }

    .info-box-header {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        margin-bottom: var(--space-4);
        color: var(--accent-blue);
    }

    .info-box-header i {
        font-size: var(--text-lg);
    }

    .info-box-header h3 {
        font-size: var(--text-sm);
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
            gap: var(--space-4);
        }
    }

    .info-section h4 {
        font-size: var(--text-xs);
        font-weight: var(--font-semibold);
        color: var(--text-primary);
        margin-bottom: var(--space-2);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .info-section ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .info-section li {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        padding: 4px 0;
        padding-left: var(--space-3);
        position: relative;
    }

    .info-section li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 5px;
        height: 5px;
        background-color: var(--accent-blue);
        border-radius: 50%;
        opacity: 0.6;
    }

    .info-section li strong {
        color: var(--text-primary);
    }
    
    .progress-status {
        text-align: center;
        margin-top: var(--space-3);
        font-size: var(--text-sm);
        color: var(--text-secondary);
    }
</style>
@endpush

@push('scripts')
<script>
    const uploadRoute = "{{ route('upload.store') }}";
    const statusRouteBase = "{{ url('/upload') }}";

    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');
        const uploadResult = document.getElementById('uploadResult');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');
        const progressStatus = document.getElementById('progressStatus');

        let pollInterval = null;

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

                if (file.size > 100 * 1024 * 1024) {
                    showResult('error', 'File size exceeds 100 MB limit');
                    uploadBtn.disabled = true;
                }
            } else {
                fileName.textContent = '';
                uploadBtn.disabled = true;
            }
        }

        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!fileInput.files.length) {
                showResult('error', 'Please select a file to upload');
                return;
            }

            const formData = new FormData(uploadForm);
            uploadBtn.disabled = true;
            progressBar.style.display = 'block';
            progressStatus.style.display = 'block';
            progressFill.style.width = '5%';
            progressStatus.textContent = 'Uploading file...';
            showResult('info', 'Uploading file...');

            try {
                const xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 30);
                        progressFill.style.width = percent + '%';
                        progressStatus.textContent = `Uploading... ${percent}%`;
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success && response.async && response.upload_id) {
                                showResult('info', 'Processing file in background...');
                                progressFill.style.width = '30%';
                                progressStatus.textContent = 'Processing...';
                                pollUploadStatus(response.upload_id);
                            } else if (response.success) {
                                progressBar.style.display = 'none';
                                progressStatus.style.display = 'none';
                                showResult('success', response.message || 'Upload successful!');
                                resetForm();
                            } else {
                                progressBar.style.display = 'none';
                                progressStatus.style.display = 'none';
                                uploadBtn.disabled = false;
                                showResult('error', response.message || 'Upload failed');
                            }
                        } catch (e) {
                            progressBar.style.display = 'none';
                            progressStatus.style.display = 'none';
                            uploadBtn.disabled = false;
                            showResult('error', 'Server returned an invalid response.');
                        }
                    } else {
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            let errorMsg = errorResponse.message || 'Upload failed.';
                            if (errorResponse.errors) {
                                const details = Object.values(errorResponse.errors).flat().join(', ');
                                errorMsg += ` (${details})`;
                            }
                            showResult('error', errorMsg);
                        } catch (e) {
                            showResult('error', `Upload failed: ${xhr.statusText}`);
                        }
                        progressBar.style.display = 'none';
                        progressStatus.style.display = 'none';
                        uploadBtn.disabled = false;
                    }
                });

                xhr.addEventListener('error', () => {
                    progressBar.style.display = 'none';
                    progressStatus.style.display = 'none';
                    uploadBtn.disabled = false;
                    showResult('error', 'Network error. Please try again.');
                });

                xhr.open('POST', uploadRoute);
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.send(formData);

            } catch (error) {
                progressBar.style.display = 'none';
                progressStatus.style.display = 'none';
                uploadBtn.disabled = false;
                showResult('error', 'An error occurred. Please try again.');
            }
        });

        function pollUploadStatus(uploadId) {
            if (pollInterval) clearInterval(pollInterval);

            pollInterval = setInterval(async () => {
                try {
                    const response = await fetch(`${statusRouteBase}/${uploadId}/status`);
                    const data = await response.json();

                    if (data.success) {
                        const processingProgress = 30 + (data.progress * 0.7);
                        progressFill.style.width = processingProgress + '%';
                        progressStatus.textContent = data.message;

                        if (data.status === 'completed') {
                            clearInterval(pollInterval);
                            pollInterval = null;
                            progressFill.style.width = '100%';
                            progressStatus.textContent = 'Complete!';
                            showResult('success', data.message);
                            setTimeout(() => {
                                window.location.href = "{{ route('scanner') }}";
                            }, 1500);
                        } else if (data.status === 'failed') {
                            clearInterval(pollInterval);
                            pollInterval = null;
                            progressBar.style.display = 'none';
                            progressStatus.style.display = 'none';
                            uploadBtn.disabled = false;
                            showResult('error', data.message);
                        }
                    }
                } catch (error) {
                    console.error('Poll error:', error);
                }
            }, 2000);
        }

        function resetForm() {
            fileInput.value = '';
            fileName.textContent = '';
            uploadBtn.disabled = true;
        }

        function showResult(type, message) {
            uploadResult.className = 'scan-result ' + type;
            uploadResult.innerHTML = message;
            uploadResult.style.display = 'block';

            if (type === 'success') {
                setTimeout(() => {
                    uploadResult.style.display = 'none';
                }, 10000);
            }
        }
    });
</script>
@endpush
