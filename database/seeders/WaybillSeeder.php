<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Waybill;
use App\Models\Upload;

class WaybillSeeder extends Seeder
{
    public function run(): void
    {
        // Create a dummy upload record
        $upload = Upload::create([
            'filename' => 'test_import.xlsx',
            'uploaded_by' => 'System Seeder',
            'total_rows' => 10,
            'processed_rows' => 10,
            'status' => 'completed'
        ]);

        // Create pending waybills
        $waybills = [
            ['WB001', 'John Doe', 'New York', '1234567890', 'Jane Smith', 'Los Angeles', '0987654321', 'Los Angeles'],
            ['WB002', 'Alice Brown', 'Chicago', '1122334455', 'Bob White', 'Miami', '5544332211', 'Miami'],
            ['WB003', 'Charlie Green', 'Seattle', '6677889900', 'Diana Blue', 'Austin', '0099887766', 'Austin'],
            ['WB004', 'Eve Black', 'Boston', '1112223333', 'Frank Red', 'Denver', '4445556666', 'Denver'],
            ['WB005', 'Grace Yellow', 'Portland', '7778889999', 'Henry Orange', 'Phoenix', '1110002222', 'Phoenix'],
        ];

        foreach ($waybills as $data) {
            Waybill::create([
                'waybill_number' => $data[0],
                'upload_id' => $upload->id,
                'sender_name' => $data[1],
                'sender_address' => $data[2],
                'sender_phone' => $data[3],
                'receiver_name' => $data[4],
                'receiver_address' => $data[5],
                'receiver_phone' => $data[6],
                'destination' => $data[7],
                'weight' => rand(1, 10) + (rand(0, 99) / 100),
                'quantity' => rand(1, 5),
                'status' => 'pending',
                'batch_ready' => true
            ]);
        }
        
        // Create some dispatched waybills
        $dispatchedWaybills = [
            ['WB006', 'Dispatched User', 'City A', '111', 'Receiver A', 'City B', '222', 'City B'],
            ['WB007', 'Dispatched User 2', 'City C', '333', 'Receiver B', 'City D', '444', 'City D'],
        ];
        
        foreach ($dispatchedWaybills as $data) {
            Waybill::create([
                'waybill_number' => $data[0],
                'upload_id' => $upload->id,
                'sender_name' => $data[1],
                'sender_address' => $data[2],
                'sender_phone' => $data[3],
                'receiver_name' => $data[4],
                'receiver_address' => $data[5],
                'receiver_phone' => $data[6],
                'destination' => $data[7],
                'weight' => rand(1, 10),
                'quantity' => 1,
                'status' => 'dispatched',
                'batch_ready' => true
            ]);
        }
    }
}
