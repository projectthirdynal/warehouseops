<?php

namespace App\Http\Controllers;

use App\Models\SipAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SipSettingsController extends Controller
{
    /**
     * Get SIP configuration for current user
     * GET /api/sip/config
     */
    public function getConfig(): JsonResponse
    {
        $sipAccount = SipAccount::getForUser(Auth::id());

        if (!$sipAccount) {
            return response()->json([
                'configured' => false,
                'message' => 'No SIP account configured. Contact administrator.',
            ], 404);
        }

        return response()->json([
            'configured' => true,
            'config' => $sipAccount->toSipConfig(),
        ]);
    }

    /**
     * Admin: List all SIP accounts
     * GET /api/sip/accounts
     */
    public function index(): JsonResponse
    {
        if (!Auth::user()->canAccess('settings_manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $accounts = SipAccount::with('user:id,name')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'user_id' => $account->user_id,
                    'user_name' => $account->user?->name ?? 'Global',
                    'sip_server' => $account->sip_server,
                    'ws_server' => $account->ws_server,
                    'username' => $account->username,
                    'display_name' => $account->display_name,
                    'is_active' => $account->is_active,
                    'is_default' => $account->is_default,
                    'is_global' => $account->isGlobal(),
                    'created_at' => $account->created_at,
                ];
            });

        return response()->json($accounts);
    }

    /**
     * Admin: Create/Update SIP account
     * POST /api/sip/accounts
     */
    public function store(Request $request): JsonResponse
    {
        if (!Auth::user()->canAccess('settings_manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'id' => 'nullable|exists:sip_accounts,id',
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:100',
            'sip_server' => 'required|string|max:255',
            'ws_server' => 'required|string|max:255|starts_with:wss://,ws://',
            'username' => 'required|string|max:100',
            'password' => 'required_without:id|string|max:255',
            'display_name' => 'nullable|string|max:100',
            'outbound_proxy' => 'nullable|string|max:255',
            'realm' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $data = $request->except(['id', 'password']);
        
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        if ($request->filled('id')) {
            // Update existing
            $account = SipAccount::findOrFail($request->id);
            $account->update($data);
        } else {
            // Create new
            $account = SipAccount::create($data);
        }

        // If setting as default, unset other defaults for same scope
        if ($request->is_default) {
            SipAccount::where('id', '!=', $account->id)
                ->where(function ($q) use ($account) {
                    if ($account->user_id) {
                        $q->where('user_id', $account->user_id);
                    } else {
                        $q->whereNull('user_id');
                    }
                })
                ->update(['is_default' => false]);
        }

        return response()->json([
            'success' => true,
            'account' => $account,
        ]);
    }

    /**
     * Admin: Delete SIP account
     * DELETE /api/sip/accounts/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        if (!Auth::user()->canAccess('settings_manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $account = SipAccount::findOrFail($id);
        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'SIP account deleted.',
        ]);
    }

    /**
     * Test SIP connection (placeholder for future implementation)
     * POST /api/sip/test
     */
    public function test(Request $request): JsonResponse
    {
        if (!Auth::user()->canAccess('settings_manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // In a real implementation, this would attempt to register with the SIP server
        // For now, just validate the format
        $request->validate([
            'sip_server' => 'required|string',
            'ws_server' => 'required|string|starts_with:wss://,ws://',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Configuration appears valid. Full test requires client connection.',
        ]);
    }
}
