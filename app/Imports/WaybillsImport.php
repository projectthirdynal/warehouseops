<?php

namespace App\Imports;

use App\Models\Waybill;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class WaybillsImport implements ToModel, WithHeadingRow, WithUpserts, WithChunkReading, WithBatchInserts
{
    protected $uploadId;
    protected $batchReady;

    public function __construct($uploadId, $batchReady = false)
    {
        $this->uploadId = $uploadId;
        $this->batchReady = $batchReady;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Ensure we have a waybill number
        if (!isset($row['waybill_number'])) return null;

        return new Waybill([
            'upload_id' => $this->uploadId,
            'waybill_number' => $row['waybill_number'],
            'sender_name' => $row['sender_name'] ?? null,
            'sender_address' => $row['sender_address'] ?? null,
            'sender_phone' => $row['sender_cellphone'] ?? null,
            'receiver_name' => $row['receiver'] ?? null,
            // Construct receiver address from components if available
            'receiver_address' => implode(', ', array_filter([
                $row['barangay'] ?? null,
                $row['city'] ?? null,
                $row['province'] ?? null
            ])) ?: null,
            'receiver_phone' => $row['receiver_cellphone'] ?? null,
            'destination' => $row['city'] ?? null,
            'weight' => $row['item_weight'] ?? 0,
            'quantity' => $row['number_of_items'] ?? 1,
            'service_type' => $row['express_type'] ?? 'Standard',
            'cod_amount' => $row['cod'] ?? 0,
            'remarks' => $row['remarks'] ?? null,
            'status' => isset($row['order_status']) ? strtolower($row['order_status']) : 'pending',
            'batch_ready' => $this->batchReady,
            'signing_time' => $this->parseDate($row['signing_time'] ?? $row['signingtime'] ?? null)
        ]);
    }

    private function parseDate($date)
    {
        if (!$date) return null;
        
        try {
            if (is_numeric($date)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
            }
            // Try multiple formats if necessary, but Carbon::parse is usually smart enough
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return string|array
     */
    public function uniqueBy()
    {
        return 'waybill_number';
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }
}
