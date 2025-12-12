<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    /**
     * Show settings dashboard
     */
    public function index()
    {
        return view('settings.index');
    }

    /**
     * Update system settings
     */
    public function update(Request $request)
    {
        // For now, just return success - settings can be stored in a settings table later
        return redirect()->route('settings')->with('success', 'Settings updated successfully.');
    }

    /**
     * Show user management page
     */
    public function users()
    {
        $users = User::orderBy('role')->orderBy('name')->get();
        $roles = User::getRoles();
        
        return view('settings.users', compact('users', 'roles'));
    }

    /**
     * Store a new user
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:superadmin,admin,operator,agent',
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        return redirect()->route('settings.users')->with('success', 'User created successfully.');
    }

    /**
     * Update an existing user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:superadmin,admin,operator,agent',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Update password if provided
        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6|confirmed']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('settings.users')->with('success', 'User updated successfully.');
    }

    /**
     * Delete a user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('settings.users')->with('error', 'You cannot delete your own account.');
        }

        // Soft delete by deactivating
        $user->update(['is_active' => false]);

        return redirect()->route('settings.users')->with('success', 'User deactivated successfully.');
    }
}
