@extends('layouts.app')

@section('title', 'Upload for Batch - Waybill System')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/upload.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/batch-upload.css') }}">
@endpush

@section('content')
    <div class="batch-ready-info">
        <div class="info-card">
            <div class="info-icon">📋</div>
            <div class="info-content">
                <h3>{{ number_format($batchReadyCount) }}</h3>
                <p>Waybills ready for batch scanning</p>
            </div>
        </div>
        <div class="info-action">
            @if($batchReadyCount > 0)
                <form action="{{ route('upload.batch.cancel') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all pending batch waybills?');" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn" style="background-color: #dc3545; color: white; margin-right: 10px;">
                        <span>✕ Clear Batch</span>
                    </button>
                </form>
            @endif
            <a href="{{ route('scanner') }}" class="btn btn-primary">
                <span>Go to Scanner</span>
            </a>
        </div>
    </div>

    <div class="upload-container">
        <div class="upload-form">
            <div id="uploadResult" class="upload-result" role="alert" aria-live="polite" style="display:none"></div>

            <div class="info-box info-box-warning" style="margin-bottom: 20px; background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; padding: 15px; border-radius: 8px; color: #d97706;">
                📌 <strong>Note:</strong> Waybills uploaded here will be immediately available for batch scanning.
            </div>

            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                
                <div class="file-upload-area" id="dropZone" style="border: 2px dashed #4b5563; padding: 40px; text-align: center; border-radius: 12px; cursor: pointer; transition: all 0.2s;">
                    <input type="file" id="fileInput" name="waybill_file" accept=".xlsx,.xls,.csv" hidden>
                    <div class="upload-icon" style="font-size: 48px; margin-bottom: 10px;">📁</div>
                    <p class="upload-text" style="font-size: 1.1em; color: #e5e7eb; margin-bottom: 5px;">Drag & drop your XLSX file here</p>
                    <p class="upload-subtext" style="color: #9ca3af; margin-bottom: 20px;">or</p>
                    <div class="btn btn-secondary" style="background: #374151; color: white; padding: 8px 16px; border-radius: 6px; display: inline-block;">Choose File</div>
                    <p class="file-name" id="fileName" style="margin-top: 15px; color: #60a5fa; font-weight: 500;"></p>
                </div>

                <div class="file-requirements" style="margin: 20px 0; padding: 20px; background: #1f2937; border-radius: 8px;">
                    <h4 style="color: #e5e7eb; margin-top: 0;">File Format Requirements</h4>
                    <ul style="color: #9ca3af; padding-left: 20px; margin-bottom: 0;">
                        <li>File type: Excel (.xlsx or .xls)</li>
                        <li>Maximum size: 50MB</li>
                        <li>First row must contain column headers</li>
                    </ul>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="uploadBtn" disabled style="width: 100%; padding: 12px; font-weight: bold; font-size: 1.1em; margin-top: 10px;">
                    <span>⬆️ Upload for Batch Scanning</span>
                </button>

                <div class="progress-bar" id="progressBar" style="display:none; height: 10px; background: #374151; border-radius: 5px; margin-top: 20px; overflow: hidden;">
                    <div class="progress-fill" id="progressFill" style="width: 0%; height: 100%; background: #3b82f6; transition: width 0.3s;"></div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Pass route to JS
        const uploadBatchRoute = "{{ route('upload.batch.store') }}";
        const scannerRoute = "{{ route('scanner') }}";
        const uploadStatusBase = "{{ url('/upload') }}";
    </script>
    <script src="{{ asset('assets/js/upload-batch.js') }}"></script>
@endpush
