<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\SupplierEntity;

/**
 * Supplier Repository Interface
 * 
 * Defines the contract for supplier persistence operations.
 * This interface belongs to the Domain layer and is implemented in the Infrastructure layer.
 * This follows the Dependency Inversion Principle (SOLID).
 */
interface SupplierRepositoryInterface
{
    /**
     * Find a supplier by ID
     *
     * @param int $id
     * @return SupplierEntity|null
     */
    public function findById(int $id): ?SupplierEntity;

    /**
     * Find a supplier by code
     *
     * @param string $code
     * @return SupplierEntity|null
     */
    public function findByCode(string $code): ?SupplierEntity;

    /**
     * Get all suppliers with optional filters
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * Save a supplier (create or update)
     *
     * @param SupplierEntity $supplier
     * @return SupplierEntity
     */
    public function save(SupplierEntity $supplier): SupplierEntity;

    /**
     * Delete a supplier
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Check if a supplier exists by ID
     *
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool;

    /**
     * Check if a supplier code is unique
     *
     * @param string $code
     * @param int|null $excludeId
     * @return bool
     */
    public function isCodeUnique(string $code, ?int $excludeId = null): bool;

    /**
     * Get supplier balance information
     *
     * @param int $id
     * @return array
     */
    public function getBalance(int $id): array;

    /**
     * Get supplier collections
     *
     * @param int $id
     * @param array $filters
     * @return array
     */
    public function getCollections(int $id, array $filters = []): array;

    /**
     * Get supplier payments
     *
     * @param int $id
     * @param array $filters
     * @return array
     */
    public function getPayments(int $id, array $filters = []): array;
}
