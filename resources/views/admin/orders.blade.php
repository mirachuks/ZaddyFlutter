@extends('admin.layouts.app')

@section('page_title', 'Orders')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Job and order management</h2>
            <p class="mt-1 text-sm text-slate-500">Search by customer email, name, status, or address and sort by newest or oldest orders.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-600">Lifecycle control</span>
    </div>
    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" class="flex flex-col gap-2 sm:flex-row">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search email, name, status, address" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="sort" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="latest" {{ ($sort ?? 'latest') === 'latest' ? 'selected' : '' }}>Newest first</option>
                <option value="oldest" {{ ($sort ?? 'latest') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
            </select>
            <button class="rounded-lg border border-emerald-300 px-3 py-2 text-sm text-emerald-700">Apply</button>
        </form>
        @if(($search ?? '') !== '')
        <a href="{{ route('admin.orders') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear</a>
        @endif
    </div>
    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-[950px] w-full text-sm">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="py-3">Order</th>
                    <th class="py-3">Customer</th>
                    <th class="py-3">Status</th>
                    <th class="py-3">Amount</th>
                    <th class="py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr class="border-b align-top bg-white hover:bg-slate-50">
                    <td class="py-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <div class="font-semibold text-slate-800">{{ $order->title ?? 'Job #' . $order->id }}</div>
                                    <div class="mt-1 text-[11px] uppercase tracking-wide text-slate-500">Order #{{ $order->id }}</div>
                                </div>
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-medium text-emerald-700">{{ $order->status }}</span>
                            </div>

                            <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                                <div class="rounded-lg bg-white p-2">
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Pickup</div>
                                    <div class="mt-1">{{ $order->pickup_address ?? 'N/A' }}</div>
                                </div>
                                <div class="rounded-lg bg-white p-2">
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Dropoff</div>
                                    <div class="mt-1">{{ $order->dropoff_address ?? 'N/A' }}</div>
                                </div>
                            </div>

                            @if($order->items->isNotEmpty())
                            <div class="mt-3 rounded-lg border border-slate-200 bg-white p-2">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Linked items</div>
                                <div class="mt-2 space-y-2">
                                    @foreach($order->items as $item)
                                    <div class="flex items-start justify-between gap-2 rounded-md bg-slate-50 p-2">
                                        <div>
                                            <div class="font-medium text-slate-700">{{ $item->title ?? 'Item #' . $item->id }}</div>
                                            <div class="text-xs text-slate-500">{{ $item->receiver_name ?? 'No receiver' }}</div>
                                        </div>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">{{ $item->item_category ?? 'Item' }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @else
                            <div class="mt-3 rounded-lg border border-dashed border-slate-200 bg-white p-2 text-sm text-slate-500">No job items attached.</div>
                            @endif
                        </div>
                    </td>
                    <td class="py-3 align-top">
                        <div class="font-medium text-slate-800">{{ $order->user?->first_name ?? 'Customer' }} {{ $order->user?->last_name ?? '' }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $order->user?->email ?? 'No email found' }}</div>
                    </td>
                    <td class="py-3 align-top">{{ $order->status }}</td>
                    <td class="py-3 align-top">{{ number_format($order->total_price ?? 0, 2) }}</td>
                    <td class="py-3 align-top">
                        <button type="button" onclick="document.getElementById('order-modal-{{ $order->id }}').classList.remove('hidden')" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs">View details</button>
                    </td>
                </tr>
                <tr id="order-modal-{{ $order->id }}" class="hidden">
                    <td colspan="5" class="px-0 py-0">
                        <div class="border-b border-slate-200 bg-slate-50 p-4">
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-semibold">{{ $order->title ?? 'Job #' . $order->id }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">Complete order overview and item breakdown</p>
                                    </div>
                                    <button type="button" onclick="document.getElementById('order-modal-{{ $order->id }}').classList.add('hidden')" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">Close</button>
                                </div>
                                <div class="mt-5 grid gap-4 md:grid-cols-2">
                                    <div class="rounded-xl border border-slate-200 p-4">
                                        <div class="text-sm font-medium text-slate-700">Order details</div>
                                        <div class="mt-3 space-y-2 text-sm text-slate-600">
                                            <div><span class="font-medium">Customer:</span> {{ $order->user?->first_name ?? 'Customer' }} {{ $order->user?->last_name ?? '' }}</div>
                                            <div><span class="font-medium">Email:</span> {{ $order->user?->email ?? 'No email found' }}</div>
                                            <div><span class="font-medium">Status:</span> {{ $order->status }}</div>
                                            <div><span class="font-medium">Pickup:</span> {{ $order->pickup_address ?? 'N/A' }}</div>
                                            <div><span class="font-medium">Dropoff:</span> {{ $order->dropoff_address ?? 'N/A' }}</div>
                                            <div><span class="font-medium">Price:</span> {{ number_format($order->price ?? 0, 2) }}</div>
                                            <div><span class="font-medium">Platform charge:</span> {{ number_format($order->platform_charge ?? 0, 2) }}</div>
                                            <div><span class="font-medium">Order fee:</span> {{ number_format($order->order_fee ?? 0, 2) }}</div>
                                            <div><span class="font-medium">Total fee:</span> {{ number_format($order->total_price ?? 0, 2) }}</div>
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 p-4">
                                        <div class="text-sm font-medium text-slate-700">Job items</div>
                                        @if($order->items->isNotEmpty())
                                        <div class="mt-3 space-y-3">
                                            @foreach($order->items as $item)
                                            <div class="rounded-lg border border-slate-200 p-3 text-sm text-slate-600">
                                                <div class="font-medium text-slate-700">{{ $item->title ?? 'Item #' . $item->id }}</div>
                                                <div class="mt-1">Category: {{ $item->item_category ?? 'N/A' }}</div>
                                                <div>Receiver: {{ $item->receiver_name ?? 'N/A' }}</div>
                                                <div>Phone: {{ $item->receiver_phone ?? 'N/A' }}</div>
                                                <div>Pickup: {{ $item->pickup_address ?? 'N/A' }}</div>
                                                <div>Dropoff: {{ $item->dropoff_address ?? 'N/A' }}</div>
                                                <div>Description: {{ $item->description ?? 'N/A' }}</div>
                                            </div>
                                            @endforeach
                                        </div>
                                        @else
                                        <div class="mt-3 text-sm text-slate-500">No job items attached.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-4 text-slate-500">No orders found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</div>
@endsection