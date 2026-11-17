<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Receipt #{{ $order->id }} - {{ config('settings.app_name', 'Laravel POS') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            background-color: #f7f7f7;
            padding: 20px 0;
        }

        .receipt-container {
            width: 80mm;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 15px 12px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .action-bar {
            width: 80mm;
            margin: 0 auto 12px auto;
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            font-family: sans-serif;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: #007bff;
            color: #fff;
            flex: 1;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .header h2 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .header p {
            font-size: 11px;
            color: #333;
            margin: 2px 0;
        }

        .section {
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .row-item {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 11px;
        }

        .bold {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }

        th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 4px 0;
            font-size: 11px;
        }

        td {
            padding: 4px 0;
            border-bottom: 1px dotted #ccc;
            font-size: 11px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary {
            margin-top: 6px;
        }

        .total-box {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            margin: 6px 0;
            display: flex;
            justify-content: space-between;
        }

        .badge {
            text-align: center;
            padding: 4px;
            margin: 8px 0;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 1px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #444;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px dashed #000;
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }

            .action-bar {
                display: none !important;
            }

            .receipt-container {
                width: 100%;
                border: none;
                box-shadow: none;
                padding: 0;
                margin: 0;
            }

            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="action-bar">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Receipt</button>
        <a href="{{ route('orders.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <div class="receipt-container">
        <div class="header">
            <h2>{{ config('settings.app_name', 'Laravel POS') }}</h2>
            @if(config('settings.app_description'))
                <p>{{ config('settings.app_description') }}</p>
            @endif
            <p style="margin-top: 4px; font-weight: bold;">*** SALE RECEIPT ***</p>
        </div>

        <div class="section">
            <div class="row-item">
                <span class="bold">Receipt No:</span>
                <span>#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="row-item">
                <span class="bold">Date:</span>
                <span>{{ $order->created_at ? $order->created_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i') }}</span>
            </div>
            <div class="row-item">
                <span class="bold">Cashier:</span>
                <span>{{ $order->user->name ?? ($order->user->first_name ?? 'Staff') }}</span>
            </div>
            <div class="row-item">
                <span class="bold">Customer:</span>
                <span>{{ $order->getCustomerName() }}</span>
            </div>
        </div>

        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th style="width: 48%;">Item</th>
                        <th class="text-center" style="width: 14%;">Qty</th>
                        <th class="text-right" style="width: 18%;">Price</th>
                        <th class="text-right" style="width: 20%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        @php
                            $unitPrice = $item->quantity > 0 ? ($item->price / $item->quantity) : $item->price;
                        @endphp
                        <tr>
                            <td>{{ $item->product->name ?? 'Product #'.$item->product_id }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">{{ number_format($unitPrice, 2) }}</td>
                            <td class="text-right">{{ number_format($item->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="summary">
                <div class="row-item">
                    <span>Subtotal:</span>
                    <span class="bold">{{ currency_format($order->total()) }}</span>
                </div>

                <div class="total-box">
                    <span>TOTAL:</span>
                    <span>{{ currency_format($order->total()) }}</span>
                </div>

                <div class="row-item">
                    <span>Paid Amount:</span>
                    <span class="bold">{{ currency_format($order->receivedAmount()) }}</span>
                </div>

                @php
                    $balance = $order->total() - $order->receivedAmount();
                @endphp

                @if($balance > 0)
                    <div class="row-item" style="color: #c00;">
                        <span class="bold">Remaining Balance:</span>
                        <span class="bold">{{ currency_format($balance) }}</span>
                    </div>
                    <div class="badge">PAYMENT PENDING</div>
                @else
                    <div class="row-item">
                        <span>Change Due:</span>
                        <span class="bold">{{ currency_format(abs($balance)) }}</span>
                    </div>
                    <div class="badge">PAID IN FULL</div>
                @endif
            </div>
        </div>

        <div class="footer">
            <p>Thank you for shopping with us!</p>
            <p>Please keep this receipt for returns or exchanges.</p>
            <p style="margin-top: 4px;">{{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            // Auto open print dialog if opened in a popup or print query is set
            if (window.location.search.includes('autoprint=1')) {
                window.print();
            }
        });
    </script>
</body>
</html>
