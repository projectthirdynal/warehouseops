@extends('layouts.app')

@section('title', 'User Management - Waybill System')
@section('page-title', 'User Management')

@section('content')
    <div class="settings-container">
        <div class="settings-grid">
            <!-- Settings Navigation -->
            <div class="settings-nav">
                <a href="{{ route('settings') }}" class="settings-nav-item">
                    <i class="fas fa-cog"></i>
                    <span>General Settings</span>
                </a>
                <a href="{{ route('settings.users') }}" class="settings-nav-item active">
                    <i class="fas fa-users-cog"></i>
                    <span>User Management</span>
                </a>
                <a href="{{ route('settings.couriers') }}" class="settings-nav-item">
                    <i class="fas fa-truck"></i>
                    <span>Courier Integration</span>
                </a>
            </div>

            <!-- Settings Content -->
            <div class="settings-content">
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h2>Users</h2>
                            <p>Manage system users and their roles</p>
                        </div>
                        <button class="btn btn-primary" onclick="openModal('createUserModal')">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->username }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <span class="role-badge role-{{ $user->role }}">
                                                    {{ $user->getRoleDisplayName() }}
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    class="status-badge {{ $user->is_active ? 'status-active' : 'status-inactive' }}">
                                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-secondary"
                                                    onclick="editUser({{ json_encode($user) }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                @if($user->id !== auth()->id())
                                                    <form action="{{ route('settings.users.delete', $user->id) }}" method="POST"
                                                        style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New User</h3>
                <button class="modal-close" onclick="closeModal('createUserModal')">&times;</button>
            </div>
            <form method="POST" action="{{ route('settings.users.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="create_name">Full Name</label>
                        <input type="text" id="create_name" name="name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="create_username">Username</label>
                        <input type="text" id="create_username" name="username" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="create_email">Email</label>
                        <input type="email" id="create_email" name="email" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="create_role">Role</label>
                        <select id="create_role" name="role" required class="form-control">
                            @foreach($roles as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="create_password">Password</label>
                        <input type="password" id="create_password" name="password" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="create_password_confirmation">Confirm Password</label>
                        <input type="password" id="create_password_confirmation" name="password_confirmation" required
                            class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <form method="POST" id="editUserForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Full Name</label>
                        <input type="text" id="edit_name" name="name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_username">Username</label>
                        <input type="text" id="edit_username" name="username" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_role">Role</label>
                        <select id="edit_role" name="role" required class="form-control">
                            @foreach($roles as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                            Active
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="edit_password">New Password (leave blank to keep current)</label>
                        <input type="password" id="edit_password" name="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_password_confirmation">Confirm New Password</label>
                        <input type="password" id="edit_password_confirmation" name="password_confirmation"
                            class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
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

        .role-badge {
            display: inline-block;
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: var(--font-semibold);
        }

        .role-superadmin {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .role-admin {
            background: rgba(234, 179, 8, 0.2);
            color: #eab308;
        }

        .role-operator {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }

        .role-agent {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .status-active {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .status-inactive {
            background: rgba(107, 114, 128, 0.2);
            color: #6b7280;
        }

        .btn-sm {
            padding: var(--space-1) var(--space-2);
            font-size: var(--text-sm);
        }

        .btn-danger {
            background: var(--status-error);
            color: white;
        }

        .btn-danger:hover {
            opacity: 0.9;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--border-primary);
        }

        .modal-header h3 {
            margin: 0;
            font-size: var(--text-lg);
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: var(--text-2xl);
            color: var(--text-secondary);
            cursor: pointer;
        }

        .modal-body {
            padding: var(--space-5);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: var(--space-3);
            padding: var(--space-4) var(--space-5);
            border-top: 1px solid var(--border-primary);
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
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-primary);
        }
    </style>
@endpush

@push('scripts')
    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        function editUser(user) {
            document.getElementById('editUserForm').action = '/settings/users/' + user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_is_active').checked = user.is_active;
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_password_confirmation').value = '';
            openModal('editUserModal');
        }
    </script>
@endpush