<?php

namespace App\Http\Controllers;

use App\Models\AgentFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentGovernanceController extends Controller
{
    /**
     * Display agent governance dashboard.
     */
    public function index()
    {
        if (!Auth::user()->can('leads_manage')) {
             abort(403);
        }

        $flags = AgentFlag::with('agent.profile')
            ->where('is_resolved', false)
            ->latest()
            ->paginate(50);
            
        return view('admin.agent-flags', compact('flags'));
    }

    /**
     * Resolve a flag.
     */
    public function resolve(AgentFlag $flag)
    {
        if (!Auth::user()->can('leads_manage')) {
             abort(403);
        }

        $flag->update(['is_resolved' => true]);
        
        return back()->with('success', 'Flag marked as resolved.');
    }
}
