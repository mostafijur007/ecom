<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private Order $model
    ) {
    }

    public function getAllWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['customer', 'items.product']);

        // Filter by customer
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by payment status
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function findWithRelations(int $id, array $relations = []): ?Order
    {
        $defaultRelations = ['customer', 'items.product', 'items.variant', 'invoice'];
        $relations = !empty($relations) ? $relations : $defaultRelations;
        
        return $this->model->with($relations)->find($id);
    }

    public function create(array $data): Order
    {
        return $this->model->create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);
        return $order->fresh();
    }

    public function getByCustomer(int $customerId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['customer_id'] = $customerId;
        return $this->getAllWithFilters($filters, $perPage);
    }

    public function getByVendor(int $vendorId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['customer', 'items.product'])
            ->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });

        // Apply additional filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function getByDateRange(string $fromDate, string $toDate): Collection
    {
        return $this->model->with(['customer', 'items'])
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->get();
    }

    public function getPending(): Collection
    {
        return $this->model->with(['customer', 'items'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->with(['customer', 'items'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function calculateSales(?string $fromDate = null, ?string $toDate = null, ?int $vendorId = null): float
    {
        $query = $this->model->whereIn('status', ['processing', 'shipped', 'delivered']);

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        if ($vendorId) {
            $query->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });
        }

        return (float) $query->sum('total');
    }

    public function getStatistics(?int $vendorId = null): array
    {
        $query = $this->model->query();

        if ($vendorId) {
            $query->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });
        }

        return [
            'total_orders' => $query->count(),
            'pending_orders' => (clone $query)->where('status', 'pending')->count(),
            'processing_orders' => (clone $query)->where('status', 'processing')->count(),
            'shipped_orders' => (clone $query)->where('status', 'shipped')->count(),
            'delivered_orders' => (clone $query)->where('status', 'delivered')->count(),
            'cancelled_orders' => (clone $query)->where('status', 'cancelled')->count(),
            'total_revenue' => (float) (clone $query)->whereIn('status', ['processing', 'shipped', 'delivered'])->sum('total'),
            'average_order_value' => (float) (clone $query)->whereIn('status', ['processing', 'shipped', 'delivered'])->avg('total'),
        ];
    }
}
