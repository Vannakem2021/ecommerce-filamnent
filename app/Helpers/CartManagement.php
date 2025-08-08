<?php

namespace App\Helpers;
use App\Models\Product;
use Illuminate\Support\Facades\Cookie;

class CartManagement {

    // add item to cart

    static public function addItemToCart($product_id)
    {
        return self::addItemToCartWithQuantity($product_id, 1, 'product');
    }

    // add item to cart with specific quantity and type
    static public function addItemToCartWithQuantity($item_id, $quantity = 1, $type = 'product')
    {
        $cart_items = self::getCartItemsFromCookie();

        $existing_item = null;
        $item_key = $type . '_' . $item_id;

        foreach ($cart_items as $key => $item)
        {
            if ($item['item_key'] == $item_key) {
                $existing_item = $key;
                break;
            }
        }

        if ($existing_item !== null) {
            $cart_items[$existing_item]['quantity'] += $quantity;
            $cart_items[$existing_item]['total_amount'] = $cart_items[$existing_item]['quantity'] * $cart_items[$existing_item]['unit_amount'];
        } else {
            if ($type === 'variant') {
                $variant = \App\Models\ProductVariant::with('product')->where('id', $item_id)->first();
                if ($variant) {
                    $cart_items[] = [
                        'item_key' => $item_key,
                        'product_id' => $variant->product->id,
                        'variant_id' => $variant->id,
                        'name' => $variant->full_name,
                        'image' => $variant->images[0] ?? $variant->product->images[0] ?? null,
                        'quantity' => $quantity,
                        'unit_amount' => $variant->price,
                        'total_amount' => $variant->price * $quantity,
                        'type' => 'variant'
                    ];
                }
            } else {
                $product = Product::where('id', $item_id)->first(['id', 'name', 'price', 'images']);
                if ($product) {
                    $cart_items[] = [
                        'item_key' => $item_key,
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'name' => $product->name,
                        'image' => $product->images[0] ?? null,
                        'quantity' => $quantity,
                        'unit_amount' => $product->price,
                        'total_amount' => $product->price * $quantity,
                        'type' => 'product'
                    ];
                }
            }
        }

        self::addCartItemsToCookie($cart_items);
        return count($cart_items);
    }

    // remove item from cart

    static public function removeCartItems($item_key)
    {
        $cart_items = self::getCartItemsFromCookie();

        foreach ($cart_items as $key => $item)
        {
            if ($item['item_key'] == $item_key){
                unset($cart_items[$key]);
            }
        }

        self::addCartItemsToCookie($cart_items);

        return $cart_items;
    }

    // add cart item to cookie

    static public function addCartItemsToCookie($cart_items)
    {
        Cookie::queue('cart_items', json_encode($cart_items), 60*24*30);
    }

    // clear cart items from cookie

    static public function clearCartItems()
    {
        Cookie::queue(Cookie::forget('cart_items'));
    }

    // get all cart items from cookie

    static public function getCartItemsFromCookie()
    {
        $cart_items = json_decode(Cookie::get('cart_items'), true);
        if (!$cart_items) {
            $cart_items = [];
        }

        return $cart_items;
    }

    // increment item quantity

    static public function incrementQuantityToCartItem($product_id)
    {
        $cart_items = self::getCartItemsFromCookie();

        foreach ($cart_items as $key => $item) {

            if ($item['product_id'] == $product_id) {
                $cart_items[$key]['quantity']++;
                $cart_items[$key]['total_amount'] = $cart_items[$key]['quantity'] * $cart_items[$key]['unit_amount'];
            }

        }

        self::addCartItemsToCookie($cart_items);
        return $cart_items;
    }

    // decrement item quantity

    static public function decrementQuantityToCartItem($product_id)
    {
        $cart_items = self::getCartItemsFromCookie();

        foreach ($cart_items as $key => $item) {

            if ($item['product_id'] == $product_id) {
            
                if ($cart_items[$key]['quantity'] > 1) {

                    $cart_items[$key]['quantity']--;

                    $cart_items[$key]['total_amount'] = $cart_items[$key]['quantity'] * $cart_items[$key]['unit_amount'];

                }

            }

        }

        self::addCartItemsToCookie($cart_items);

        return $cart_items;

    }

    // calculate grandtotal

    static public function calculateGrandTotal($items)
    {
        return array_sum(array_column($items, 'total_amount'));
    }

}