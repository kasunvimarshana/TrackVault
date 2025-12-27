<?php

namespace App\Application\UseCases\Collection;

use App\Domain\Repositories\CollectionRepositoryInterface;

/**
 * List Collections Use Case
 * 
 * Lists collections with optional filtering and pagination.
 */
class ListCollectionsUseCase
{
    private CollectionRepositoryInterface $repository;

    public function __construct(CollectionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Execute the use case
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array ['data' => CollectionEntity[], 'total' => int, 'page' => int]
     */
    public function execute(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        return $this->repository->list($filters, $page, $perPage);
    }
}
