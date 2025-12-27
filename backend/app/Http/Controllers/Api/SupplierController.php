<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name, code or contact
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $suppliers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:suppliers',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $supplier = Supplier::create([
            'name' => $request->name,
            'code' => $request->code ?? 'SUP' . str_pad(Supplier::count() + 1, 6, '0', STR_PAD_LEFT),
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
            'status' => $request->get('status', 'active'),
        ]);

        AuditLog::log('create', 'Supplier', $supplier->id, null, $supplier->toArray(), 'Supplier created', auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully',
            'data' => $supplier
        ], 201);
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier)
    {
        return response()->json([
            'success' => true,
            'data' => $supplier
        ]);
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50|unique:suppliers,code,' . $supplier->id,
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldData = $supplier->toArray();
        
        $supplier->update($request->only([
            'name', 'code', 'contact_person', 'phone', 'email',
            'address', 'city', 'state', 'country', 'postal_code', 'status'
        ]));

        AuditLog::log('update', 'Supplier', $supplier->id, $oldData, $supplier->toArray(), 'Supplier updated', auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => $supplier
        ]);
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        $oldData = $supplier->toArray();
        
        $supplier->delete();

        AuditLog::log('delete', 'Supplier', $supplier->id, $oldData, null, 'Supplier deleted', auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully'
        ]);
    }

    /**
     * Get collections for a supplier.
     */
    public function collections(Supplier $supplier)
    {
        $collections = $supplier->collections()
            ->with('product')
            ->orderBy('collection_date', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    /**
     * Get payments for a supplier.
     */
    public function payments(Supplier $supplier)
    {
        $payments = $supplier->payments()
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Get balance for a supplier.
     */
    public function balance(Supplier $supplier)
    {
        $balance = $supplier->calculateBalance();

        return response()->json([
            'success' => true,
            'data' => [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'total_collections' => $balance['total_collections'],
                'total_payments' => $balance['total_payments'],
                'outstanding_balance' => $balance['outstanding_balance'],
            ]
        ]);
    }
}
