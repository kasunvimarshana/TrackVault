<?php

namespace App\Infrastructure\Sync;

use App\Domain\Repositories\SupplierRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sync Service
 * 
 * Handles synchronization of offline data from mobile devices.
 * Implements conflict detection and resolution strategies.
 * Ensures data integrity across multiple users and devices.
 */
class SyncService
{
    /**
     * Sync suppliers from mobile device
     *
     * @param array $suppliers Array of supplier data from mobile device
     * @param int $userId User ID performing the sync
     * @return array Sync result with conflicts and successes
     */
    public function syncSuppliers(array $suppliers, int $userId): array
    {
        $results = [
            'success' => [],
            'conflicts' => [],
            'errors' => [],
        ];

        DB::beginTransaction();
        
        try {
            foreach ($suppliers as $supplierData) {
                $result = $this->syncSingleSupplier($supplierData, $userId);
                
                if ($result['status'] === 'success') {
                    $results['success'][] = $result;
                } elseif ($result['status'] === 'conflict') {
                    $results['conflicts'][] = $result;
                } else {
                    $results['errors'][] = $result;
                }
            }

            DB::commit();
            
            Log::info('Sync completed', [
                'user_id' => $userId,
                'success_count' => count($results['success']),
                'conflict_count' => count($results['conflicts']),
                'error_count' => count($results['errors']),
            ]);

            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Sync failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Sync operation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Sync a single supplier with conflict detection
     *
     * @param array $data Supplier data from mobile device
     * @param int $userId User ID
     * @return array Sync result
     */
    private function syncSingleSupplier(array $data, int $userId): array
    {
        // Extract sync metadata
        $localId = $data['local_id'] ?? null;
        $serverId = $data['id'] ?? null;
        $clientVersion = $data['version'] ?? 1;
        $lastSyncedAt = $data['last_synced_at'] ?? null;

        try {
            if ($serverId !== null) {
                // Update existing supplier
                return $this->updateExistingSupplier($serverId, $data, $clientVersion, $userId);
            } else {
                // Create new supplier
                return $this->createNewSupplier($data, $localId, $userId);
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'local_id' => $localId,
                'server_id' => $serverId,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create new supplier from mobile data
     */
    private function createNewSupplier(array $data, ?string $localId, int $userId): array
    {
        $supplier = \App\Models\Supplier::create([
            'name' => $data['name'],
            'code' => $data['code'] ?? 'SUP' . str_pad(\App\Models\Supplier::count() + 1, 6, '0', STR_PAD_LEFT),
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'status' => $data['status'] ?? 'active',
            'version' => 1,
        ]);

        \App\Models\AuditLog::log('create', 'Supplier', $supplier->id, null, $supplier->toArray(), 
            'Supplier created via sync', $userId);

        return [
            'status' => 'success',
            'action' => 'created',
            'local_id' => $localId,
            'server_id' => $supplier->id,
            'version' => $supplier->version,
            'data' => $supplier->toArray(),
        ];
    }

    /**
     * Update existing supplier with conflict detection
     */
    private function updateExistingSupplier(int $serverId, array $data, int $clientVersion, int $userId): array
    {
        $supplier = \App\Models\Supplier::findOrFail($serverId);
        $serverVersion = $supplier->version ?? 1;

        // Conflict detection: Check if server version matches client version
        if ($serverVersion !== $clientVersion) {
            // Conflict detected - server has newer version
            return [
                'status' => 'conflict',
                'action' => 'conflict_detected',
                'server_id' => $serverId,
                'local_version' => $clientVersion,
                'server_version' => $serverVersion,
                'server_data' => $supplier->toArray(),
                'client_data' => $data,
                'message' => 'Version conflict: server has been modified since last sync',
            ];
        }

        // No conflict - proceed with update
        $oldData = $supplier->toArray();

        $supplier->update([
            'name' => $data['name'] ?? $supplier->name,
            'code' => $data['code'] ?? $supplier->code,
            'contact_person' => $data['contact_person'] ?? $supplier->contact_person,
            'phone' => $data['phone'] ?? $supplier->phone,
            'email' => $data['email'] ?? $supplier->email,
            'address' => $data['address'] ?? $supplier->address,
            'city' => $data['city'] ?? $supplier->city,
            'state' => $data['state'] ?? $supplier->state,
            'country' => $data['country'] ?? $supplier->country,
            'postal_code' => $data['postal_code'] ?? $supplier->postal_code,
            'status' => $data['status'] ?? $supplier->status,
            'version' => $serverVersion + 1,
        ]);

        $supplier->refresh();

        \App\Models\AuditLog::log('update', 'Supplier', $supplier->id, $oldData, $supplier->toArray(), 
            'Supplier updated via sync', $userId);

        return [
            'status' => 'success',
            'action' => 'updated',
            'server_id' => $supplier->id,
            'version' => $supplier->version,
            'data' => $supplier->toArray(),
        ];
    }

    /**
     * Resolve sync conflict using specified strategy
     *
     * @param int $serverId Supplier ID
     * @param array $clientData Client data
     * @param string $strategy Resolution strategy (server_wins, client_wins, merge)
     * @param int $userId User ID
     * @return array Resolution result
     */
    public function resolveConflict(int $serverId, array $clientData, string $strategy, int $userId): array
    {
        $supplier = \App\Models\Supplier::findOrFail($serverId);

        switch ($strategy) {
            case 'server_wins':
                // Keep server data, discard client changes
                return [
                    'status' => 'resolved',
                    'strategy' => 'server_wins',
                    'data' => $supplier->toArray(),
                ];

            case 'client_wins':
                // Apply client changes, increment version
                $oldData = $supplier->toArray();
                $supplier->update(array_merge($clientData, [
                    'version' => $supplier->version + 1,
                ]));
                $supplier->refresh();

                \App\Models\AuditLog::log('update', 'Supplier', $supplier->id, $oldData, $supplier->toArray(), 
                    'Conflict resolved: client wins', $userId);

                return [
                    'status' => 'resolved',
                    'strategy' => 'client_wins',
                    'data' => $supplier->toArray(),
                ];

            case 'merge':
                // Intelligent merge - preserve non-conflicting changes
                $oldData = $supplier->toArray();
                $merged = $this->mergeData($supplier->toArray(), $clientData);
                $supplier->update(array_merge($merged, [
                    'version' => $supplier->version + 1,
                ]));
                $supplier->refresh();

                \App\Models\AuditLog::log('update', 'Supplier', $supplier->id, $oldData, $supplier->toArray(), 
                    'Conflict resolved: merged', $userId);

                return [
                    'status' => 'resolved',
                    'strategy' => 'merge',
                    'data' => $supplier->toArray(),
                ];

            default:
                throw new \InvalidArgumentException("Invalid conflict resolution strategy: {$strategy}");
        }
    }

    /**
     * Merge server and client data intelligently
     * Keeps client changes for user-modified fields
     */
    private function mergeData(array $serverData, array $clientData): array
    {
        // Simple merge strategy: client data takes precedence for specified fields
        return array_merge($serverData, array_filter($clientData, function ($value) {
            return $value !== null;
        }));
    }

    /**
     * Get changes since last sync for a user
     *
     * @param int $userId User ID
     * @param string|null $lastSyncedAt ISO 8601 timestamp of last sync
     * @return array Changes since last sync
     */
    public function getChangesSinceLastSync(int $userId, ?string $lastSyncedAt): array
    {
        $query = \App\Models\Supplier::query();

        if ($lastSyncedAt !== null) {
            $query->where('updated_at', '>', $lastSyncedAt);
        }

        $suppliers = $query->get();

        return [
            'suppliers' => $suppliers->map(fn($s) => $s->toArray())->toArray(),
            'sync_timestamp' => now()->toISOString(),
        ];
    }
}
