@extends('layouts.admin')

@section('title', 'Sales & Inventory Reports')
@section('content-header', 'Sales & Inventory Reports')

@section('content')
<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form action="{{ route('reports.index') }}" method="GET" class="row align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="font-weight-bold">Quick Date Range</label>
                    <select name="preset" class="form-control" onchange="this.form.submit()">
                        <option value="today" {{ $preset == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="last_7_days" {{ $preset == 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="this_month" {{ $preset == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ $preset == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="all_time" {{ $preset == 'all_time' ? 'selected' : '' }}>All Time</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="font-weight-bold">Start Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="font-weight-bold">End Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
                </div>
                <div class="col-md-3 mb-2 d-flex">
                    <button type="submit" class="btn btn-primary mr-2 flex-grow-1">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('reports.export', ['preset' => $preset, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-success" title="Export CSV">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalOrders }}</h3>
                    <p>Total Orders</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ currency_format($totalRevenue) }}</h3>
                    <p>Total Sales Revenue</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $totalItemsSold }}</h3>
                    <p>Items Sold</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-purple" style="background-color: #6f42c1; color: white;">
                <div class="inner">
                    <h3>{{ currency_format($avgOrderValue) }}</h3>
                    <p>Average Order Value</p>
                </div>
                <div class="icon">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Daily Sales Trend Table -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-calendar-day mr-1"></i> Daily Sales Breakdown
                    </h3>
                </div>
                <div class="card-body table-responsive p-0" style="max-height: 400px;">
                    <table class="table table-striped table-valign-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-center">Orders</th>
                                <th class="text-center">Items Sold</th>
                                <th class="text-right">Revenue</th>
                                <th class="text-right">Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyData as $row)
                                <tr>
                                    <td><strong>{{ $row['date'] }}</strong></td>
                                    <td class="text-center"><span class="badge badge-primary">{{ $row['orders_count'] }}</span></td>
                                    <td class="text-center">{{ $row['items_count'] }}</td>
                                    <td class="text-right font-weight-bold text-success">{{ currency_format($row['revenue']) }}</td>
                                    <td class="text-right text-info">{{ currency_format($row['received']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No sales records in this date range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Selling Products in Range -->
            <div class="card mt-4">
                <div class="card-header border-0">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-fire text-danger mr-1"></i> Top Selling Products
                    </h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Barcode</th>
                                <th class="text-center">Units Sold</th>
                                <th class="text-right">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $idx => $item)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td><strong>{{ $item->product->name ?? 'Product #'.$item->product_id }}</strong></td>
                                    <td><code>{{ $item->product->barcode ?? '-' }}</code></td>
                                    <td class="text-center"><span class="badge badge-info">{{ $item->total_quantity }}</span></td>
                                    <td class="text-right font-weight-bold">{{ currency_format($item->total_amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No product sales in this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert and Purchase Recommendation -->
        <div class="col-lg-5">
            <div class="card border-warning">
                <div class="card-header bg-warning py-2">
                    <h3 class="card-title font-weight-bold text-dark">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Low Stock & Reorder Alert
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-dark">Threshold: &lt; {{ $warningLimit }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Stock</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockProducts as $p)
                                    <tr>
                                        <td>
                                            <strong>{{ $p->name }}</strong><br>
                                            <small class="text-muted">{{ $p->barcode }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($p->quantity <= 0)
                                                <span class="badge badge-danger">0</span>
                                            @else
                                                <span class="badge badge-warning">{{ $p->quantity }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($p->quantity <= 0)
                                                <span class="text-danger font-weight-bold">Out of Stock</span>
                                            @else
                                                <span class="text-warning font-weight-bold">Low Stock</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('purchases.create') }}" class="btn btn-xs btn-primary" title="Create Purchase Order">
                                                <i class="fas fa-plus"></i> Reorder
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-success py-4">
                                            <i class="fas fa-check-circle"></i> All products have sufficient inventory!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center bg-light">
                    <a href="{{ route('products.index', ['stock_status' => 'low_stock']) }}" class="btn btn-sm btn-outline-secondary">
                        View All In Products Management <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Financial Profit Snapshot -->
            <div class="card mt-4">
                <div class="card-header bg-primary text-white py-2">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-coins mr-1"></i> Period Financial Snapshot
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <b>Total Revenue</b> <span>{{ currency_format($totalRevenue) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <b>Received Payments</b> <span class="text-success font-weight-bold">{{ currency_format($totalReceived) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <b>Unpaid / Balance</b> <span class="text-danger font-weight-bold">{{ currency_format(max(0, $totalRevenue - $totalReceived)) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <b>Estimated Gross Profit</b> <span class="text-primary font-weight-bold">{{ currency_format($grossProfit) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
