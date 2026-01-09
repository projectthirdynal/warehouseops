@extends('layouts.app')

@section('title', 'Courier Settings - Waybill System')
@section('page-title', 'Courier Settings')

@section('content')
<div class="settings-container">
    <div class="settings-grid">
        <!-- Settings Navigation -->
        <div class="settings-nav">
            <a href="{{ route('settings') }}" class="settings-nav-item">
                <i class="fas fa-cog"></i>
                <span>General Settings</span>
            </a>
            @if(auth()->user()->canAccess('users'))
            <a href="{{ route('settings.users') }}" class="settings-nav-item">
                <i class="fas fa-users-cog"></i>
                <span>User Management</span>
            </a>
            @endif
            <a href="{{ route('settings.couriers') }}" class="settings-nav-item active">
                <i class="fas fa-truck"></i>
                <span>Courier Integration</span>
            </a>
        </div>

        <!-- Settings Content -->
        <div class="settings-content">
            @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h2>Courier API Integration</h2>
                    <p>Configure courier provider API keys for automatic order creation and status tracking</p>
                </div>
                <div class="card-body">
                    <div class="courier-providers">
                        @foreach($providers as $provider)
                        <div class="courier-card {{ $provider->is_active ? 'active' : '' }}">
                            <div class="courier-header">
                                <div class="courier-info">
                                    <div class="courier-logo">
                                        @if($provider->code === 'jnt')
                                            <i class="fas fa-shipping-fast" style="color: #e31e25;"></i>
                                        @elseif($provider->code === 'manual')
                                            <i class="fas fa-edit" style="color: #6b7280;"></i>
                                        @else
                                            <i class="fas fa-truck" style="color: #3b82f6;"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <h3>{{ $provider->name }}</h3>
                                        <span class="courier-code">{{ strtoupper($provider->code) }}</span>
                                    </div>
                                </div>
                                <div class="courier-status">
                                    @if($provider->is_active)
                                        <span class="status-badge active">
                                            <i class="fas fa-check-circle"></i> Active
                                        </span>
                                    @else
                                        <span class="status-badge inactive">
                                            <i class="fas fa-circle"></i> Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <form method="POST" action="{{ route('settings.couriers.update', $provider) }}" class="courier-form">
                                @csrf
                                @method('PATCH')

                                @if($provider->code !== 'manual')
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="api_key_{{ $provider->id }}">API Key</label>
                                        <input type="password" 
                                               id="api_key_{{ $provider->id }}" 
                                               name="api_key" 
                                               class="form-control" 
                                               placeholder="{{ $provider->api_key ? '••••••••••••' : 'Enter API Key' }}"
                                               autocomplete="off">
                                        <small>Leave blank to keep existing key</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="api_secret_{{ $provider->id }}">API Secret (Optional)</label>
                                        <input type="password" 
                                               id="api_secret_{{ $provider->id }}" 
                                               name="api_secret" 
                                               class="form-control" 
                                               placeholder="{{ $provider->api_secret ? '••••••••••••' : 'Enter API Secret' }}"
                                               autocomplete="off">
                                        <small>Leave blank to keep existing secret</small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="base_url_{{ $provider->id }}">API Base URL</label>
                                    <input type="url" 
                                           id="base_url_{{ $provider->id }}" 
                                           name="base_url" 
                                           class="form-control" 
                                           value="{{ $provider->base_url }}"
                                           placeholder="https://api.example.com">
                                </div>
                                @else
                                <div class="manual-info">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Manual mode allows you to update waybill statuses manually without API integration. This is useful while waiting for courier API access.</p>
                                </div>
                                @endif

                                <div class="form-actions">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="is_active" value="1" {{ $provider->is_active ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                        <span class="toggle-label">{{ $provider->is_active ? 'Active' : 'Inactive' }}</span>
                                    </label>
                                    
                                    <div class="action-buttons">
                                        @if($provider->code !== 'manual')
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="testConnection({{ $provider->id }})">
                                            <i class="fas fa-plug"></i> Test Connection
                                        </button>
                                        @endif
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-save"></i> Save Settings
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Webhook Endpoints</h2>
                    <p>Share these URLs with courier providers for real-time status updates</p>
                </div>
                <div class="card-body">
                    <div class="webhook-list">
                        @foreach($providers->where('code', '!=', 'manual') as $provider)
                        <div class="webhook-item">
                            <div class="webhook-info">
                                <span class="webhook-provider">{{ $provider->name }}</span>
                                <code class="webhook-url">{{ url($provider->webhook_path ?? '/api/courier/'.$provider->code.'/webhook') }}</code>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="copyWebhook('{{ url($provider->webhook_path ?? '/api/courier/'.$provider->code.'/webhook') }}')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .settings-container {
        max-width: 1200px;
    }

    .settings-grid {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: var(--space-6);
    }

    @media (max-width: 768px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }
    }

    .settings-nav {
        display: flex;
        flex-direction: column;
        gap: var(--space-2);
    }

    .settings-nav-item {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        padding: var(--space-3) var(--space-4);
        background: var(--bg-secondary);
        border-radius: var(--radius-md);
        color: var(--text-secondary);
        text-decoration: none;
        transition: all var(--transition-fast);
    }

    .settings-nav-item:hover {
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .settings-nav-item.active {
        background: var(--accent-primary);
        color: white;
    }

    .settings-content {
        display: flex;
        flex-direction: column;
        gap: var(--space-5);
    }

    .alert {
        padding: var(--space-4);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        gap: var(--space-3);
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid var(--status-success);
        color: var(--status-success);
    }

    .card {
        background: var(--bg-secondary);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-primary);
        overflow: hidden;
    }

    .card-header {
        padding: var(--space-5);
        border-bottom: 1px solid var(--border-primary);
    }

    .card-header h2 {
        font-size: var(--text-lg);
        font-weight: var(--font-semibold);
        color: var(--text-primary);
        margin: 0 0 var(--space-1) 0;
    }

    .card-header p {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        margin: 0;
    }

    .card-body {
        padding: var(--space-5);
    }

    .courier-providers {
        display: flex;
        flex-direction: column;
        gap: var(--space-5);
    }

    .courier-card {
        background: var(--bg-tertiary);
        border: 1px solid var(--border-secondary);
        border-radius: var(--radius-lg);
        padding: var(--space-5);
        transition: all var(--transition-fast);
    }

    .courier-card.active {
        border-color: var(--status-success);
    }

    .courier-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--space-4);
    }

    .courier-info {
        display: flex;
        align-items: center;
        gap: var(--space-4);
    }

    .courier-logo {
        width: 48px;
        height: 48px;
        background: var(--bg-secondary);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .courier-info h3 {
        font-size: var(--text-base);
        font-weight: var(--font-semibold);
        color: var(--text-primary);
        margin: 0 0 var(--space-1) 0;
    }

    .courier-code {
        font-size: var(--text-xs);
        color: var(--text-muted);
        font-family: monospace;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-2) var(--space-3);
        border-radius: var(--radius-full);
        font-size: var(--text-xs);
        font-weight: var(--font-medium);
    }

    .status-badge.active {
        background: rgba(16, 185, 129, 0.1);
        color: var(--status-success);
    }

    .status-badge.inactive {
        background: rgba(107, 114, 128, 0.1);
        color: var(--text-muted);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-4);
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }

    .form-group {
        margin-bottom: var(--space-4);
    }

    .form-group label {
        display: block;
        font-size: var(--text-sm);
        font-weight: var(--font-medium);
        color: var(--text-primary);
        margin-bottom: var(--space-2);
    }

    .form-group small {
        display: block;
        font-size: var(--text-xs);
        color: var(--text-muted);
        margin-top: var(--space-1);
    }

    .form-control {
        width: 100%;
        padding: var(--space-3);
        background: var(--bg-secondary);
        border: 1px solid var(--border-secondary);
        border-radius: var(--radius-md);
        color: var(--text-primary);
        font-size: var(--text-sm);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--accent-primary);
    }

    .manual-info {
        display: flex;
        align-items: flex-start;
        gap: var(--space-3);
        padding: var(--space-4);
        background: rgba(59, 130, 246, 0.1);
        border-radius: var(--radius-md);
        margin-bottom: var(--space-4);
    }

    .manual-info i {
        color: var(--accent-primary);
        font-size: 18px;
        margin-top: 2px;
    }

    .manual-info p {
        margin: 0;
        color: var(--text-secondary);
        font-size: var(--text-sm);
        line-height: 1.5;
    }

    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: var(--space-4);
        border-top: 1px solid var(--border-secondary);
    }

    .toggle-switch {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        cursor: pointer;
    }

    .toggle-switch input {
        display: none;
    }

    .toggle-slider {
        width: 44px;
        height: 24px;
        background: var(--bg-secondary);
        border-radius: var(--radius-full);
        position: relative;
        transition: all var(--transition-fast);
    }

    .toggle-slider::before {
        content: '';
        position: absolute;
        width: 18px;
        height: 18px;
        background: var(--text-muted);
        border-radius: 50%;
        top: 3px;
        left: 3px;
        transition: all var(--transition-fast);
    }

    .toggle-switch input:checked + .toggle-slider {
        background: var(--status-success);
    }

    .toggle-switch input:checked + .toggle-slider::before {
        transform: translateX(20px);
        background: white;
    }

    .toggle-label {
        font-size: var(--text-sm);
        color: var(--text-secondary);
    }

    .action-buttons {
        display: flex;
        gap: var(--space-3);
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-2) var(--space-4);
        border-radius: var(--radius-md);
        font-size: var(--text-sm);
        font-weight: var(--font-medium);
        cursor: pointer;
        transition: all var(--transition-fast);
        border: none;
    }

    .btn-primary {
        background: var(--accent-primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--accent-secondary);
    }

    .btn-secondary {
        background: var(--bg-secondary);
        color: var(--text-primary);
        border: 1px solid var(--border-secondary);
    }

    .btn-secondary:hover {
        background: var(--bg-tertiary);
    }

    .btn-sm {
        padding: var(--space-2) var(--space-3);
        font-size: var(--text-xs);
    }

    .webhook-list {
        display: flex;
        flex-direction: column;
        gap: var(--space-3);
    }

    .webhook-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-4);
        background: var(--bg-tertiary);
        border-radius: var(--radius-md);
    }

    .webhook-info {
        display: flex;
        flex-direction: column;
        gap: var(--space-1);
    }

    .webhook-provider {
        font-size: var(--text-sm);
        font-weight: var(--font-medium);
        color: var(--text-primary);
    }

    .webhook-url {
        font-size: var(--text-xs);
        color: var(--text-secondary);
        background: var(--bg-secondary);
        padding: var(--space-2) var(--space-3);
        border-radius: var(--radius-sm);
    }
</style>
@endpush

@push('scripts')
<script>
function testConnection(providerId) {
    fetch(`/settings/couriers/${providerId}/test`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
        } else {
            alert('✗ ' + data.message);
        }
    })
    .catch(error => {
        alert('Error testing connection: ' + error.message);
    });
}

function copyWebhook(url) {
    navigator.clipboard.writeText(url).then(() => {
        alert('Webhook URL copied to clipboard!');
    });
}
</script>
@endpush
