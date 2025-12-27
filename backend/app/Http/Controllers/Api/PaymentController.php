<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request)
    {
        $query = Payment::with('supplier');

        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by payment type
        if ($request->has('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('payment_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('payment_date', '<=', $request->to_date);
        }

        $perPage = $request->get('per_page', 15);
        $payments = $query->orderBy('payment_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_type' => 'required|in:advance,partial,final,adjustment',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
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
            $payment = Payment::create([
                'supplier_id' => $request->supplier_id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_type' => $request->payment_type,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'paid_by' => auth()->id(),
            ]);

            AuditLog::log('create', 'Payment', $payment->id, null, $payment->toArray(), 'Payment created', auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => $payment->load('supplier')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment',
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment)
    {
        return response()->json([
            'success' => true,
            'data' => $payment->load(['supplier', 'payer'])
        ]);
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, Payment $payment)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_date' => 'sometimes|required|date',
            'payment_type' => 'sometimes|required|in:advance,partial,final,adjustment',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
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
            $oldData = $payment->toArray();
            
            $payment->update($request->only([
                'supplier_id', 'amount', 'payment_date', 'payment_type',
                'payment_method', 'reference_number', 'notes'
            ]));

            AuditLog::log('update', 'Payment', $payment->id, $oldData, $payment->toArray(), 'Payment updated', auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => $payment->load('supplier')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment',
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Payment $payment)
    {
        $oldData = $payment->toArray();
        
        $payment->delete();

        AuditLog::log('delete', 'Payment', $payment->id, $oldData, null, 'Payment deleted', auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully'
        ]);
    }

    /**
     * Get payments by supplier.
     */
    public function bySupplier($supplierId)
    {
        $payments = Payment::where('supplier_id', $supplierId)
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Calculate payment details for a supplier.
     */
    public function calculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $supplier = Supplier::find($request->supplier_id);
        $balance = $supplier->calculateBalance($request->from_date, $request->to_date);

        return response()->json([
            'success' => true,
            'data' => [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'total_collections' => $balance['total_collections'],
                'total_payments' => $balance['total_payments'],
                'outstanding_balance' => $balance['outstanding_balance'],
            ]
        ]);
    }
}
