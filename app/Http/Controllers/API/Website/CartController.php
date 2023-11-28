<?php

namespace App\Http\Controllers\API\Website;


use App\Models\Cart;
use App\Models\User;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\CartProduct;
use Illuminate\Http\Request;
use App\Models\ShippingAddress;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    //////////////////////////////////////////////Add To Cart///////////////////////////////////////////////
    public function addToCart(Request $request)
    {

        $userId = getTokenUserId($request->header('Authorization'));
        //If he is a User
        if ($userId) {

            $user = User::where('id', $userId)->first();

            $points = $user->points;

            $request->session()->put('session_expiry_time', now()->addMinutes(20)->timestamp);
            // Check if the user already has an active cart or create a new one
            $cart = Cart::where('user_id', $userId)->first();
            //Build new Cart if he don't have
            if (!$cart) {
                $cart = new Cart();
                $cart->user_id = $userId;
                // Initialize other cart fields
                $cart->subtotal = 0;
                $cart->shipping_fees = 0;
                $cart->tax = 0;
                $cart->promocode_price = 0;
                $cart->points_price = 0;
                $cart->total_price = 0;
                $cart->save();
            }

            // Get the product and quantity to add to the cart
            $productId = $request->input('product_id');
            $quantity = $request->input('quantity');

            // Fetch the product from the database
            $product = Product::find($productId);

            if (!$product) {
                return response()->json(['error' => 'Product not found.'], 404);
            }

            // Calculate the total quantity of this product in the cart
            if (!$totalProductQuantityInCart = $cart->cartProducts()
                ->where('product_id', $productId)) {
                $totalProductQuantityInCart = 0;
            } else {
                $totalProductQuantityInCart = $cart->cartProducts()
                    ->where('product_id', $productId)
                    ->value('quantity');
            }

            // Calculate the new total quantity after adding the product
            $newTotalQuantity = 0;
            $newTotalQuantity = $totalProductQuantityInCart + $quantity;
            //return  response()->json([$newTotalQuantity], 200);
            // Check if the new total quantity exceeds the product's available quantity
            if ($newTotalQuantity > $product->quantity) {
                return response()->json(['error' => 'Insufficient product quantity in stock.'], 400);
            } else {
                // Check if the product is already in the cart
                $existingProduct = CartProduct::where('product_id', $productId)->where('cart_id', $cart->id)->first();

                if ($existingProduct) {
                    // Update the quantity in the existing cart product
                    //$this->editCartProduct($cart->id, $productId, $quantity);
                    CartProduct::where('cart_id', $cart->id)
                        ->where('product_id', $productId)
                        ->update(['quantity' => DB::raw('quantity + ' . $quantity)]);
                } else {
                    // Add the product to the cart
                    $cartProduct = new CartProduct();
                    $cartProduct->cart_id = $cart->id;
                    $cartProduct->product_id = $productId;
                    $cartProduct->quantity = $quantity;
                    $cartProduct->save();
                }

                if ($cart->points != null) {


                    $user->points = $user->points + $cart->points;
                    $user->save();

                    $cart->points = null;
                    $cart->points_price = 0;
                    $cart->save();

                    $singleProduct = Product::where('id', $productId)->first();
                    $cart->subtotal = $cart->subtotal
                        + ($singleProduct->price * $quantity);

                    $cart->save();


                    $points = $user->points;
                    $subtotal = $cart->subtotal;
                    $eqpoints = $points / 20; //flooos
                    $eqprice = $subtotal - $eqpoints;

                    // // Update both promocode and subtotal in a single query
                    //$cart->promocode =  $promocode->id;

                    if ($eqprice <= 0) {
                        //$cart->subtotal = 0;
                        $eqprice *= -1;
                        $user->points = floor($eqprice * 20);
                        $user->save();
                        $cart->points = $points - $user->points;
                        $cart->points_price = ($points - $user->points) / 20;
                        $cart->save();
                    } else {
                        $cart->points = $user->points;
                        $cart->points_price = $eqpoints;
                        $user->points = 0;
                        $cart->save();
                        $user->save();
                    }

                    $cart->total_price = $this->checkTotal($cart->id);
                    $cart->save();


                    return response()->json(['message' => 'Product added to cart. Points'], 200);
                }

                if ($cart->promocode_id != null) {

                    $promocodeId = $cart->promocode_id;
                    $promocode = PromoCode::where('id', $promocodeId)->first();


                    $cart->promocode_price = 0;

                    $cart->promocode_id = null;

                    // // Retrieve the updated subtotal again
                    $cart->save();

                    // Update the cart's subtotal
                    $cart->subtotal += $product->price * $quantity;
                    $cart->save();


                    $subtotal = $cart->subtotal;
                    $cart->promocode_id =  $promocode->id;
                    $cart->promocode_price = min(($subtotal * ($promocode->value / 100)), $promocode->max_amount);
                    // // Retrieve the updated subtotal again
                    $cart->save();

                    $cart->total_price = $this->checkTotal($cart->id);
                    $cart->save();


                    return response()->json(['message' => 'Product added to cart. Promo'], 200);
                } else {
                    // Update the cart's subtotal
                    $cart->subtotal += $product->price * $quantity;
                    $cart->save();

                    $cart->total_price = $this->checkTotal($cart->id);
                    $cart->save();

                    return response()->json(['message' => 'Product added to cart.'], 200);
                }
            }


            // ... (rest of your code for authenticated users)
        } else {
            // Handle guest users

            $guestId = session('guest_id');

            if (!$guestId) {
                // Generate a random number between 100000 and 999999
                $randomNumber = mt_rand(100000, 999999);

                // Create a guest ID by appending the random number
                $guestId = 'guest_' . $randomNumber;

                // Check if the ID already exists in the database
                $exists = Cart::where('guest_id', $guestId)->exists();

                while ($exists) {
                    // Regenerate until a unique ID is found
                    $randomNumber = mt_rand(
                        100000,
                        999999
                    );
                    $guestId = 'guest_' . $randomNumber;
                    $exists = Cart::where('guest_id', $guestId)->exists();
                }

                // Store it in the session
                session(['guest_id' => $guestId]);
            }

            $cart = Cart::where('guest_id', $guestId)->where('user_id', null)->first();

            if (!$cart) {
                $cart = new Cart();
                $cart->guest_id = $guestId;
                // Initialize other cart fields
                $cart->subtotal = 0;
                $cart->shipping_fees = 0;
                $cart->tax = 0;
                $cart->promocode_price = 0;
                $cart->points_price = 0;
                $cart->total_price = 0;
                $cart->save();
            }

            // Get the product and quantity to add to the cart
            $productId = $request->input('product_id');
            $quantity = $request->input('quantity');

            $product = Product::find($productId);

            if (!$product) {
                return response()->json(['error' => 'Product not found.'], 404);
            }

            // Calculate the total quantity of this product in the cart
            if (!$totalProductQuantityInCart = $cart->cartProducts()
                ->where('product_id', $productId)) {
                $totalProductQuantityInCart = 0;
            } else {
                $totalProductQuantityInCart = $cart->cartProducts()
                    ->where('product_id', $productId)
                    ->value('quantity');
            }

            // Calculate the new total quantity after adding the product
            $newTotalQuantity = 0;
            $newTotalQuantity = $totalProductQuantityInCart + $quantity;
            //return  response()->json([$newTotalQuantity], 200);
            // Check if the new total quantity exceeds the product's available quantity
            if ($newTotalQuantity > $product->quantity) {
                return response()->json(['error' => 'Insufficient product quantity in stock.'], 400);
            } else {
                // Check if the product is already in the cart
                $existingProduct = CartProduct::where('product_id', $productId)->where('cart_id', $cart->id)->first();

                if ($existingProduct) {
                    // Update the quantity in the existing cart product
                    //$this->editCartProduct($cart->id, $productId, $quantity);
                    CartProduct::where('cart_id', $cart->id)
                        ->where('product_id', $productId)
                        ->update(['quantity' => DB::raw('quantity + ' . $quantity)]);
                } else {
                    // Add the product to the cart
                    $cartProduct = new CartProduct();
                    $cartProduct->cart_id = $cart->id;
                    $cartProduct->product_id = $productId;
                    $cartProduct->quantity = $quantity;
                    $cartProduct->save();
                }
                if ($cart->promocode_id != null) {

                    $promocodeId = $cart->promocode_id;
                    $promocode = PromoCode::where('id', $promocodeId)->first();


                    $cart->promocode_price = 0;

                    $cart->promocode_id = null;

                    // // Retrieve the updated subtotal again
                    $cart->save();

                    // Update the cart's subtotal
                    $cart->subtotal += $product->price * $quantity;
                    $cart->save();


                    $subtotal = $cart->subtotal;
                    $cart->promocode_id =  $promocode->id;
                    $cart->promocode_price = min(($subtotal * ($promocode->value / 100)), $promocode->max_amount);
                    // // Retrieve the updated subtotal again
                    $cart->save();

                    $cart->total_price = $this->checkTotal($cart->id);
                    $cart->save();



                    return response()->json(['message' => 'Product added to cart. Guest Promo'], 200);
                } else {
                    $cart->subtotal += $product->price * $quantity;
                    $cart->save();

                    $cart->total_price = $this->checkTotal($cart->id);
                    $cart->save();

                    return response()->json(['message' => 'Product added to cart. Guest'], 200);
                }
            }
        }
    }
    //////////////////////////////////////////////View Cart///////////////////////////////////////////////
    public function viewCart(Request $request)
    {
        //$user = auth()->user();
        $userId = getTokenUserId($request->header('Authorization'));

        if (!$userId) {
            $userId = session('guest_id');
            $cart = Cart::where('guest_id', $userId)->where('user_id', null)->first();
        } else {
            $cart = Cart::where('user_id', $userId)->first();
        }
        if ($cart) {
            $cartProducts = CartProduct::where('cart_id', $cart->id)->get();

            $cartData = [];
            $total = 0;

            foreach ($cartProducts as $cartProduct) {
                $product = $cartProduct->product;

                $productTotal = $product->price * $cartProduct->quantity;
                // Add product details to the response
                $cartData[] = [
                    'Product Id' => $product->id,
                    'Name EN' => $product->name_en,
                    'Name AR' => $product->name_ar,
                    'Description EN' => $product->description_en,
                    'Description AR' => $product->description_ar,
                    'Quantity' => $cartProduct->quantity,
                    'Price' => $product->price,
                    'Product_total' => $productTotal,
                ];
            }


            $cart->total_price = $this->checkTotal($cart->id);
            $cart->save();

            // Include the total price in the response
            $response = [
                'Products' => $cartData,
                'Subtotal' => $cart->subtotal,
                'Shipping Fees' => $cart->shipping_fees ?? 0,
                'Tax' => $cart->$cart->tax ?? 0,
                'Discounted Promocode Price' => $cart->promocode_price ?? 0,
                'Discounted Points Price' => $cart->points_price ?? 0,
                'Total Price' => $cart->total_price,
            ];

            return response()->json($response);
        } else {
            return response()->json(['message' => 'Cart is empty'], 404);
        }
    }
    /////////////////////////////////////////////Update Cart////////////////////////////////////////////////
    public function editCart(Request $request)
    {
        // Get the authenticated user's ID
        $userId = getTokenUserId($request->header('Authorization'));

        if (!$userId) {
            $userId = session('guest_id');
            $cart = Cart::where('guest_id', $userId)->where('user_id', null)->first();
            //$user = User::where('id', $userId)->first();
        } else {
            $user = User::where('id', $userId)->first();
            $points = $user->points;
            $cart = Cart::where('user_id', $userId)->first();
        }
        // Retrieve the product ID and quantity from the query parameters
        $productId = $request->input('product_id');
        $newQuantity = (int)$request->input('quantities');
        // Fetch the product from the database
        $product = Product::find($productId);

        if (!$cart) {
            // If the user doesn't have a cart, you can handle it here (create a new cart, etc.)
            return response()->json(['error' => 'User does not have a cart.'], 404);
        }
        // Find the specific cart product
        $cartProduct = CartProduct::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if ($cartProduct) {

            $oldQuantity = $cartProduct->quantity;


            if ($newQuantity > $product->quantity) {
                return response()->json(['error' => 'Insufficient product quantity in stock.'], 400);
            } else if ($newQuantity <= 0) {

                if ($cart->promocode_id != null) {
                    $promocodeId = $cart->promocode_id;
                    $promocode = PromoCode::where('id', $promocodeId)->first();


                    $cart->promocode_price = 0;

                    $cart->promocode_id = null;

                    // // Retrieve the updated subtotal again
                    $cart->save();

                    CartProduct::where('cart_id', $cart->id)->where('product_id', $productId)->delete();


                    $cart->subtotal = $cart->subtotal - $product->price * $oldQuantity;
                    $cart->save();
                    $cartProductsCount = CartProduct::where('cart_id', $cart->id)->count();

                    if ($cartProductsCount === 0) {
                        $cart->delete();
                    } else {

                        $subtotal = $cart->subtotal;
                        $cart->promocode_id =  $promocode->id;
                        $cart->promocode_price = min(($subtotal * ($promocode->value / 100)), $promocode->max_amount);
                        // // Retrieve the updated subtotal again
                        $cart->save();

                        $cart->total_price = $this->checkTotal($cart->id);
                        $cart->save();
                    }
                    return response()->json(['message' => 'Product is removed from Cart'], 200);
                }
                if ($cart->points != null) {
                    $user->points = $user->points + $cart->points;
                    $user->save();

                    $cart->points = null;
                    $cart->points_price = 0;
                    $cart->save();

                    CartProduct::where('cart_id', $cart->id)->where('product_id', $productId)->delete();
                    //$cart->update(['subtotal' => DB::raw('subtotal - ' . $product->price * $oldQuantity)]);
                    $cart->subtotal = $cart->subtotal - $product->price * $oldQuantity;
                    $cart->save();
                    $cartProductsCount = CartProduct::where('cart_id', $cart->id)->count();

                    if ($cartProductsCount == 0) {
                        $cart->delete();
                    } else {
                        $points = $user->points;
                        $subtotal = $cart->subtotal;
                        $eqpoints = $points / 20; //flooos
                        $eqprice = $subtotal - $eqpoints;


                        if ($eqprice <= 0) {
                            //$cart->subtotal = 0;
                            $eqprice *= -1;
                            $user->points = floor($eqprice * 20);
                            $user->save();
                            $cart->points = $points - $user->points;
                            $cart->points_price = ($points - $user->points) / 20;
                            $cart->save();
                        } else {
                            $cart->points = $user->points;
                            $cart->points_price = $eqpoints;
                            $user->points = 0;
                            $cart->save();
                            $user->save();
                        }

                        $cart->total_price = $this->checkTotal($cart->id);
                        $cart->save();
                    }
                    return response()->json(['message' => 'Product is removed from Cart'], 200);
                }
                // Delete associated cart products
                CartProduct::where('cart_id', $cart->id)->where('product_id', $productId)->delete();
                $cart->update(['subtotal' => DB::raw('subtotal - ' . $product->price * $oldQuantity)]);
                $cartProductsCount = CartProduct::where('cart_id', $cart->id)->count();

                if ($cartProductsCount === 0) {
                    $cart->delete();
                }
                return response()->json(['message' => 'Product is removed from Cart'], 200);
            } else {
                // Update the product quantity
                if ($cart->promocode_id != null) {
                    $promocodeId = $cart->promocode_id;
                    $promocode = PromoCode::where('id', $promocodeId)->first();


                    $cart->promocode_price = 0;

                    $cart->promocode_id = null;

                    // // Retrieve the updated subtotal again
                    $cart->save();

                    $cartProduct = CartProduct::where('cart_id', $cart->id)
                        ->where('product_id', $productId)
                        ->update(['quantity' => DB::raw($newQuantity)]);


                    //return response()->json(['message' => $oldQuantity, $newQuantity],400);
                    if ($oldQuantity < $newQuantity) {
                        // $cart->update(['subtotal' => DB::raw('subtotal + ' . $product->price * ($newQuantity - $oldQuantity))]);
                        $cart->subtotal = $cart->subtotal + ($product->price * ($newQuantity - $oldQuantity));
                        $cart->save();
                    }
                    if ($oldQuantity > $newQuantity) {
                        //$cart->update(['subtotal' => DB::raw('subtotal - ' . $product->price * ($oldQuantity - $newQuantity))]);
                        $cart->subtotal = $cart->subtotal - ($product->price * ($oldQuantity - $newQuantity));
                        $cart->save();
                        //return response()->json(['message' => $cart], 400);
                    }
                    $subtotal = $cart->subtotal;
                    $cart->promocode_id =  $promocode->id;
                    $cart->promocode_price = min(($subtotal * ($promocode->value / 100)), $promocode->max_amount);
                    // // Retrieve the updated subtotal again
                    $cart->save();
                } else if ($cart->points != null) {
                    $user->points = $user->points + $cart->points;
                    $user->save();

                    $cart->points = null;
                    $cart->points_price = 0;
                    $cart->save();


                    CartProduct::where('cart_id', $cart->id)
                        ->where('product_id', $productId)
                        ->update(['quantity' => DB::raw($newQuantity)]);


                    //return response()->json(['message' => $oldQuantity, $newQuantity],400);
                    if ($oldQuantity < $newQuantity) {
                        // $cart->update(['subtotal' => DB::raw('subtotal + ' . $product->price * ($newQuantity - $oldQuantity))]);
                        $cart->subtotal = $cart->subtotal + ($product->price * ($newQuantity - $oldQuantity));
                        $cart->save();
                    }
                    if ($oldQuantity > $newQuantity) {
                        //$cart->update(['subtotal' => DB::raw('subtotal - ' . $product->price * ($oldQuantity - $newQuantity))]);
                        $cart->subtotal = $cart->subtotal - ($product->price * ($oldQuantity - $newQuantity));
                        $cart->save();
                        //return response()->json(['message' => $cart], 400);
                    }
                    $points = $user->points;
                    $subtotal = $cart->subtotal;
                    $eqpoints = $points / 20; //flooos
                    $eqprice = $subtotal - $eqpoints;


                    if ($eqprice <= 0) {
                        //$cart->subtotal = 0;
                        $eqprice *= -1;
                        $user->points = floor($eqprice * 20);
                        $user->save();
                        $cart->points = $points - $user->points;
                        $cart->points_price = ($points - $user->points) / 20;
                        $cart->save();
                    } else {
                        $cart->points = $user->points;
                        $cart->points_price = $eqpoints;
                        $user->points = 0;
                        $cart->save();
                        $user->save();
                    }
                } else {

                    CartProduct::where('cart_id', $cart->id)
                        ->where('product_id', $productId)
                        ->update(['quantity' => DB::raw($newQuantity)]);

                    if ($oldQuantity < $newQuantity) {
                        // $cart->update(['subtotal' => DB::raw('subtotal + ' . $product->price * ($newQuantity - $oldQuantity))]);
                        $cart->subtotal = $cart->subtotal + ($product->price * ($newQuantity - $oldQuantity));
                        $cart->save();
                    }
                    if ($oldQuantity > $newQuantity) {
                        //$cart->update(['subtotal' => DB::raw('subtotal - ' . $product->price * ($oldQuantity - $newQuantity))]);
                        $cart->subtotal = $cart->subtotal - ($product->price * ($oldQuantity - $newQuantity));
                        $cart->save();
                    }
                    // You can recalculate the cart's subtotal, Total_price, and other fields if needed
                }

                $cart->total_price = $this->checkTotal($cart->id);
                $cart->save();
                return response()->json(['message' => 'Cart updated successfully.'], 200);
            }
        } else {
            return response()->json(['error' => 'Product not found in your cart.'], 400);
        }
    }
    ////////////////////////////////////////////Remove product From Cart////////////////////////////////////
    public function removeProductFromCart(Request $request)
    {
        $userId = getTokenUserId($request->header('Authorization'));

        if (!$userId) {
            $userId = session('guest_id');
            $cart = Cart::where('guest_id', $userId)->where('user_id', null)->first();
            //$user = User::where('id', $userId)->first();
        } else {
            $user = User::where('id', $userId)->first();
            $points = $user->points;
            $cart = Cart::where('user_id', $userId)->first();
        }

        $productId = $request->input('product_id');

        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['error' => 'There is no product with this ID'], 404);
        }

        // Find the user's cart
        if (!$cart) {
            return response()->json(['error' => 'You does not have a cart.'], 404);
        }

        // Find the specific cart product
        $cartProduct = CartProduct::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if (!$cartProduct) {
            return response()->json(['error' => 'This product is not in the Cart.'], 404);
        }

        $oldQuantity = $cartProduct->quantity;

        if ($cart->promocode_id != null) {
            $promocodeId = $cart->promocode_id;
            $promocode = PromoCode::where('id', $promocodeId)->first();


            $cart->promocode_price = 0;

            $cart->promocode_id = null;

            // // Retrieve the updated subtotal again
            $cart->save();

            CartProduct::where('cart_id', $cart->id)->where('product_id', $productId)->delete();


            $cart->subtotal = $cart->subtotal - $product->price * $oldQuantity;
            $cart->save();
            $cartProductsCount = CartProduct::where('cart_id', $cart->id)->count();

            if ($cartProductsCount === 0) {
                $cart->delete();
            } else {

                $subtotal = $cart->subtotal;
                $cart->promocode_id =  $promocode->id;
                $cart->promocode_price = min(($subtotal * ($promocode->value / 100)), $promocode->max_amount);
                // // Retrieve the updated subtotal again
                $cart->save();

                $cart->total_price = $this->checkTotal($cart->id);
                $cart->save();
            }
            return response()->json(['message' => 'Product is removed from Cart'], 200);
        }
        if ($cart->points != null) {
            $user->points = $user->points + $cart->points;
            $user->save();

            $cart->points = null;
            $cart->points_price = 0;
            $cart->save();

            CartProduct::where('cart_id', $cart->id)->where('product_id', $productId)->delete();
            //$cart->update(['subtotal' => DB::raw('subtotal - ' . $product->price * $oldQuantity)]);
            $cart->subtotal = $cart->subtotal - $product->price * $oldQuantity;
            $cart->save();
            $cartProductsCount = CartProduct::where('cart_id', $cart->id)->count();

            if ($cartProductsCount == 0) {
                $cart->delete();
            } else {
                $points = $user->points;
                $subtotal = $cart->subtotal;
                $eqpoints = $points / 20; //flooos
                $eqprice = $subtotal - $eqpoints;


                if ($eqprice <= 0) {
                    //$cart->subtotal = 0;
                    $eqprice *= -1;
                    $user->points = floor($eqprice * 20);
                    $user->save();
                    $cart->points = $points - $user->points;
                    $cart->points_price = ($points - $user->points) / 20;
                    $cart->save();
                } else {
                    $cart->points = $user->points;
                    $cart->points_price = $eqpoints;
                    $user->points = 0;
                    $cart->save();
                    $user->save();
                }

                $cart->total_price = $this->checkTotal($cart->id);
                $cart->save();
            }
            return response()->json(['message' => 'Product is removed from Cart'], 200);
        }
        // Delete associated cart products
        CartProduct::where('cart_id', $cart->id)->where('product_id', $productId)->delete();
        $cart->update(['subtotal' => DB::raw('subtotal - ' . $product->price * $oldQuantity)]);
        $cartProductsCount = CartProduct::where('cart_id', $cart->id)->count();

        if ($cartProductsCount === 0) {
            $cart->delete();
        } else {
            $cart->total_price = $this->checkTotal($cart->id);
            $cart->save();
        }
        return response()->json(['message' => 'Product is removed from Cart'], 200);
    }
    ////////////////////////////////////////////Apply Promocode//////////////////////////////////////////////////////////////
    public function applyPromoCode(Request $request)
    {
        $userId = getTokenUserId($request->header('Authorization'));


        if (!$userId) {
            $userId = session('guest_id');

            $cart = Cart::where('guest_id', $userId)->where('user_id', null)->first();
        } else {
            $cart = Cart::where('user_id', $userId)->first();
        }
        $code = $request->input('promocode');

        $promocode = PromoCode::where('code', $code)->first();



        if (!$cart) {
            return response()->json(['error' => 'There is no cart to add the promocode'], 404);
        }
        if (!$promocode) {
            return response()->json(['error' => 'This Promo is Not Valid'], 404);
        }

        if ($promocode->is_working == 0) {
            return response()->json(['error' => 'This Promo is expired'], 404);
        }
        if ($promocode->max_number_of_used <= 0) {
            return response()->json(['error' => 'This Promo is reached max of usuage'], 404);
        }
        if ($cart->promocode_id != null) {
            return response()->json(['error' => 'Cannot use promocode while another promocode is working'], 404);
        }
        if ($cart->points != null) {
            return response()->json(['error' => 'Cannot use promocode while using pointing system'], 404);
        }

        $subtotal = $cart->subtotal;


        // // Update both promocode and subtotal in a single query
        $cart->promocode_id =  $promocode->id;
        // $cart->subtotal =
        //     $subtotal - ($subtotal * ($promocode->value / 100));

        $cart->promocode_price = min(($subtotal * ($promocode->value / 100)), $promocode->max_amount);

        // // Retrieve the updated subtotal again
        $cart->save();

        $cart->total_price = $this->checkTotal($cart->id);
        $cart->save();

        return response()->json([
            'message' => 'Promocode added Successfully ',
            'The Discount' => $cart->promocode_price,
        ], 200);
    }
    //////////////////////////////////////////////Remove Promocode///////////////////////////////////////////////////////////////
    public function removePromoCode(Request $request)
    {
        $userId = getTokenUserId($request->header('Authorization'));

        if (!$userId) {
            $userId = session('guest_id');
            $cart = Cart::where('guest_id', $userId)->where('user_id', null)->first();
            // session(['guest_id' => $userId]);
        } else {
            $cart = Cart::where('user_id', $userId)->first();
        }



        if (!$cart) {
            return response()->json(['error' => 'There is no cart to remove the promocode'], 404);
        }
        $promocodeId = $cart->promocode_id;

        if ($promocodeId == null) {
            return response()->json(['error' => 'There is no Promocode to remove it'], 404);
        }

        // // Update both promocode and subtotal in a single query

        $cart->promocode_price = 0;

        $cart->promocode_id = null;

        // // Retrieve the updated subtotal again
        $cart->save();

        $cart->total_price = $this->checkTotal($cart->id);
        $cart->save();

        return response()->json([
            'message' => 'Promocode removed Successfully ',
            'The Total Price is' => $cart->total_price,
        ], 200);
    }
    //////////////////////////////////////////////Apply Points////////////////////////////////////////////
    public function applyPoints(Request $request)
    {
        $userId = getTokenUserId($request->header('Authorization'));
        //$code = $request->input('promocode');

        if (!$userId) {
            return response()->json(['error' => 'You are not a User To apply points'], 404);
        }
        $user = User::where('id', $userId)->first();

        $points = $user->points;

        $cart = Cart::where('user_id', $userId)->first();


        if (!$cart) {
            return response()->json(['error' => 'There is no cart to add the points'], 404);
        }
        if ($cart->promocode_id != null) {
            return response()->json(['error' => 'Cannot use points while using promocodes'], 404);
        }
        if ($cart->points != null) {
            return response()->json(['error' => 'You already using your points'], 404);
        }


        if ($points == 0) {
            return response()->json(['error' => 'You Dont have points yet'], 404);
        }

        $points = $user->points;
        $subtotal = $cart->subtotal;
        $eqpoints = $points / 20; //flooos
        $eqprice = $subtotal - $eqpoints;

        // // Update both promocode and subtotal in a single query
        //$cart->promocode =  $promocode->id;

        if ($eqprice <= 0) {
            //$cart->subtotal = 0;
            $eqprice *= -1;
            $user->points = floor($eqprice * 20);
            $user->save();
            $cart->points = $points - $user->points;
            $cart->points_price = $cart->subtotal;
            $cart->save();
        } else {
            $cart->points = $user->points;
            $cart->points_price = $eqpoints;
            $user->points = 0;
            $cart->save();
            $user->save();
        }

        $cart->total_price = $this->checkTotal($cart->id);
        $cart->save();

        return response()->json([
            'message' => 'Points added Successfully ',
            'The Total Price is' => $cart->total_price,
        ], 200);
    }
    ////////////////////////////////////////////////Remove Points////////////////////////////////////////////////
    public function removePoints(Request $request)
    {
        $userId = getTokenUserId($request->header('Authorization'));

        if (!$userId) {
            return response()->json(['error' => 'You are not loged in to remove your points'], 404);
        }

        $cart = Cart::where('user_id', $userId)->first();

        $user = User::where('id', $userId)->first();


        if (!$cart) {
            return response()->json(['error' => 'There is no cart to remove the Points'], 404);
        }
        $pointsCart = $cart->points;

        if ($pointsCart == 0) {
            return response()->json(['error' => 'There is no Points to remove it or you dont have Points'], 404);
        }

        $user->points = $user->points + $cart->points;
        $user->save();

        $cart->points = null;
        $cart->points_price = 0;
        $cart->save();


        $cart->total_price = $this->checkTotal($cart->id);
        $cart->save();


        return response()->json([
            'message' => 'Points removed Successfully ',
            'The Subtotal is' => $cart->subtotal,
        ], 200);
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function addShipmentInfo(Request $request)
    {
        $userId = getTokenUserId($request->header('Authorization'));

        if (!$userId) {
            $userId = session('guest_id');

            $cart = Cart::where('guest_id', $userId)->where('user_id', null)->first();

            if (!$cart) {
                return response()->json(['error' => 'You dont have cart yet to fill out shipmet info.'], 404);
            } else {
                $shipping_address = new ShippingAddress();
                $shipping_address->default_user_flag = 0;
                $shipping_address->first_name = $request->input('first_name');
                $shipping_address->last_name = $request->input('last_name');
                $shipping_address->address = $request->input('address');
                $shipping_address->appartment = $request->input('appartment');
                $shipping_address->district = $request->input('district');
                $shipping_address->region = $request->input('region');
                $shipping_address->phone = $request->input('phone');
                $shipping_address->save();

                $cart->shipping_address_id = $shipping_address->id;
                $cart->total_price = $this->checkTotal($cart->id);
                $cart->save();


                return response()->json(['message' => 'Shipment info is added Successfully as a Guest'], 200);
            }
        } else {
            $cart = Cart::where('user_id', $userId)->first();

            if (!$cart) {
                return response()->json(['error' => 'You dont have cart yet to fill out shipmet info.'], 404);
            }
            $shippmentId = $request->input('id');
            if (!$shippmentId) {
                $shipping_address = new ShippingAddress();
                $shipping_address->user_id = $userId;
                $shipping_address->default_user_flag = 0;
                $shipping_address->first_name = $request->input('first_name');
                $shipping_address->last_name = $request->input('last_name');
                $shipping_address->address = $request->input('address');
                $shipping_address->appartment = $request->input('appartment');
                $shipping_address->district = $request->input('district');
                $shipping_address->region = $request->input('region');
                $shipping_address->phone = $request->input('phone');
                $shipping_address->save();

                $cart->shipping_address_id = $shipping_address->id;
                $cart->total_price = $this->checkTotal($cart->id);
                $cart->save();

                return response()->json(['message' => 'Shipment info is added Successfully as a User'], 200);
            } else {
                $cart->shipping_address_id = $shippmentId;
                $cart->total_price = $this->checkTotal($cart->id);
                $cart->save();

                return response()->json(['message' => 'Shipment info is added Successfully as a previous add User'], 200);
            }
        }
    }
    public function viewAllPreviousAdd(Request $request)
    {
        $userId = getTokenUserId($request->header('Authorization'));

        if (!$userId) {
            return response()->json(['error' => 'You are not a user to view your previous addresses.'], 404);
        }
        $prevAdresses = ShippingAddress::where('user_id', $userId)->get();

        if (!$prevAdresses) {
            return response()->json(['error' => 'You dont have any previous addresses here '], 404);
        } else {
            return response()->json(['message' => $prevAdresses], 200);
        }
    }

    private function checkTotal($cartId)
    {
        $cart = Cart::where('id', $cartId)->first();
        $total_price = $cart->subtotal + $cart->shipping_fees + $cart->tax - $cart->promocode_price - $cart->points_price;
        if ($total_price < 0) {
            $total_price = 0;
        }
        return ($total_price);
    }
}
