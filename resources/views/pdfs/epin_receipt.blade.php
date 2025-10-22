<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $epinData['card_network'] }} Epin Cards</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 portrait;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ddd;
        }

        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
        }

        .cards-container {
            max-width: 190mm;
            margin: 0 auto;
        }

        .card-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .card-row {
            page-break-inside: avoid; /* Prevent row splitting across pages */
        }

        .epin-card {
            width: 58mm; /* Reduced to fit 3 cards + gaps within 190mm */
            height: 40mm;
            border: 2px dashed #999;
            border-radius: 8px;
            padding: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            vertical-align: top;
        }

        .epin-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                rgba(255,255,255,0.1) 0px,
                rgba(255,255,255,0.1) 1px,
                transparent 1px,
                transparent 20px
            );
            pointer-events: none;
        }

        .card-header {
            text-align: center;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }

        .network-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card-value {
            font-size: 12px;
            background: rgba(255,255,255,0.2);
            padding: 2px 6px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 2px;
        }

        .card-body {
            position: relative;
            z-index: 1;
        }

        .pin-section, .serial-section {
            margin-bottom: 4px;
        }

        .pin-label, .serial-label {
            font-size: 8px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pin-number, .serial-number {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            font-weight: bold;
            background: rgba(0,0,0,0.3);
            padding: 2px 4px;
            border-radius: 3px;
            margin-top: 1px;
            word-spacing: 1px;
            letter-spacing: 0.5px;
        }

        .card-footer {
            position: absolute;
            bottom: 3px;
            right: 6px;
            font-size: 7px;
            opacity: 0.7;
        }

        .cut-line {
            text-align: center;
            color: #999;
            font-size: 10px;
            margin: 8mm 0;
            page-break-before: auto;
        }

        /* Print optimizations */
        @media print {
            .header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .epin-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            body {
                print-color-adjust: exact;
            }
        }

        /* Alternative color schemes for different networks */
        .mtn-card {
            background: linear-gradient(135deg, #ffce00 0%, #ff9500 100%);
            color: #333;
        }

        .airtel-card {
            background: linear-gradient(135deg, #e60012 0%, #b30009 100%);
        }

        .glo-card {
            background: linear-gradient(135deg, #00a651 0%, #007b3a 100%);
        }

        .etisalat-card, .nine-mobile-card {
            background: linear-gradient(135deg, #00a651 0%, #006837 100%);
        }
    </style>
</head>
<body>
<!-- Header Section -->
<div class="header">
    <h1>{{ $epinData['card_network'] }} Epin Cards</h1>
</div>

<!-- Cards Container -->
<div class="cards-container">
    <table class="card-table">
        @foreach ($epinData['cards'] as $index => $card)
            @if ($index % 3 == 0)
                @if ($index > 0)
                    </tr>
        <tr class="cut-line"><td colspan="3">✂️ Cut along dashed lines ✂️</td></tr>
        @endif
        <tr class="card-row">
            @endif
            <td>
                <div class="epin-card {{ strtolower($epinData['card_network']) }}-card">
                    <div class="card-header">
                        <div class="network-name">{{ $epinData['card_network'] }}</div>
                        <div class="card-value">₦{{ number_format($epinData['value']) }}</div>
                    </div>

                    <div class="card-body">
                        <div class="pin-section">
                            <div class="pin-label">PIN</div>
                            <div class="pin-number">{{ chunk_split($card['pin'], 4, ' ') }}</div>
                        </div>

                        <div class="serial-section">
                            <div class="serial-label">Serial Number</div>
                            <div class="serial-number">{{ chunk_split($card['serial'], 4, ' ') }}</div>
                        </div>
                    </div>

                    <div class="card-footer">
                        Card {{ $index + 1 }}/{{ $epinData['quantity'] }}
                    </div>
                </div>
            </td>
            @if ($index % 3 == 2 || $index == $epinData['quantity'] - 1)
        </tr>
        @endif
        @endforeach
    </table>
</div>

<div class="cut-line" style="margin-top: 20px;">
    ✂️ Cut along dashed lines to separate individual cards ✂️
</div>

</body>
</html>
