<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'midtrans_order_id', // Saved Midtrans order_id for fast status lookup
        'payment_status',  // Controlled ONLY by Midtrans webhook: pending, paid, failed, expired
        'order_status',    // Controlled by admin: new, processing, shipped, completed, refunded
        'total_amount',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Payment Status Helpers
     * These statuses are controlled ONLY by Midtrans webhook
     */
    public function isPaymentPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isPaymentPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPaymentFailed(): bool
    {
        return $this->payment_status === 'failed';
    }

    public function isPaymentExpired(): bool
    {
        return $this->payment_status === 'expired';
    }

    /**
     * Order Status Helpers
     * These statuses are controlled by admin for fulfillment workflow
     */
    public function isOrderNew(): bool
    {
        return $this->order_status === 'new';
    }

    public function isOrderProcessing(): bool
    {
        return $this->order_status === 'processing';
    }

    public function isOrderShipped(): bool
    {
        return $this->order_status === 'shipped';
    }

    public function isOrderCompleted(): bool
    {
        return $this->order_status === 'completed';
    }

    public function isOrderRefunded(): bool
    {
        return $this->order_status === 'refunded';
    }

    /**
     * Business Logic Helpers
     */
    public function canBeFulfilled(): bool
    {
        // Order can only be fulfilled if payment is paid
        return $this->isPaymentPaid() && !$this->isOrderCompleted() && !$this->isOrderRefunded();
    }

    public function canBeCancelled(): bool
    {
        // Order can be cancelled if not completed or refunded
        return !$this->isOrderCompleted() && !$this->isOrderRefunded();
    }

    /**
     * Scope: Filter by payment status
     */
    public function scopePaymentStatus($query, string $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope: Filter by order status
     */
    public function scopeOrderStatus($query, string $status)
    {
        return $query->where('order_status', $status);
    }

    /**
     * Scope: Only paid orders (for fulfillment)
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }
}

