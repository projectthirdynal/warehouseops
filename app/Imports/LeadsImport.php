<?php

namespace App\Imports;

use App\Models\Lead;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class LeadsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    protected $assignedUserId;

    public function __construct($assignedUserId = null)
    {
        $this->assignedUserId = $assignedUserId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $phone = $row['phone'] ?? null;
            if (!$phone) continue;

            // One phone = One ACTIVE lead.
            $lead = Lead::where('phone', $phone)
                ->whereNotIn('status', [Lead::STATUS_SALE, Lead::STATUS_DELIVERED, Lead::STATUS_CANCELLED])
                ->first();

            $signingTime = $this->parseDate($row['signing_time'] ?? $row['signingtime'] ?? null);
            $submissionTime = $this->parseDate($row['submission_time'] ?? $row['submissiontime'] ?? null);

            // Use SigningTime if available, fallback to SubmissionTime
            $effectiveSigningTime = $signingTime ?: $submissionTime;

            if ($lead) {
                // If the lead exists, update its timing info if we have better data
                // We prioritize update if the new row has a REAL signing time or if currently NULL
                if ($effectiveSigningTime && (!$lead->signing_time || $signingTime)) {
                    $lead->update([
                        'signing_time' => $effectiveSigningTime,
                        'submission_time' => $submissionTime ?: $lead->submission_time,
                    ]);
                }
            } else {
                // Create new lead
                Lead::create([
                    'name'            => $row['name'],
                    'phone'           => $phone,
                    'address'         => $row['address'] ?? null,
                    'city'            => $row['city'] ?? null,
                    'state'           => $row['state'] ?? null,
                    'status'          => 'NEW',
                    'signing_time'    => $effectiveSigningTime,
                    'submission_time' => $submissionTime,
                    'uploaded_by'     => Auth::id(),
                    'assigned_to'     => $this->assignedUserId
                ]);
            }
        }
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

    public function rules(): array
    {
        return [
            'name'  => 'required|string',
            'phone' => 'required|string',
        ];
    }
}
