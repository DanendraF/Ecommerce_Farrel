<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Gloudemans\Shoppingcart\Facades\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct()
    {
        // Pastikan user terautentikasi untuk akses metode tertentu
        $this->middleware('auth')->except(['index', 'addToCart']);
    }

    public function index()
    {
        $cartItems = Cart::instance('cart')->content();
        return view('cart', ['cartItems' => $cartItems]);
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::find($request->id);
        $price = $product->sale_price ? $product->sale_price : $product->regular_price;

        Cart::instance('cart')->add($product->id, $product->name, $request->quantity, $price)
            ->associate(Product::class);

        return redirect()->back()->with('message', 'Success! Item has been added successfully.');
    }

    public function updateCart(Request $request)
    {
        $request->validate([
            'rowId' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        Cart::instance('cart')->update($request->rowId, $request->quantity);
        return redirect()->route('cart.index');
    }

    public function removeCart(Request $request)
    {
        $request->validate([
            'rowId' => 'required',
        ]);

        Cart::instance('cart')->remove($request->rowId);
        return redirect()->route('cart.index');
    }

    public function clearCart()
    {
        Cart::instance('cart')->destroy();
        return redirect()->route('cart.index');
    }

    public function createOrder()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to place an order.');
        }

        $cartItems = Cart::instance('cart')->content();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $order = new Order();
        $order->user_id = auth()->id();
        $order->subtotal = (float) str_replace(',', '', Cart::subtotal());
        $order->tax = (float) str_replace(',', '', Cart::tax());
        $order->total = (float) str_replace(',', '', Cart::total());
        $order->name = auth()->user()->name;
        $order->save();

        foreach ($cartItems as $item) {
            $order->items()->create([
                'product_id' => $item->id,
                'price' => $item->price,
                'quantity' => $item->qty,
            ]);
        }

        Cart::instance('cart')->destroy();

        return redirect()->route('checkout', ['orderId' => $order->id]);
    }

    public function showCheckout($orderId)
    {
        $order = Order::with('items.product')->findOrFail($orderId);
        return view('checkout', compact('order'));
    }
}
