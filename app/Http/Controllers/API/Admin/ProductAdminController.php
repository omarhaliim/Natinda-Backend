<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Product;
use App\Models\ProductType;



class ProductAdminController extends Controller
{
    public function getAllProductsAdmin(Request $request)
    {
        $query = Product::with('productType', 'reviews');

        if ($request->filled('minPrice')) {
            $query->where('price', '>=', $request->input('minPrice'));
        }

        if ($request->filled('maxPrice')) {
            $query->where('price', '<=', $request->input('maxPrice'));
        }

        if ($request->filled('typeId')) {
            $typeName = $request->input('typeId');

            $query->whereHas('productType', function ($q) use ($typeName) {
                $q->where('name_en', $typeName);
            });
        }

        $products = $query->get();

        $products = $products->map(function ($product) {
            $ratings = $product->reviews->pluck('rating');
            $averageRating = $ratings->isNotEmpty() ? $ratings->avg() : 0;
            $product['average_rating'] = $averageRating;

            return $product;
        });

        return response()->json($products, Response::HTTP_OK);
    }
    public function getAllTypeIdAdmin(Request $request)
    {
        $nameEnValues = ProductType::pluck('name_en')->all();

        return response()->json($nameEnValues, 200);

        return response()->json($nameEnValues, Response::HTTP_OK);

    }
    function editProductAdmin(Request $request)
    {
        $productId = $request->input('id');

        // // Find the product by ID
        $product = Product::find($productId);

        // Check if the product exists
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $data = $request->all();

        // Remove the 'id' field from the data to avoid updating it
        unset($data['id']);
        unset($data['type_id']);

        // Check if 'type_name_en' is present in the request data
        if (isset($data['type_name_en'])) {
            // Find the product type by name_en
            $productType = ProductType::where('name_en', $data['type_name_en'])->first();

            // Check if the product type exists
            if ($productType) {
                // Update the product type ID
                $data['type_id'] = $productType->id;
                // Remove 'type_name_en' from the data array to avoid errors during update
                unset($data['type_name_en']);
            } else {
                return response()->json(['success' => false, 'message' => 'Product type not found'], 404);
            }
        }

        // Save the original data before the update
        $originalData = $product->toArray();

        // Update the product fields dynamically
        $product->update($data);

        // Check if any changes were made
        $changes = array_diff_assoc($product->toArray(), $originalData);

        if (empty($changes)) {
            return response()->json(['success' => true, 'message' => 'No changes made'], 200);
        }

        return response()->json(['success' => $productId, 'message' => 'Product updated successfully'], 200);
    }
    public function getProductAdmin(Request $request)
    {
        $productId = $request->input('id');
        // Retrieve the product by its ID along with its type
        $product = Product::with('productType','reviews', 'faqs')->find($productId);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json(['product' => $product], 200);
    }
    public function addNewProductAdmin(Request $request)
    {
        // Validate the request data
        $request->validate([
            'name_en' => 'required|string',
            'name_ar' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'description_en' => 'required|string',
            'description_ar' => 'required|string',
            'how_to_use_en' => 'required|string',
            'how_to_use_ar' => 'required|string',
            'ingredients_en' => 'required|string',
            'ingredients_ar' => 'required|string',
            'type_name_en' => 'required|string', // Assuming you receive the type name in the request
        ]);

        // Find the product type by name_en
        $productType = ProductType::where('name_en', $request->input('type_name_en'))->first();

        if (!$productType) {
            // If the product type doesn't exist, you might want to handle this case accordingly.
            return response()->json(['error' => 'Product type not found'], 404);
        }

        // Create a new product
        $newProduct = new Product([
            'name_en' => $request->input('name_en'),
            'name_ar' => $request->input('name_ar'),
            'price' => $request->input('price'),
            'quantity' => $request->input('quantity'),
            'description_en' => $request->input('description_en'),
            'description_ar' => $request->input('description_ar'),
            'how_to_use_en' => $request->input('how_to_use_en'),
            'how_to_use_ar' => $request->input('how_to_use_ar'),
            'ingredients_en' => $request->input('ingredients_en'),
            'ingredients_ar' => $request->input('ingredients_ar'),
            'type_id' => $productType->id,
        ]);

        // Save the new product
        $newProduct->save();

        // You can return a success response or the newly created product data
        return response()->json(['success' => true, 'data' => $newProduct], 201);
    }
}
