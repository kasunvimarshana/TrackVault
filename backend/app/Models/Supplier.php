<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'status',
        'metadata',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the collections for this supplier.
     */
    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    /**
     * Get the payments for this supplier.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Calculate total collections for this supplier.
     */
    public function totalCollections()
    {
        return $this->collections()->sum('total_amount');
    }

    /**
     * Calculate total payments for this supplier.
     */
    public function totalPayments()
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Calculate outstanding balance for this supplier.
     */
    public function outstandingBalance()
    {
        return $this->totalCollections() - $this->totalPayments();
    }
}
