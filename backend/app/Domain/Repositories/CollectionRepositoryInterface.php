<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\CollectionEntity;

/**
 * Collection Repository Interface
 * 
 * Defines the contract for Collection persistence operations.
 * Infrastructure layer will provide concrete implementations.
 */
interface CollectionRepositoryInterface
{
    /**
     * Save a collection entity (create or update)
     * 
     * @param CollectionEntity $entity
     * @return CollectionEntity
     * @throws \Exception if save fails
     */
    public function save(CollectionEntity $entity): CollectionEntity;

    /**
     * Find a collection by ID
     * 
     * @param int $id
     * @return CollectionEntity|null
     */
    public function findById(int $id): ?CollectionEntity;

    /**
     * Find collections by supplier
     * 
     * @param int $supplierId
     * @param \DateTimeInterface|null $fromDate
     * @param \DateTimeInterface|null $toDate
     * @return CollectionEntity[]
     */
    public function findBySupplier(int $supplierId, ?\DateTimeInterface $fromDate = null, ?\DateTimeInterface $toDate = null): array;

    /**
     * Find collections by product
     * 
     * @param int $productId
     * @param \DateTimeInterface|null $fromDate
     * @param \DateTimeInterface|null $toDate
     * @return CollectionEntity[]
     */
    public function findByProduct(int $productId, ?\DateTimeInterface $fromDate = null, ?\DateTimeInterface $toDate = null): array;

    /**
     * Find collections by date
     * 
     * @param \DateTimeInterface $date
     * @return CollectionEntity[]
     */
    public function findByDate(\DateTimeInterface $date): array;

    /**
     * List all collections with optional filters
     * 
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array ['data' => CollectionEntity[], 'total' => int, 'page' => int]
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * Delete a collection by ID
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Check if a collection exists
     * 
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool;

    /**
     * Calculate total collections amount for a supplier
     * 
     * @param int $supplierId
     * @param \DateTimeInterface|null $fromDate
     * @param \DateTimeInterface|null $toDate
     * @return float
     */
    public function getTotalAmountBySupplier(int $supplierId, ?\DateTimeInterface $fromDate = null, ?\DateTimeInterface $toDate = null): float;
}
