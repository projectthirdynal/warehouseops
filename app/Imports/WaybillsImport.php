<?php

namespace App\Imports;

use App\Models\Waybill;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class WaybillsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $uploadId;
    protected $batchReady;

    public function __construct($uploadId, $batchReady = true)
    {
        $this->uploadId = $uploadId;
        $this->batchReady = $batchReady;
    }

    /**
    * @param Collection $rows
    */
    public function collection(Collection $rows)
    {
        $now = now();
        $recordsToUpsert = [];

        foreach ($rows as $row) {
            if (!isset($row['waybill_number'])) continue;

            $recordsToUpsert[] = [
                'upload_id' => $this->uploadId,
                'waybill_number' => $row['waybill_number'],
                'sender_name' => $row['sender_name'] ?? null,
                'sender_address' => $row['sender_address'] ?? null,
                'sender_phone' => $row['sender_cellphone'] ?? null,
                'receiver_name' => $row['receiver'] ?? null,
                'receiver_address' => $this->formatReceiverAddress($row),
                'receiver_phone' => $row['receiver_cellphone'] ?? null,
                'destination' => $row['city'] ?? null,
                'weight' => $row['item_weight'] ?? 0,
                'quantity' => $row['number_of_items'] ?? 1,
                'service_type' => $row['express_type'] ?? 'Standard',
                'cod_amount' => $row['cod'] ?? 0,
                'remarks' => $row['remarks'] ?? null,
                'status' => isset($row['order_status']) ? strtolower($row['order_status']) : 'pending',
                'batch_ready' => $this->batchReady,
                'signing_time' => $this->parseDate($row['signing_time'] ?? $row['signingtime'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($recordsToUpsert)) {
            Waybill::upsert(
                $recordsToUpsert, 
                ['waybill_number'], // Unique constraints
                [ // Columns to update if exists
                    'sender_name', 'sender_address', 'sender_phone',
                    'receiver_name', 'receiver_address', 'receiver_phone',
                    'destination', 'weight', 'quantity', 'service_type',
                    'cod_amount', 'remarks', 'status', 'batch_ready', 
                    'signing_time', 'updated_at'
                ]
            );
        }
    }

    private function formatReceiverAddress($row)
    {
        return implode(', ', array_filter([
            $row['barangay'] ?? null,
            $row['city'] ?? null,
            $row['province'] ?? null
        ])) ?: null;
    }

    private function parseDate($date)
    {
        if (!$date) return null;
        
        try {
            if (is_numeric($date)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
            }
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
