<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">{{ $title ?? 'Orders' }}</h5>
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Order</th>
                <th>Farmer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $order)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div class="fw-semibold">#{{ $order->id }}</div>
                        <small class="text-muted">{{ optional($order->created_at)->format('d-m-Y h:i A') }}</small>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $order->farmer_name ?: optional($order->farmer)->name ?: 'Farmer' }}</div>
                        <small class="text-muted">{{ $order->farmer_phone ?: optional($order->farmer)->mobile_number ?: '-' }}</small>
                    </td>
                    <td>
                        @foreach($order->items as $item)
                            <div class="small">{{ $item->product_name }} x {{ $item->quantity }}</div>
                        @endforeach
                    </td>
                    <td>Rs {{ number_format((float) $order->total, 2) }}</td>
                    <td><span class="badge bg-light text-dark text-capitalize">{{ str_replace('_', ' ', $order->status) }}</span></td>
                    <td>
                        <div class="small text-capitalize">{{ $order->payment_method ?: 'cod' }}</div>
                        <span class="badge {{ $order->payment_status === 'paid' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                            {{ $order->payment_status ?: 'pending' }}
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('shop.orders.status', $order) }}" class="d-flex gap-2 align-items-center flex-wrap">
                            @csrf
                            <input type="hidden" name="tab" value="{{ $tab ?? 'new-order' }}">
                            <select name="status" class="form-select form-select-sm" style="width: 140px;">
                                <option value="placed" {{ $order->status === 'placed' ? 'selected' : '' }}>Placed</option>
                                <option value="in_progress" {{ $order->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            <select name="payment_status" class="form-select form-select-sm" style="width: 120px;">
                                <option value="pending" {{ ($order->payment_status ?? 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">No orders found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
