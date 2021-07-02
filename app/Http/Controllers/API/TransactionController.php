<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');

        if ($id) {
            $transaction = Transaction::with(['food', 'user'])
                ->find($id);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Transactions Data has been fatched'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Nothing found',
                    404
                );
            }
        }

        $transaction = Transaction::with(['food', 'user'])
            ->where('user_id', Auth::user()->id);

        if ($food_id) $transaction->where('food_id', $food_id);
        if ($status) $transaction->where('status', $status);

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Transaction data has been fetched successfully'
        );
    }

    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $transaction->update($request->all());

        return ResponseFormatter::success($transaction, 'Transaksi berhasil diperbarui');
    }

    public function checkout(Request $request)
    {
        // Validate the order by food_id, user_id, qty, and total amount
        $request->validate([
            'food_id' => 'required|exists:food,id',
            'user_id' => 'required|exists:user,id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required'
        ]);

        // Create transactions
        $transaction = Transaction::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status,
            'payment_url' => ''
        ]);

        // Midtrans Configuration
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.$is3ds');

        // Call the transaction
        $transaction = Transaction::with(['food', 'user'])->find($transaction->id);

        // create object for midtrans transaction
        $midtrans = array(
            'transaction_detail' => array(
                'order_id' => $transaction->id,
                'gross_amount' => (int) $transaction->total,
            ),
            'customer_detail' => array(
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ),
            'enabled_payments' => array('gopay', 'bank_transfer'),
            'vtweb' => array()
        );

        // Call object Midtrans
        try {
            // Get payment page
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;
            // Set field payment_url in transaction table
            $transaction->payment_url = $paymentUrl;
            $transaction->save();
            // Redirect to midtrans page
            return ResponseFormatter::success($transaction, 'Transaction Success');
        } catch (Exception  $e) {
            return ResponseFormatter::error([$e->getMessage(), 'Transaction Failed']);
        }
    }
}
