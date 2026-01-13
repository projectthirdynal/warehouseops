<?php

namespace App\Http\Controllers;

use App\Models\CourierProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CourierSettingsController extends Controller
{
    /**
     * Display courier settings page.
     */
    public function index()
    {
        $providers = CourierProvider::orderBy('name')->get();
        
        return view('settings.couriers', compact('providers'));
    }

    /**
     * Update courier provider settings.
     */
    public function update(Request $request, CourierProvider $provider)
    {
        $validated = $request->validate([
            'api_key' => 'nullable|string|max:500',
            'api_secret' => 'nullable|string|max:500',
            'base_url' => 'nullable|url|max:255',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        // Only update API key if provided (don't clear existing)
        if ($request->filled('api_key')) {
            $provider->api_key = $validated['api_key'];
        }

        if ($request->filled('api_secret')) {
            $provider->api_secret = $validated['api_secret'];
        }

        if ($request->filled('base_url')) {
            $provider->base_url = $validated['base_url'];
        }

        $provider->is_active = $validated['is_active'] ?? false;

        // Merge additional settings
        if (!empty($validated['settings'])) {
            $provider->settings = array_merge($provider->settings ?? [], $validated['settings']);
        }

        $provider->save();

        return redirect()->route('settings.couriers')
            ->with('success', $provider->name . ' settings updated successfully.');
    }

    /**
     * Toggle courier provider active status.
     */
    public function toggle(CourierProvider $provider)
    {
        $provider->update(['is_active' => !$provider->is_active]);

        $status = $provider->is_active ? 'activated' : 'deactivated';
        
        return redirect()->route('settings.couriers')
            ->with('success', $provider->name . ' has been ' . $status . '.');
    }

    /**
     * Test courier API connection.
     */
    public function testConnection(CourierProvider $provider)
    {
        if (!$provider->hasApiCredentials()) {
            return response()->json([
                'success' => false,
                'message' => 'API credentials not configured.',
            ]);
        }

        // For now, just verify credentials exist
        // Full API test would require actual endpoint call
        return response()->json([
            'success' => true,
            'message' => 'API credentials are configured. Ready for integration.',
        ]);
    }
}
