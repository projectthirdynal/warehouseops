@extends('layouts.app')

@section('title', 'Settings - Waybill System')
@section('page-title', 'Settings')

@section('content')
<div class="settings-container">
    <div class="settings-grid">
        <!-- Settings Navigation -->
        <div class="settings-nav">
            <a href="{{ route('settings') }}" class="settings-nav-item active">
                <i class="fas fa-cog"></i>
                <span>General Settings</span>
            </a>
            @if(auth()->user()->canAccess('users'))
            <a href="{{ route('settings.users') }}" class="settings-nav-item">
                <i class="fas fa-users-cog"></i>
                <span>User Management</span>
            </a>
            @endif
            <a href="{{ route('settings.couriers') }}" class="settings-nav-item">
                <i class="fas fa-truck"></i>
                <span>Courier Integration</span>
            </a>
        </div>

        <!-- Settings Content -->
        <div class="settings-content">
            <div class="card">
                <div class="card-header">
                    <h2>Access Control</h2>
                    <p>Configure role permissions and access levels</p>
                </div>
                <div class="card-body">
                    <div class="roles-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>Super Admin</th>
                                    <th>Admin</th>
                                    <th>Operator</th>
                                    <th>Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Dashboard</td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                </tr>
                                <tr>
                                    <td>Scanner</td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-times text-danger"></i></td>
                                </tr>
                                <tr>
                                    <td>Pending Section</td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-times text-danger"></i></td>
                                </tr>
                                <tr>
                                    <td>Upload</td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-times text-danger"></i></td>
                                </tr>
                                <tr>
                                    <td>Accounts</td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                </tr>
                                <tr>
                                    <td>Settings</td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-times text-danger"></i></td>
                                    <td><i class="fas fa-times text-danger"></i></td>
                                </tr>
                                <tr>
                                    <td>User Management</td>
                                    <td><i class="fas fa-check text-success"></i></td>
                                    <td><i class="fas fa-times text-danger"></i></td>
                                    <td><i class="fas fa-times text-danger"></i></td>
                                    <td><i class="fas fa-times text-danger"></i></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>System Configuration</h2>
                    <p>General system settings and preferences</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        <div class="form-group">
                            <label for="app_name">Application Name</label>
                            <input type="text" id="app_name" name="app_name" value="Thirdynal Warehouse System" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="timezone">Timezone</label>
                            <select id="timezone" name="timezone" class="form-control">
                                <option value="Asia/Manila" selected>Asia/Manila (GMT+8)</option>
                                <option value="UTC">UTC</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
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

    .roles-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .roles-table th,
    .roles-table td {
        padding: var(--space-3);
        text-align: center;
        border-bottom: 1px solid var(--border-primary);
    }

    .roles-table th {
        font-weight: var(--font-semibold);
        color: var(--text-primary);
        background: var(--bg-tertiary);
    }

    .roles-table td:first-child,
    .roles-table th:first-child {
        text-align: left;
    }

    .text-success {
        color: var(--status-success);
    }

    .text-danger {
        color: var(--status-error);
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

    .form-control {
        width: 100%;
        padding: var(--space-3);
        background: var(--bg-tertiary);
        border: 1px solid var(--border-secondary);
        border-radius: var(--radius-md);
        color: var(--text-primary);
        font-size: var(--text-base);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--accent-primary);
    }
</style>
@endpush
