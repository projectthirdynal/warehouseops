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
            if (!Storage::disk('local')->exists($this->filePath) && $upload->file_content) {
                Log::info("File missing locally, restoring from database for Upload {$this->uploadId}");
                Storage::disk('local')->put($this->filePath, $upload->file_content);
            }

            $fullPath = Storage::disk('local')->path($this->filePath);
            Log::info("DEBUG FULL PATH: " . $fullPath);

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
