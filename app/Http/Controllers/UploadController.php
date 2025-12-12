<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use App\Jobs\ProcessWaybillImport;
use Illuminate\Support\Facades\Storage;

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
            ->update(['batch_ready' => false]);

        return redirect()->route('scanner')->with('success', 'Batch cleared. Waybills moved to general pending list.');
    }

    /**
     * Get upload status for progress polling
     */
    public function status($id)
    {
        $upload = Upload::find($id);
        
        if (!$upload) {
            return response()->json([
                'success' => false,
                'message' => 'Upload not found'
            ], 404);
        }

        // Calculate progress percentage
        $progress = 0;
        if ($upload->status === 'completed') {
            $progress = 100;
        } elseif ($upload->status === 'processing' && $upload->total_rows > 0) {
            $progress = min(99, round(($upload->processed_rows / $upload->total_rows) * 100));
        } elseif ($upload->status === 'processing') {
            // Still estimating, count current waybills
            $currentCount = $upload->waybills()->count();
            $upload->update(['processed_rows' => $currentCount]);
            $progress = $currentCount > 0 ? min(99, $currentCount) : 5; // Show some progress
        }

        return response()->json([
            'success' => true,
            'upload_id' => $upload->id,
            'status' => $upload->status,
            'filename' => $upload->filename,
            'total_rows' => $upload->total_rows ?? 0,
            'processed_rows' => $upload->processed_rows ?? 0,
            'progress' => $progress,
            'message' => $this->getStatusMessage($upload)
        ]);
    }

    private function getStatusMessage(Upload $upload): string
    {
        switch ($upload->status) {
            case 'pending':
                return 'Waiting to start...';
            case 'processing':
                return 'Processing ' . number_format($upload->processed_rows ?? 0) . ' rows...';
            case 'completed':
                return 'Completed! ' . number_format($upload->total_rows ?? 0) . ' waybills imported.';
            case 'failed':
                return 'Failed: ' . ($upload->notes ?? 'Unknown error');
            default:
                return 'Unknown status';
        }
    }

    private function handleUpload(Request $request, $batchReady)
    {
        $request->validate([
            'waybill_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:102400' // 100MB limit
        ]);

        $file = $request->file('waybill_file');
        
        // Create upload record
        $upload = Upload::create([
            'filename' => $file->getClientOriginalName(),
            'uploaded_by' => 'Admin',
            'status' => 'pending',
            'notes' => $batchReady ? 'Batch scanning upload' : 'General upload',
            // Store content in DB for multi-server support (transient)
            // Use base64 to ensure safe transport across DB drivers
            'file_content' => base64_encode(file_get_contents($file->getRealPath()))
        ]);
        
        // Debug logging
        $contentSize = strlen($upload->file_content ?? '');
        \Illuminate\Support\Facades\Log::info("UploadController: Stored file in DB. ID: {$upload->id}, Size: {$contentSize} bytes");

        try {
            // Store file to disk for async processing
            $filePath = 'imports/' . $upload->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            Storage::disk('local')->put($filePath, file_get_contents($file->getRealPath()));

            // Dispatch job to queue
            ProcessWaybillImport::dispatch($upload->id, $filePath, $batchReady);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded and queued for processing',
                'upload_id' => $upload->id,
                'status' => 'pending',
                'async' => true,
                'total_rows' => 0,     // Prevent "undefined" on frontend
                'processed_rows' => 0, // Prevent "undefined" on frontend
                'batch_ready' => 0     // Prevent "undefined" on frontend
            ]);

        } catch (\Exception $e) {
            $upload->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error queuing file: ' . $e->getMessage()
            ], 500);
        }
    }
}
