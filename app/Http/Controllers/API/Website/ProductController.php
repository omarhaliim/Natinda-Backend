<?php

namespace App\Http\Controllers\API\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Product;
use App\Models\ProductType;

class ProductController extends Controller
{

    ////////////////////////////////////////Get All Products//////////////////////////////////////////////////////////////////
    public function getallproducts()
    {
        $products = Product::whereIn('status', [1, 2])->get();
        return response()->json($products, Response::HTTP_OK);
    }
    /////////////////////////////////////////Get Single Product////////////////////////////////////////////////////////////
    public function getProduct($id)
    {
        // Retrieve the product by its ID along with its type
        $product = Product::with('reviews', 'faqs')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $relatedProducts = Product::where('type_id','!=', $product->type_id)
         // Exclude the current product
            ->inRandomOrder()
            ->take(3)
            ->get();

        $numRelatedProducts = count($relatedProducts);

        // If there are less than 3 related products, continue with other types
        if ($numRelatedProducts < 3) {
            $remainingProducts = 3 - $numRelatedProducts;
            $additionalProducts = Product::where('type_id', $product->type_id)
                ->inRandomOrder()
                ->take($remainingProducts)
                ->get();

            // Merge related and different products
            $relatedProducts = $relatedProducts->concat($additionalProducts);
        }

        // Append the related products to the existing response structure
        $product->related_products = $relatedProducts;

        return response()->json(['product' => $product], 200);
    }
    /////////////////////////////////////////////Get Featured Products////////////////////////////////////////////////
    public function getFeaturedProducts()
    {
        // Retrieve all product types with their associated products
        $productTypes = ProductType::with('products')->get();

        return response()->json(['product_types' => $productTypes], 200);
    }
    /////////////////////////////////////////////////Filter Product /////////////////////////////////////////////////////////
    public function filterProducts(Request $request)
    {
        // Get the minimum and maximum price values from query parameters
        $minPrice = $request->filled('minPrice') ? $request->input('minPrice') : null;
        $maxPrice = $request->filled('maxPrice') ? $request->input('maxPrice') : null;

        // Get the selected product type from query parameters or use null to indicate no filter
        $typeId = $request->filled('typeId') ? $request->input('typeId') : null;

        // Build the query to filter products
        $query = Product::where(function ($query) use ($minPrice, $maxPrice) {
            if ($minPrice !== null) {
                $query->where('price', '>=', $minPrice);
            }
            if ($maxPrice !== null) {
                $query->where('price', '<=', $maxPrice);
            }
        });

        if ($typeId !== null) {
            $query->where('type_id', $typeId);
        }

        // If no filter parameters provided, return all products
        if ($minPrice === null && $maxPrice === null && $typeId === null) {
            $filteredProducts = Product::all();
        } else {
            // Retrieve the filtered products
            $filteredProducts = $query->get();
        }

        return response()->json(['filtered_products' => $filteredProducts], 200);
    }
    ////////////////////////////////////////////////////Search Product/////////////////////////////////////////////////////
    public function searchProducts(Request $request)
    {
        // Get the search query from the request
        $searchQuery = $request->query('query');

        // Perform the search using the query
        $results = Product::where('name_en', 'like', "%$searchQuery%")
            ->orWhere('name_ar', 'like', "%$searchQuery%")
            ->orWhere('description_en', 'like', "%$searchQuery%")
            ->orWhere('description_ar', 'like', "%$searchQuery%")
            ->get();

        return response()->json(['results' => $results], 200);
    }
    public function searchhints(Request $request)
    {
        // Get the search query from the request
        $searchQuery = $request->query('query');

        // Perform a partial text search to get search hints
        $suggestions = Product::where('name_en', 'like', "%$searchQuery%")
        ->orWhere('name_ar', 'like', "%$searchQuery%")
        ->select(['id','name_en', 'name_ar']) // Select the fields you want to suggest
        ->distinct() // Ensure distinct results
        ->limit(5) // Limit the number of suggestions
        ->get();

        return response()->json(['suggestions' => $suggestions], 200);
    }
}
