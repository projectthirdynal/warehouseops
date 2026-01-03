<?php

namespace App\Imports;

use App\Models\Waybill;
use App\Models\Upload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Common\Entity\Row;

class FastWaybillImport
{
    protected $uploadId;
    protected $batchReady;
    protected $headerMap = [];

    public function __construct($uploadId, $batchReady = true)
    {
        $this->uploadId = $uploadId;
        $this->batchReady = $batchReady;
    }

    public function import($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'csv' || $extension === 'txt') {
            $reader = new CSVReader();
        } else {
            $reader = new XLSXReader();
        }

        $reader->open($filePath);

        $rowCount = 0;
        $batch = [];
        $batchSize = 2500; // Optimal batch size for PostgreSQL upsert
        $now = now();

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();
                
                if ($rowCount === 0) {
                    // This is the header row - normalize by lowercasing and replacing spaces with underscores
                    $normalizedHeaders = array_map(function($h) {
                        return str_replace(' ', '_', strtolower(trim($h)));
                    }, $cells);
                    $this->headerMap = array_flip($normalizedHeaders);
                    Log::info("FastWaybillImport: Headers mapped: " . json_encode($this->headerMap));
                    $rowCount++;
                    continue;
                }

                $data = $this->mapRow($cells);
                if ($data) {
                    $data['upload_id'] = $this->uploadId;
                    $data['batch_ready'] = $this->batchReady;
                    $data['created_at'] = $now;
                    $data['updated_at'] = $now;
                    $batch[] = $data;
                }

                if (count($batch) >= $batchSize) {
                    $this->upsertBatch($batch);
                    $batch = [];
                }

                $rowCount++;
                
                // Update progress every 5000 rows to keep it smooth but not too frequent
                if ($rowCount % 5000 === 0) {
                     DB::table('uploads')->where('id', $this->uploadId)->update(['processed_rows' => $rowCount]);
                }
            }
            break; // Only process the first sheet for now, matching previous behavior
        }

        if (!empty($batch)) {
            $this->upsertBatch($batch);
        }

        // Final progress update
        DB::table('uploads')->where('id', $this->uploadId)->update([
            'total_rows' => $rowCount - 1,
            'processed_rows' => $rowCount - 1,
            'status' => 'completed'
        ]);

        $reader->close();
        return $rowCount - 1;
    }

    protected function mapRow($cells)
    {
        $mapped = [];
        
        // Helper to get value by header name
        $get = function($keys) use ($cells) {
            if (!is_array($keys)) $keys = [$keys];
            foreach ($keys as $key) {
                if (isset($this->headerMap[$key])) {
                    $index = $this->headerMap[$key];
                    return $cells[$index] ?? null;
                }
            }
            return null;
        };

        $waybillNumber = $get(['waybill_number', 'waybill no', 'waybill']);
        if (!$waybillNumber) return null;

        $status = $get(['order_status', 'status', 'orderstatus']);
        $signingTime = $get(['signing_time', 'signingtime', 'submission_time', 'submissiontime', 'signing time']);

        return [
            'waybill_number' => $waybillNumber,
            'sender_name' => $get(['sender_name', 'sender']),
            'sender_address' => $get(['sender_address', 'sender address']),
            'sender_phone' => $get(['sender_cellphone', 'sender_phone', 'sender phone']),
            'receiver_name' => $get(['receiver', 'receiver_name', 'receiver name']),
            'receiver_address' => $this->formatReceiverAddress($get),
            'receiver_phone' => $get(['receiver_cellphone', 'receiver_phone', 'receiver phone']),
            'destination' => $get(['city', 'destination']),
            'province' => $get(['province', 'state']),
            'city' => $get(['city']),
            'barangay' => $get(['barangay']),
            'street' => $get(['address', 'street']),
            'weight' => $get(['item_weight', 'weight']) ?? 0,
            'quantity' => $get(['number_of_items', 'quantity', 'qty']) ?? 1,
            'service_type' => $get(['express_type', 'service_type']) ?? 'Standard',
            'cod_amount' => $get(['cod', 'cod_amount']) ?? 0,
            'remarks' => $get(['remarks', 'remark']) ?: ($cells[22] ?? null),
            'item_name' => $get(['item_name', 'item name']) ?: ($cells[23] ?? null),
            'status' => $status ? strtolower($status) : 'pending',
            'signing_time' => $this->parseDate($signingTime),
        ];
    }

    protected function formatReceiverAddress($get)
    {
        $barangay = $get('barangay');
        $city = $get('city');
        $province = $get(['province', 'state']);
        
        return implode(', ', array_filter([$barangay, $city, $province])) ?: null;
    }

    protected function parseDate($date)
    {
        if (!$date) return null;
        if ($date instanceof \DateTimeInterface) return $date;
        
        try {
            if (is_numeric($date)) {
                // If it's a numeric value from Excel, we might need special handling
                // but OpenSpout usually gives DateTime objects for date cells.
                return null; 
            }
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function upsertBatch($batch)
    {
        $start = microtime(true);
        DB::table('waybills')->upsert(
            $batch,
            ['waybill_number'],
            [
                'upload_id', // Now updating upload_id to latest
                'sender_name', 'sender_address', 'sender_phone',
                'receiver_name', 'receiver_address', 'receiver_phone',
                'destination', 'province', 'city', 'barangay', 'street',
                'weight', 'quantity', 'service_type',
                'cod_amount', 'remarks', 'item_name', 'status', 'batch_ready', 
                'signing_time', 'updated_at'
            ]
        );
        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::info("FastWaybillImport: Upserted batch of " . count($batch) . " rows in {$duration}ms");
    }
}
