<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\ProductRateEntity;

/**
 * ProductRate Repository Interface
 * 
 * Defines the contract for ProductRate persistence operations.
 * Infrastructure layer will provide concrete implementations.
 */
interface ProductRateRepositoryInterface
{
    /**
     * Save a product rate entity (create or update)
     * 
     * @param ProductRateEntity $entity
     * @return ProductRateEntity
     * @throws \Exception if save fails
     */
    public function save(ProductRateEntity $entity): ProductRateEntity;

    /**
     * Find a product rate by ID
     * 
     * @param int $id
     * @return ProductRateEntity|null
     */
    public function findById(int $id): ?ProductRateEntity;

    /**
     * Get all rates for a product
     * 
     * @param int $productId
     * @param bool $activeOnly
     * @return ProductRateEntity[]
     */
    public function findByProduct(int $productId, bool $activeOnly = false): array;

    /**
     * Get the current effective rate for a product on a specific date and unit
     * 
     * @param int $productId
     * @param \DateTimeInterface $date
     * @param string $unit
     * @return ProductRateEntity|null
     */
    public function getCurrentRate(int $productId, \DateTimeInterface $date, string $unit): ?ProductRateEntity;

    /**
     * List all product rates with optional filters
     * 
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array ['data' => ProductRateEntity[], 'total' => int, 'page' => int]
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * Delete a product rate by ID
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Check if a product rate exists
     * 
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool;

    /**
     * Deactivate all rates for a product except the specified one
     * 
     * @param int $productId
     * @param int $exceptRateId
     * @return void
     */
    public function deactivateOtherRates(int $productId, int $exceptRateId): void;
}
