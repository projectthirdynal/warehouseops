<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Label - {{ $waybill->courier_waybill_no ?? $waybill->waybill_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 100mm 150mm;
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            width: 100mm;
            height: 150mm;
            padding: 2mm;
            background: white;
        }

        .label-container {
            border: 2px solid #000;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* Header Row */
        .header-row {
            display: flex;
            border-bottom: 2px solid #000;
        }

        .logo-section {
            flex: 2;
            padding: 3mm;
            border-right: 1px solid #000;
            display: flex;
            align-items: center;
            gap: 2mm;
            background: #e31e25;
            color: white;
            font-weight: bold;
            font-size: 14pt;
        }

        .express-type {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18pt;
            font-weight: bold;
        }

        /* Barcode Row */
        .barcode-row {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3mm;
            border-bottom: 1px solid #000;
        }

        .barcode {
            font-family: 'Libre Barcode 39', 'IDAutomationHC39M', 'Code39', monospace;
            font-size: 36pt;
            letter-spacing: 2px;
        }

        .barcode-number {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            margin-top: 1mm;
        }

        /* Order Info Row */
        .order-row {
            display: flex;
            border-bottom: 1px solid #000;
            font-size: 7pt;
        }

        .order-number {
            flex: 2;
            padding: 2mm;
            border-right: 1px solid #000;
        }

        .destination {
            flex: 1;
            padding: 2mm;
            font-weight: bold;
        }

        /* Sort Code Row */
        .sort-row {
            display: flex;
            border-bottom: 2px solid #000;
        }

        .sort-code {
            flex: 2;
            padding: 3mm;
            border-right: 2px solid #000;
            font-size: 24pt;
            font-weight: bold;
            text-align: center;
        }

        .sort-number {
            flex: 1;
            padding: 3mm;
            font-size: 20pt;
            font-weight: bold;
            text-align: center;
        }

        /* Address Sections */
        .address-section {
            padding: 2mm 3mm;
            border-bottom: 1px solid #000;
            min-height: 18mm;
        }

        .address-label {
            font-size: 7pt;
            color: #666;
            margin-bottom: 1mm;
        }

        .address-name {
            font-weight: bold;
            font-size: 9pt;
        }

        .address-details {
            font-size: 8pt;
            line-height: 1.3;
        }

        .address-phone {
            font-size: 9pt;
            font-weight: bold;
            margin-top: 1mm;
        }

        /* Delivery Attempts */
        .attempts-row {
            display: flex;
            border-bottom: 1px solid #000;
        }

        .attempts-label {
            padding: 2mm;
            font-size: 7pt;
            border-right: 1px solid #000;
            display: flex;
            align-items: center;
        }

        .attempts-boxes {
            flex: 1;
            display: flex;
        }

        .attempt-box {
            flex: 1;
            height: 10mm;
            border-right: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14pt;
            font-weight: bold;
        }

        .attempt-box:last-child {
            border-right: none;
        }

        .cod-section {
            padding: 2mm;
            font-weight: bold;
        }

        /* Item Details */
        .item-row {
            display: flex;
            border-bottom: 1px solid #000;
            font-size: 7pt;
        }

        .item-cell {
            flex: 1;
            padding: 2mm;
            border-right: 1px solid #000;
        }

        .item-cell:last-child {
            border-right: none;
        }

        /* Remarks Section */
        .remarks-section {
            flex: 1;
            display: flex;
        }

        .remarks-content {
            flex: 2;
            padding: 2mm;
            border-right: 1px solid #000;
        }

        .remarks-label {
            font-size: 6pt;
            color: #666;
        }

        .signature-area {
            flex: 1;
            padding: 2mm;
        }

        .signature-label {
            font-size: 6pt;
            color: #666;
        }

        /* COD Watermark */
        .cod-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 48pt;
            color: rgba(0, 0, 0, 0.05);
            font-weight: bold;
            pointer-events: none;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="label-container">
        <!-- Header -->
        <div class="header-row">
            <div class="logo-section">
                J&T EXPRESS
            </div>
            <div class="express-type">
                {{ $waybill->service_type ?? 'EZ' }}
            </div>
        </div>

        <!-- Barcode -->
        <div class="barcode-row">
            <div>
                <div class="barcode">*{{ $waybill->courier_waybill_no ?? $waybill->waybill_number }}*</div>
                <div class="barcode-number">{{ $waybill->courier_waybill_no ?? $waybill->waybill_number }}</div>
            </div>
        </div>

        <!-- Order Info -->
        <div class="order-row">
            <div class="order-number">
                Order No.: <strong>{{ $waybill->waybill_number }}</strong>
            </div>
            <div class="destination">
                {{ strtoupper($waybill->barangay ?? 'BARANGAY') }}
            </div>
        </div>

        <!-- Sort Code -->
        <div class="sort-row">
            <div class="sort-code">
                {{ $waybill->courier_sorting_code ?? '---' }}
            </div>
            <div class="sort-number">
                {{ substr($waybill->courier_sorting_code ?? '000', -3) }}
            </div>
        </div>

        <!-- Sender Info -->
        <div class="address-section">
            <div class="address-label">Sender</div>
            <div class="address-name">{{ $waybill->sender_name }}</div>
            <div class="address-details">{{ $waybill->sender_address }}</div>
        </div>

        <!-- Receiver Info -->
        <div class="address-section">
            <div class="address-label">Receiver</div>
            <div class="address-name">{{ $waybill->receiver_name }}</div>
            <div class="address-details">
                {{ $waybill->street ?? '' }}
                {{ $waybill->barangay ? ', ' . $waybill->barangay : '' }}
                {{ $waybill->city ? ', ' . $waybill->city : '' }}
                {{ $waybill->province ? ', ' . $waybill->province : '' }}
            </div>
            <div class="address-phone">{{ $waybill->receiver_phone }}</div>
        </div>

        <!-- Delivery Attempts & COD -->
        <div class="attempts-row">
            <div class="attempts-label">No. of Delivery<br>Attempts</div>
            <div class="attempts-boxes">
                <div class="attempt-box">1</div>
                <div class="attempt-box">2</div>
                <div class="attempt-box">3</div>
            </div>
            <div class="cod-section">
                COD: PHP {{ number_format($waybill->cod_amount ?? 0, 2) }}
            </div>
        </div>

        <!-- Item Details -->
        <div class="item-row">
            <div class="item-cell">Piece: {{ $waybill->quantity ?? 1 }}</div>
            <div class="item-cell">Weight: {{ $waybill->weight ?? 1 }}kg</div>
            <div class="item-cell">Pouches:</div>
        </div>
        <div class="item-row">
            <div class="item-cell" style="flex: 2;">Goods: {{ $waybill->item_name ?? 'Package' }}</div>
            <div class="item-cell">Waybill Info</div>
        </div>

        <!-- Remarks & Signature -->
        <div class="remarks-section">
            <div class="remarks-content">
                <div class="remarks-label">Remarks:</div>
                {{ $waybill->remarks ?? '' }}
            </div>
            <div class="signature-area">
                <div class="signature-label">Signature:</div>
            </div>
        </div>

        @if($waybill->cod_amount > 0)
        <div class="cod-watermark">COD</div>
        @endif
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
