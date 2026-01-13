<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Separates payment status (controlled by Midtrans webhook) 
     * from order fulfillment status (controlled by admin)
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add payment_status column (controlled ONLY by Midtrans webhook)
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'expired'])
                  ->default('pending')
                  ->after('order_number');
            
            // Add order_status column (controlled by admin for fulfillment)
            $table->enum('order_status', ['new', 'processing', 'shipped', 'completed', 'refunded'])
                  ->default('new')
                  ->after('payment_status');
        });

        // Migrate existing data
        // Map old status to new payment_status and order_status
        DB::table('orders')->get()->each(function ($order) {
            $paymentStatus = 'pending';
            $orderStatus = 'new';
            
            // Map old status values
            switch ($order->status) {
                case 'pending':
                    $paymentStatus = 'pending';
                    $orderStatus = 'new';
                    break;
                case 'paid':
                    $paymentStatus = 'paid';
                    $orderStatus = 'new';
                    break;
                case 'shipped':
                    $paymentStatus = 'paid'; // Assume paid if shipped
                    $orderStatus = 'shipped';
                    break;
                case 'completed':
                    $paymentStatus = 'paid'; // Assume paid if completed
                    $orderStatus = 'completed';
                    break;
                case 'cancelled':
                    $paymentStatus = 'failed'; // Treat cancelled as failed payment
                    $orderStatus = 'new';
                    break;
            }
            
            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'payment_status' => $paymentStatus,
                    'order_status' => $orderStatus,
                ]);
        });

        // Drop old status column after migration
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Re-add old status column
            $table->enum('status', ['pending', 'paid', 'shipped', 'completed', 'cancelled'])
                  ->default('pending')
                  ->after('order_number');
        });

        // Migrate data back (use payment_status as primary)
        DB::table('orders')->get()->each(function ($order) {
            $status = 'pending';
            
            // Prioritize payment_status, but consider order_status for fulfillment states
            if ($order->payment_status === 'paid') {
                switch ($order->order_status) {
                    case 'shipped':
                        $status = 'shipped';
                        break;
                    case 'completed':
                        $status = 'completed';
                        break;
                    default:
                        $status = 'paid';
                        break;
                }
            } elseif ($order->payment_status === 'failed' || $order->payment_status === 'expired') {
                $status = 'cancelled';
            }
            
            DB::table('orders')
                ->where('id', $order->id)
                ->update(['status' => $status]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'order_status']);
        });
    }
};

