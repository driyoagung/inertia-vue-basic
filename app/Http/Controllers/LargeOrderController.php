<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LargeOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:pending,paid,shipped,completed,cancelled'],
            'sort' => ['nullable', 'string', 'in:id,invoice_number,customer_name,customer_email,status,total,ordered_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $search = $validated['search'] ?? null;
        $status = $validated['status'] ?? null;
        $sort = $validated['sort'] ?? 'ordered_at';
        $direction = $validated['direction'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 25;

        $orders = Order::query()
            ->select(['id', 'invoice_number', 'customer_name', 'customer_email', 'status', 'total', 'ordered_at'])
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                'id' => $order->id,
                'invoice_number' => $order->invoice_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'status' => $order->status,
                'total' => (float) $order->total,
                'ordered_at' => $order->ordered_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('LargeOrders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'statuses' => ['pending', 'paid', 'shipped', 'completed', 'cancelled'],
        ]);
    }
}
