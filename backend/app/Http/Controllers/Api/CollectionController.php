<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Product;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{
    /**
     * Display a listing of collections.
     */
    public function index(Request $request)
    {
        $query = Collection::with(['supplier', 'product']);

        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('collection_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('collection_date', '<=', $request->to_date);
        }

        $perPage = $request->get('per_page', 15);
        $collections = $query->orderBy('collection_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    /**
     * Store a newly created collection.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'collection_date' => 'required|date',
            'rate_per_unit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get rate if not provided
            $rate = $request->rate_per_unit;
            if (!$rate) {
                $product = Product::find($request->product_id);
                $currentRate = $product->currentRate();
                if ($currentRate) {
                    $rate = $currentRate->rate_per_unit;
                }
            }

            // Calculate total amount
            $totalAmount = $request->quantity * ($rate ?? 0);

            $collection = Collection::create([
                'supplier_id' => $request->supplier_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'unit' => $request->unit,
                'collection_date' => $request->collection_date,
                'rate_per_unit' => $rate,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'collected_by' => auth()->id(),
            ]);

            AuditLog::log('create', 'Collection', $collection->id, null, $collection->toArray(), 'Collection created', auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Collection created successfully',
                'data' => $collection->load(['supplier', 'product'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create collection',
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Display the specified collection.
     */
    public function show(Collection $collection)
    {
        return response()->json([
            'success' => true,
            'data' => $collection->load(['supplier', 'product', 'collector'])
        ]);
    }

    /**
     * Update the specified collection.
     */
    public function update(Request $request, Collection $collection)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'quantity' => 'sometimes|required|numeric|min:0',
            'unit' => 'sometimes|required|string|max:20',
            'collection_date' => 'sometimes|required|date',
            'rate_per_unit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $oldData = $collection->toArray();
            
            $data = $request->only(['supplier_id', 'product_id', 'quantity', 'unit', 'collection_date', 'rate_per_unit', 'notes']);
            
            // Recalculate total if quantity or rate changed
            if ($request->has('quantity') || $request->has('rate_per_unit')) {
                $quantity = $request->get('quantity', $collection->quantity);
                $rate = $request->get('rate_per_unit', $collection->rate_per_unit);
                $data['total_amount'] = $quantity * ($rate ?? 0);
            }

            $collection->update($data);

            AuditLog::log('update', 'Collection', $collection->id, $oldData, $collection->toArray(), 'Collection updated', auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Collection updated successfully',
                'data' => $collection->load(['supplier', 'product'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update collection',
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Remove the specified collection.
     */
    public function destroy(Collection $collection)
    {
        $oldData = $collection->toArray();
        
        $collection->delete();

        AuditLog::log('delete', 'Collection', $collection->id, $oldData, null, 'Collection deleted', auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Collection deleted successfully'
        ]);
    }

    /**
     * Get collections by supplier.
     */
    public function bySupplier($supplierId)
    {
        $collections = Collection::with('product')
            ->where('supplier_id', $supplierId)
            ->orderBy('collection_date', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    /**
     * Get collections by date.
     */
    public function byDate($date)
    {
        $collections = Collection::with(['supplier', 'product'])
            ->whereDate('collection_date', $date)
            ->orderBy('collection_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }
}
