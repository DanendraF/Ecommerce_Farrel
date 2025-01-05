<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Gloudemans\Shoppingcart\Facades\Cart;

class WishlistController extends Controller
{

    public function getWishlistProducts()
    {
        $items = Cart::instance("wishlist")->content();
        return view('wishlist',['items' => $items]);
    }
    public function addProductToWishlist(Request $request)
    {
        Cart::instance("wishlist")->add($request->id,$request->name,1,$request->price)->associate('App\Models\Product');
        return response()->json(['status' => 200, 'message' => 'Product added to wishlist successfully!']);
    }

    public function removeProductFromWishlist(Request $request)
    {
        $rowId = $request->rowId;
        Cart::instance("wishlist")->remove($rowId);
        return redirect()->route('wishlist.list');
    }

    public function clearWishlist()
    {
        Cart::instance("wishlist")->destroy();
        return redirect()->route('wishlist.list');
    }
}
