@extends('layouts.app')

@section('title', 'Upload - Waybill System')
@section('page-title', 'Upload')

@section('content')
    <!-- Page Header -->
    <x-page-header
        title="Upload Waybills"
        description="Upload Excel or CSV files containing waybill data"
        icon="fas fa-cloud-upload-alt"
    />

    <div class="max-w-2xl mx-auto">
        <x-card>
            <div id="uploadResult" class="mb-4 hidden" role="alert" aria-live="polite"></div>

            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <!-- Drop Zone -->
                <div
                    class="border-2 border-dashed border-dark-400 rounded-xl p-12 text-center transition-all duration-200 cursor-pointer hover:border-info-500 hover:bg-info-50 mb-6"
                    id="dropZone"
                    role="button"
                    tabindex="0"
                    aria-label="File upload area - drag and drop or click to select"
                >
                    <input type="file" id="fileInput" name="waybill_file" accept=".xlsx,.xls,.csv" hidden>
                    <div class="text-5xl text-info-500 mb-4">
                        <i class="fas fa-cloud-arrow-up"></i>
                    </div>
                    <p class="text-lg font-medium text-white mb-2">Drag & drop your file here</p>
                    <p class="text-sm text-dark-100 mb-4">or click to browse</p>
                    <p class="text-xs text-dark-100">.xlsx, .xls, .csv</p>
                    <p class="text-sm font-semibold text-cyan-500 mt-4" id="fileName"></p>
                </div>

                <!-- File Format Info -->
                <div class="bg-info-50 border border-info-200 rounded-xl p-5 mb-6">
                    <div class="flex items-center gap-2 mb-4 text-info-500">
                        <i class="fas fa-circle-info text-lg"></i>
                        <h3 class="text-sm font-semibold">File Requirements</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <h4 class="text-xs font-semibold text-white uppercase tracking-wider mb-2">Required Columns</h4>
                            <ul class="space-y-1">
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">Waybill Number</li>
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">Sender Name</li>
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">Receiver Name</li>
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">Receiver Phone</li>
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">Destination</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-white uppercase tracking-wider mb-2">Optional Columns</h4>
                            <ul class="space-y-1">
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">Receiver Address</li>
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">Weight (kg)</li>
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">COD Amount</li>
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">Remarks</li>
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">Service Type</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-white uppercase tracking-wider mb-2">Constraints</h4>
                            <ul class="space-y-1">
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">Max file size: <strong class="text-white">100 MB</strong></li>
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">First row = headers</li>
                                <li class="text-sm text-slate-300 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-info-500 before:rounded-full before:opacity-60">Large files processed async</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <x-button type="submit" variant="primary" size="lg" icon="fas fa-upload" id="uploadBtn" class="w-full" disabled>
                    Upload Waybills
                </x-button>

                <div class="w-full h-1.5 bg-dark-950 rounded-full overflow-hidden mt-4 hidden" id="progressBar">
                    <div class="h-full bg-gradient-to-r from-info-500 to-cyan-500 rounded-full transition-all duration-300" id="progressFill" style="width: 0%"></div>
                </div>

                <div id="progressStatus" class="text-center mt-3 text-sm text-slate-400 hidden"></div>
            </form>
        </x-card>
    </div>
@endsection

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
            dropZone.classList.add('border-info-500', 'bg-info-50');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('border-info-500', 'bg-info-50');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-info-500', 'bg-info-50');
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
            progressBar.classList.remove('hidden');
            progressStatus.classList.remove('hidden');
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
                                progressBar.classList.add('hidden');
                                progressStatus.classList.add('hidden');
                                showResult('success', response.message || 'Upload successful!');
                                resetForm();
                            } else {
                                progressBar.classList.add('hidden');
                                progressStatus.classList.add('hidden');
                                uploadBtn.disabled = false;
                                showResult('error', response.message || 'Upload failed');
                            }
                        } catch (e) {
                            progressBar.classList.add('hidden');
                            progressStatus.classList.add('hidden');
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
                        progressBar.classList.add('hidden');
                        progressStatus.classList.add('hidden');
                        uploadBtn.disabled = false;
                    }
                });

                xhr.addEventListener('error', () => {
                    progressBar.classList.add('hidden');
                    progressStatus.classList.add('hidden');
                    uploadBtn.disabled = false;
                    showResult('error', 'Network error. Please try again.');
                });

                xhr.open('POST', uploadRoute);
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.send(formData);

            } catch (error) {
                progressBar.classList.add('hidden');
                progressStatus.classList.add('hidden');
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
                            progressBar.classList.add('hidden');
                            progressStatus.classList.add('hidden');
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
            uploadResult.classList.remove('hidden');
            const colors = {
                success: 'bg-success-50 border-success-200 text-success-500',
                error: 'bg-error-50 border-error-200 text-error-500',
                info: 'bg-info-50 border-info-200 text-info-500',
                warning: 'bg-warning-50 border-warning-200 text-warning-500'
            };
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle',
                warning: 'fa-exclamation-triangle'
            };

            uploadResult.className = `px-4 py-3 rounded-lg flex items-center gap-3 text-sm font-medium border ${colors[type] || colors.info}`;
            uploadResult.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i>${message}`;

            if (type === 'success') {
                setTimeout(() => {
                    uploadResult.classList.add('hidden');
                }, 10000);
            }
        }
    });
</script>
@endpush
