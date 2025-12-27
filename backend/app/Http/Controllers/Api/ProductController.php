<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRate;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:products',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create([
            'name' => $request->name,
            'code' => $request->code ?? 'PRD' . str_pad(Product::count() + 1, 6, '0', STR_PAD_LEFT),
            'description' => $request->description,
            'unit' => $request->unit,
            'status' => $request->get('status', 'active'),
        ]);

        AuditLog::log('create', 'Product', $product->id, null, $product->toArray(), 'Product created', auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50|unique:products,code,' . $product->id,
            'description' => 'nullable|string',
            'unit' => 'sometimes|required|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldData = $product->toArray();
        
        $product->update($request->only(['name', 'code', 'description', 'unit', 'status']));

        AuditLog::log('update', 'Product', $product->id, $oldData, $product->toArray(), 'Product updated', auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
    {
        $oldData = $product->toArray();
        
        $product->delete();

        AuditLog::log('delete', 'Product', $product->id, $oldData, null, 'Product deleted', auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Get all rates for a product.
     */
    public function rates(Product $product)
    {
        $rates = $product->rates()
            ->orderBy('effective_from', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rates
        ]);
    }

    /**
     * Add a new rate for a product.
     */
    public function addRate(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'rate_per_unit' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $rate = ProductRate::create([
            'product_id' => $product->id,
            'rate_per_unit' => $request->rate_per_unit,
            'effective_from' => $request->effective_from,
            'effective_to' => $request->effective_to,
        ]);

        AuditLog::log('add_rate', 'Product', $product->id, null, $rate->toArray(), 'Product rate added', auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Rate added successfully',
            'data' => $rate
        ], 201);
    }

    /**
     * Get current rate for a product.
     */
    public function currentRate(Product $product)
    {
        $rate = $product->currentRate();

        if (!$rate) {
            return response()->json([
                'success' => false,
                'message' => 'No active rate found for this product'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rate
        ]);
    }
}
