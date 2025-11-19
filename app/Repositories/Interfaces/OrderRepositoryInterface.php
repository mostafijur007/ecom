<?php

namespace App\Repositories\Interfaces;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    /**
     * Get all orders with optional filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find order by ID with relationships
     *
     * @param int $id
     * @param array $relations
     * @return Order|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Order;

    /**
     * Create a new order
     *
     * @param array $data
     * @return Order
     */
    public function create(array $data): Order;

    /**
     * Update order
     *
     * @param Order $order
     * @param array $data
     * @return Order
     */
    public function update(Order $order, array $data): Order;

    /**
     * Get orders by customer ID
     *
     * @param int $customerId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCustomer(int $customerId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get orders by vendor ID
     *
     * @param int $vendorId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByVendor(int $vendorId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get orders by date range
     *
     * @param string $fromDate
     * @param string $toDate
     * @return Collection
     */
    public function getByDateRange(string $fromDate, string $toDate): Collection;

    /**
     * Get pending orders
     *
     * @return Collection
     */
    public function getPending(): Collection;

    /**
     * Get recent orders
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Calculate total sales for period
     *
     * @param string|null $fromDate
     * @param string|null $toDate
     * @param int|null $vendorId
     * @return float
     */
    public function calculateSales(?string $fromDate = null, ?string $toDate = null, ?int $vendorId = null): float;

    /**
     * Get order statistics
     *
     * @param int|null $vendorId
     * @return array
     */
    public function getStatistics(?int $vendorId = null): array;
}
