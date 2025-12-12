<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Imports\WaybillsImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessWaybillImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uploadId;
    protected $filePath;
    protected $batchReady;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 1800; // 30 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(int $uploadId, string $filePath, bool $batchReady = false)
    {
        $this->uploadId = $uploadId;
        $this->filePath = $filePath;
        $this->batchReady = $batchReady;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $upload = Upload::find($this->uploadId);
        
        if (!$upload) {
            Log::error("ProcessWaybillImport: Upload {$this->uploadId} not found");
            return;
        }

        try {
            $upload->update(['status' => 'processing']);

            // Get file path
            Log::info("DEBUG PATHS: base_path=" . base_path() . " | storage_path=" . storage_path());
            
            // Restore from DB if missing locally (Multi-server support)
            // Force restoration if file is missing OR has 0 bytes
            $needsRestore = !Storage::disk('local')->exists($this->filePath) || Storage::disk('local')->size($this->filePath) === 0;
            
            if ($needsRestore) {
                 $contentSize = $upload->file_content ? strlen($upload->file_content) : 0;
                 Log::info("Restoring file from database for Upload {$this->uploadId}. DB Base64 Size: {$contentSize} bytes");
                 
                 if ($upload->file_content) {
                    $decoded = base64_decode($upload->file_content);
                    if ($decoded === false) {
                        throw new \Exception("Failed to decode base64 content for Upload {$this->uploadId}");
                    }
                    Storage::disk('local')->put($this->filePath, $decoded);
                 } else {
                    // Critical failure: File missing and no content in DB
                    throw new \Exception("File missing locally and no content in database (Size: {$contentSize}) for Upload {$this->uploadId}");
                 }
            }

            $fullPath = Storage::disk('local')->path($this->filePath);
            
            // Ensure file is readable
            if (!file_exists($fullPath)) {
                throw new \Exception("File not found at path: {$fullPath} even after restoration attempt");
            }
            clearstatcache(); // Clear cache to ensure file size is correct
            $fileSize = filesize($fullPath);
            Log::info("Importing file: {$fullPath} (Size: {$fileSize} bytes)");
            
            if ($fileSize === 0) {
                 throw new \Exception("File exists but is empty: {$fullPath}");
            }

            chmod($fullPath, 0664); // Ensure readable by group/user

            // Import with custom import class that tracks progress
            Excel::import(
                new WaybillsImport($this->uploadId, $this->batchReady),
                $fullPath
            );

            // Update final stats
            $totalRows = $upload->waybills()->count();
            $upload->update([
                'total_rows' => $totalRows,
                'processed_rows' => $totalRows,
                'status' => 'completed'
            ]);

            // Clean up the temp file
            Storage::disk('local')->delete($this->filePath);
            
            // Clean up DB content to free space
            $upload->update(['file_content' => null]);

            Log::info("ProcessWaybillImport: Upload {$this->uploadId} completed with {$totalRows} rows");

        } catch (\Exception $e) {
            Log::error("ProcessWaybillImport: Upload {$this->uploadId} failed - " . $e->getMessage());
            
            $upload->update([
                'status' => 'failed',
                'notes' => 'Error: ' . $e->getMessage()
            ]);

            // Clean up temp file on failure too
            if (Storage::disk('local')->exists($this->filePath)) {
                Storage::disk('local')->delete($this->filePath);
            }

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $upload = Upload::find($this->uploadId);
        
        if ($upload) {
            $upload->update([
                'status' => 'failed',
                'notes' => 'Import failed after all retries: ' . $exception->getMessage(),
                'file_content' => null // Clean up DB content
            ]);
        }

        Log::error("ProcessWaybillImport: Upload {$this->uploadId} failed permanently - " . $exception->getMessage());
    }
}
