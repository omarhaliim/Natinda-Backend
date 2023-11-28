<?php

namespace App\Http\Controllers\API\Wesbite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\UserProductReview;
use App\Models\Review;
use App\Models\User;

class ReviewController extends Controller
{
///////////////////////////////////////////////////Create Product Review/////////////////////////////////////////////////////
    public function createReview(Request $request)
    {
        $authUser = getTokenUserId($request->header('Authorization'));
        $productId = $request->product_id;

        if (!$productId) {
            return response()->json(['error' => 'Product ID is missing.'], Response::HTTP_BAD_REQUEST);
        }

        $existingReview = UserProductReview::where('user_id', $authUser)
            ->where('product_id', $productId)
            ->first();

        if ($existingReview) {
            return response()->json(['error' => 'You have already reviewed this product.'], Response::HTTP_BAD_REQUEST);
        }
        $user = User::where('id', $authUser)->first();



        $review = Review::create([
            'user_id' => $authUser,
            'product_id' => $productId,
            'rating' => $request->rating,
            'title' => $request->title,
            'review_comment' => $request->review_comment,
        ]);

        $user = User::where('id', $authUser)->first();

        $user->points += 90; // 2 Riyal per review

        $user->save();

        UserProductReview::create([
            'user_id' => $authUser,
            'product_id' => $productId,
        ]);

        return response()->json(['message' => 'Review created successfully.'], Response::HTTP_CREATED);
    }
    /////////////////////////////////////////////////Update Reviews////////////////////////////////////////////////////////
    public function updateReviews(Request $request, $id)
    {
        $authUser = getTokenUserId($request->header('Authorization'));

        // Find the review by its ID
        $review = Review::find($id);

        // Check if the review exists
        if (!$review) {
            return response()->json(['error' => 'Review not found.'], Response::HTTP_NOT_FOUND);
        }

        // Check if the review belongs to the authenticated user
        if ($review->user_id !== $authUser) {
            return response()->json(['error' => 'You are not authorized to edit this review.'], Response::HTTP_UNAUTHORIZED);
        }

        // Update the review's data based on the request
        if ($request->has('title')) {
            $review->title = $request->title;
        }

        if ($request->has('rating')) {
            $review->rating = $request->rating;
        }

        if ($request->has('review_comment')) {
            $review->review_comment = $request->review_comment;
        }

        // Save the updated review
        $review->save();

        return response()->json(['message' => 'Review updated successfully.'], Response::HTTP_OK);
    }
}
