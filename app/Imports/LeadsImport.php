<?php

namespace App\Imports;

use App\Models\Lead;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Auth;

class LeadsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    protected $assignedUserId;

    public function __construct($assignedUserId = null)
    {
        $this->assignedUserId = $assignedUserId;
    }

    public function model(array $row)
    {
        // One phone = One ACTIVE lead. Skip if there's an unfinalized lead already.
        $exists = Lead::where('phone', $row['phone'])
            ->whereNotIn('status', [Lead::STATUS_SALE, Lead::STATUS_DELIVERED, Lead::STATUS_CANCELLED])
            ->exists();

        if ($exists) {
            return null; // Skip duplicates
        }

        return new Lead([
            'name'        => $row['name'],
            'phone'       => $row['phone'],
            'address'     => $row['address'] ?? null,
            'city'        => $row['city'] ?? null,
            'state'       => $row['state'] ?? null,
            'status'      => 'NEW',
            'uploaded_by' => Auth::id(),
            'assigned_to' => $this->assignedUserId // Auto-assign if passed, else null
        ]);
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|string',
            'phone' => 'required|string',
        ];
    }
}
