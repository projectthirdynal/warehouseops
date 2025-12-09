<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use App\Imports\WaybillsImport;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    public function index()
    {
        return view('upload');
    }

    public function batchIndex()
    {
        $batchReadyCount = \App\Models\Waybill::where('batch_ready', true)
            ->where('status', 'pending')
            ->count();
            
        return view('upload-batch', compact('batchReadyCount'));
    }

    public function store(Request $request)
    {
        return $this->handleUpload($request, false);
    }

    public function storeBatch(Request $request)
    {
        return $this->handleUpload($request, true);
    }

    public function cancelBatch()
    {
        \App\Models\Waybill::where('batch_ready', true)
            ->where('status', '!=', 'dispatched')
            ->delete();

        return redirect()->route('scanner')->with('success', 'Batch upload cleared successfully.');
    }

    private function handleUpload(Request $request, $batchReady)
    {
        $request->validate([
            'waybill_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:51200'
        ]);

        $file = $request->file('waybill_file');
        
        $upload = Upload::create([
            'filename' => $file->getClientOriginalName(),
            'uploaded_by' => 'Admin',
            'status' => 'processing',
            'notes' => $batchReady ? 'Batch scanning upload' : 'General upload'
        ]);

        try {
            Excel::import(new WaybillsImport($upload->id, $batchReady), $file);
            
            // Update stats
            $totalRows = $upload->waybills()->count();
            $upload->update([
                'total_rows' => $totalRows,
                'processed_rows' => $totalRows,
                'status' => 'completed'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'total_rows' => $totalRows,
                'processed_rows' => $totalRows,
                'batch_ready' => $batchReady ? $totalRows : 0
            ]);
        } catch (\Exception $e) {
            $upload->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage()
            ], 500);
        }
    }
}
