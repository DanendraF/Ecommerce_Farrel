<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Transaction;

class PaymentController extends Controller
{
    public function showCheckout($orderId)
    {
        $order = Order::with('items')->findOrFail($orderId);

        return view('checkout', [
            'order' => $order
        ]);
    }

    public function processPayment(Request $request, $orderId)
    {
        $request->validate([
            'payment_method' => 'required|in:cod,card,paypal',
        ]);

        $order = Order::findOrFail($orderId);

        // Simpan transaksi pembayaran
        $transaction = new Transaction();
        $transaction->user_id = $order->user_id;
        $transaction->order_id = $order->id;
        $transaction->mode = $request->payment_method;
        $transaction->status = 'approved'; // Dianggap berhasil untuk contoh sederhana
        $transaction->save();

        // Perbarui status pesanan
        $order->status = 'delivered';
        $order->save();

        return redirect()->route('checkout.success', $orderId)->with('success', 'Pembayaran berhasil!');
    }

    public function showSuccess($orderId)
    {
        $order = Order::findOrFail($orderId);

        return view('checkout-success', [
            'order' => $order
        ]);
    }
}
