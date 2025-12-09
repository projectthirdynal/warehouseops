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
                    </div>
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
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/upload-batch.js') }}"></script>
    <script>
        // Pass route to JS
        const uploadBatchRoute = "{{ route('upload.batch.store') }}";
        const scannerRoute = "{{ route('scanner') }}";
    </script>
@endpush
