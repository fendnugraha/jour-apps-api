<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccountResource;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = Product::when($request->search, function ($query, $search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%');
        })
            ->orderBy('name')
            ->paginate(10)
            ->onEachSide(0);
        return new AccountResource($products, true, "Successfully fetched products");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $product = new Product();
        $request->validate(
            [
                'name' => 'required|string|max:255|unique:products,name',
                'category' => 'required',  // Make sure category_id is present
                'price' => 'required|numeric',
                'cost' => 'required|numeric',
            ]
        );

        $product->create([
            'code' => $product->newCode($request->category),
            'name' => strtoupper($request->name),
            'category' => $request->category,
            'price' => $request->price,
            'cost' => $request->cost
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return new AccountResource($product, true, "Successfully fetched product");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate(
            [
                'name' => 'required|string|max:255|unique:products,name,' . $product->id,
                'category' => 'required|exists:product_categories,name',  // Make sure category_id is present
                'price' => 'required|numeric',
                'cost' => 'required|numeric',
            ]
        );

        $product->update([
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'cost' => $request->cost
        ]);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->refresh()
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $transactionsExist = $product->transactions()->exists();
        if ($transactionsExist) {
            return response()->json([
                'success' => false,
                'message' => 'Product cannot be deleted because it has transactions'
            ], 400);
        }

        $product->delete();
        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ], 200);
    }

    public function getAllProducts()
    {
        $products = Product::orderBy('name')->get();
        return new AccountResource($products, true, "Successfully fetched products");
    }
}
