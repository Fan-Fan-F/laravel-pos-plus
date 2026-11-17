<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sale Receipt #{{ $order->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.4;
            width: 70mm;
            padding: 5mm;
            color: #111;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .header p {
            font-size: 10px;
            margin: 2px 0;
            color: #444;
        }

        .section {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #000;
        }

        .info-row {
            margin: 2px 0;
            font-size: 10px;
            clear: both;
        }

        .info-label {
            float: left;
            font-weight: bold;
        }

        .info-value {
            float: right;
            text-align: right;
        }

        .clearfix {
            clear: both;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }

        th {
            text-align: left;
            padding: 4px 0;
            border-bottom: 1px solid #000;
            font-weight: bold;
            font-size: 10px;
        }

        td {
            padding: 4px 0;
            font-size: 10px;
            border-bottom: 1px dotted #ccc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            font-size: 11px;
            font-weight: bold;
            padding: 3px 0;
        }

        .grand-total {
            font-size: 13px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 0;
            margin-top: 4px;
        }

        .status-badge {
            text-align: center;
            margin: 6px 0;
            padding: 3px;
            font-weight: bold;
            border: 1px solid #000;
            font-size: 11px;
            text-transform: uppercase;
        }

        .footer {
            text-align: center;
            font-size: 9px;
            color: #555;
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px dashed #000;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('settings.app_name', 'Laravel POS') }}</h1>
        @if(config('settings.app_description'))
            <p>{{ config('settings.app_description') }}</p>
        @endif
        <p>*** SALE RECEIPT ***</p>
    </div>

    <div class="section">
        <div class="info-row">
            <span class="info-label">Receipt No:</span>
            <span class="info-value">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
            <div class="clearfix"></div>
        </div>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value">{{ $order->created_at ? $order->created_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i') }}</span>
            <div class="clearfix"></div>
        </div>
        <div class="info-row">
            <span class="info-label">Cashier:</span>
            <span class="info-value">{{ $order->user->name ?? ($order->user->first_name ?? 'Staff') }}</span>
            <div class="clearfix"></div>
        </div>
        <div class="info-row">
            <span class="info-label">Customer:</span>
            <span class="info-value">{{ $order->getCustomerName() }}</span>
            <div class="clearfix"></div>
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

        <div class="info-row total-row">
            <span class="info-label">Subtotal:</span>
            <span class="info-value">{{ currency_format($order->total()) }}</span>
            <div class="clearfix"></div>
        </div>

        <div class="info-row grand-total">
            <span class="info-label">TOTAL:</span>
            <span class="info-value">{{ currency_format($order->total()) }}</span>
            <div class="clearfix"></div>
        </div>

        <div class="info-row total-row" style="margin-top: 5px;">
            <span class="info-label">Paid Amount:</span>
            <span class="info-value">{{ currency_format($order->receivedAmount()) }}</span>
            <div class="clearfix"></div>
        </div>

        @php
            $balance = $order->total() - $order->receivedAmount();
        @endphp

        @if($balance > 0)
            <div class="info-row total-row" style="color: #c00;">
                <span class="info-label">Remaining Balance:</span>
                <span class="info-value">{{ currency_format($balance) }}</span>
                <div class="clearfix"></div>
            </div>
            <div class="status-badge">Payment Pending</div>
        @else
            <div class="info-row total-row">
                <span class="info-label">Change Due:</span>
                <span class="info-value">{{ currency_format(abs($balance)) }}</span>
                <div class="clearfix"></div>
            </div>
            <div class="status-badge">PAID IN FULL</div>
        @endif
    </div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Please keep this receipt for your records.</p>
        <p>{{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
