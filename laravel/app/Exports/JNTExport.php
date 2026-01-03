<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;

class JNTExport implements FromCollection, WithMapping, WithHeadings, WithStyles, WithCustomStartCell
{
    protected $leads;

    public function __construct($leads)
    {
        $this->leads = $leads;
    }

    public function collection()
    {
        return $this->leads;
    }

    public function startCell(): string
    {
        return 'A8';
    }

    public function headings(): array
    {
        return [
            'Receiver(*)',
            'Receiver Telephone (*)',
            'Receiver Address (*)',
            'Receiver Province (*)',
            'Receiver City (*)',
            'Receiver Region (*)',
            'Express Type (*)',
            'Parcel Name (*)',
            'Weight (kg) (*)',
            'Total parcels(*)',
            'Parcel Value (Insurance Fee) (*)',
            'COD (PHP) (*)',
            'Remarks'
        ];
    }

    public function map($lead): array
    {
        $province = $lead->state ?? '';
        $city = $lead->city ?? '';
        $region = $lead->barangay ?? '';
        $street = $lead->street ?? '';

        // If specific columns are empty, try to parse from the consolidated 'address' field (backward compatibility)
        if (!$province && !$city && $lead->address) {
            $parts = explode(', ', $lead->address);
            if (count($parts) >= 4) {
                // Street, Barangay, City, Province
                $province = array_pop($parts);
                $city = array_pop($parts);
                $region = array_pop($parts);
                $street = implode(', ', $parts);
            } elseif (count($parts) == 3) {
                // Barangay, City, Province
                $province = array_pop($parts);
                $city = array_pop($parts);
                $region = array_pop($parts);
                $street = '';
            }
        }

        // Prepare full address for the 'Receiver Address' column
        // J&T format typically needs the specific street/house info here
        $fullAddress = $street ?: $lead->address;

        // Use product_brand as Parcel Name (required for JNT)
        $parcelName = $lead->product_brand ?: ($lead->product_name ?: 'Products');

        return [
            $lead->name,
            $lead->phone,
            $fullAddress,
            strtoupper($province),
            strtoupper($city),
            strtoupper($region),
            'EZ', // Express Type
            $parcelName, // Parcel Name from product_brand
            '0.1', // Weight
            '1', // Total parcels
            $lead->amount ?: 0,
            $lead->amount ?: 0, // COD
            $lead->notes ?: $lead->product_name // Remarks
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Add metadata rows manually to mimic sa.xls
        
        // Title
        $sheet->mergeCells('C2:G2');
        $sheet->setCellValue('C2', 'PH GLOBAL JET EXPRESS INC.');
        $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(16);

        // Order List Header
        $sheet->mergeCells('E5:G5');
        $sheet->setCellValue('E5', 'ORDER LIST');
        $sheet->getStyle('E5')->getFont()->setBold(true);

        // Version/Helper info
        $sheet->setCellValue('A5', 'V20200721');
        $sheet->setCellValue('A6', '(*) Information that must be filled out');
        $sheet->getStyle('A6')->getFont()->setItalic(true)->setSize(10);
        
        // Header Row Styling (Row 8)
        $sheet->getStyle('A8:M8')->getFont()->setBold(true);
        $sheet->getStyle('A8:M8')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFF00'); // Yellow background

        // Set column widths to be more readable
        foreach(range('A','M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
