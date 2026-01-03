<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispatch Manifest</title>
    <style>
        @page {
            size: 4in 6in;
            margin: 0.2in; /* Slightly smaller margin for printer handling, content will use internal padding */
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .container {
            width: 3.6in; /* 4in - 0.4in margins */
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 5px;
        }

        .title {
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 0;
        }

        .label {
            font-weight: bold;
            width: 40%;
        }

        .waybill-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .waybill-table th, .waybill-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: center;
        }

        .waybill-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            width: 100%;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        .signature-row {
            margin-bottom: 10px;
        }

        @media print {
            body {
                width: 4in;
                height: 6in;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; padding: 10px; background: #eee; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-weight: bold; cursor: pointer;">PRINT MANIFEST</button>
    </div>

    <div class="container">
        <div class="header">
            @if(file_exists(public_path('assets/logo.png')))
                <img src="{{ asset('assets/logo.png') }}" alt="Logo" class="logo">
            @else
                <h2>THIRDYNAL</h2>
            @endif
            <div class="title">DISPATCH MANIFEST<br>& PROOF OF SCANNING</div>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">DISPATCH DATE:</td>
                <td>{{ $date }}</td>
            </tr>
            <tr>
                <td class="label">ORIGIN BRANCH:</td>
                <td>Guiguinto, Bulacan</td>
            </tr>
            <tr>
                <td class="label">DESTINATION HUB:</td>
                <td>Central Luzon Distribution Center</td>
            </tr>
            <tr>
                <td class="label">SESSION ID:</td>
                <td>#{{ $session->id }}</td>
            </tr>
            <tr>
                <td class="label">SCANNED BY:</td>
                <td>{{ $session->scanned_by }}</td>
            </tr>
        </table>

        <div style="font-weight: bold; text-align: center; border: 1px solid #000; border-bottom: none; padding: 2px; background: #e0e0e0;">
            WAYBILL SUMMARY & SCAN PROOF
        </div>
        <table class="waybill-table">
            <thead>
                <tr>
                    <th style="width: 10%;">No.</th>
                    <th>WAYBILL NUMBER</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}.</td>
                    <td>{{ $item->waybill_number }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            <div class="signature-row">
                <div>DISPATCHED BY: (Signature)</div>
                <div class="signature-line"></div>
            </div>
            
            <div class="signature-row">
                <div>RECEIVED BY: (Signature)</div>
                <div class="signature-line"></div>
            </div>

            <div class="signature-row">
                <div>DATE/TIME: <u>{{ $date }} {{ $time }}</u></div>
            </div>
        </div>
    </div>
</body>
</html>
