<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\PaymentEntity;

/**
 * Payment Repository Interface
 * 
 * Defines the contract for Payment persistence operations.
 * Infrastructure layer will provide concrete implementations.
 */
interface PaymentRepositoryInterface
{
    /**
     * Save a payment entity (create or update)
     * 
     * @param PaymentEntity $entity
     * @return PaymentEntity
     * @throws \Exception if save fails
     */
    public function save(PaymentEntity $entity): PaymentEntity;

    /**
     * Find a payment by ID
     * 
     * @param int $id
     * @return PaymentEntity|null
     */
    public function findById(int $id): ?PaymentEntity;

    /**
     * Find payments by supplier
     * 
     * @param int $supplierId
     * @param \DateTimeInterface|null $fromDate
     * @param \DateTimeInterface|null $toDate
     * @return PaymentEntity[]
     */
    public function findBySupplier(int $supplierId, ?\DateTimeInterface $fromDate = null, ?\DateTimeInterface $toDate = null): array;

    /**
     * Find payments by type
     * 
     * @param string $paymentType
     * @return PaymentEntity[]
     */
    public function findByType(string $paymentType): array;

    /**
     * List all payments with optional filters
     * 
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array ['data' => PaymentEntity[], 'total' => int, 'page' => int]
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * Delete a payment by ID
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Check if a payment exists
     * 
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool;

    /**
     * Calculate total payments for a supplier
     * 
     * @param int $supplierId
     * @param \DateTimeInterface|null $fromDate
     * @param \DateTimeInterface|null $toDate
     * @return float
     */
    public function getTotalAmountBySupplier(int $supplierId, ?\DateTimeInterface $fromDate = null, ?\DateTimeInterface $toDate = null): float;

    /**
     * Get payments by reference number
     * 
     * @param string $referenceNumber
     * @return PaymentEntity|null
     */
    public function findByReferenceNumber(string $referenceNumber): ?PaymentEntity;
}
