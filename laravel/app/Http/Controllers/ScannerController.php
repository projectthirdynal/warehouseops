<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ScannerController extends Controller
{
    public function index()
    {
        $batchReadyCount = \App\Models\Waybill::where('batch_ready', true)
            ->where('status', '!=', 'dispatched')
            ->count();

        return view('scanner', compact('batchReadyCount'));
    }
}
