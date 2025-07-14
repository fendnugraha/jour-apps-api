<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccountResource;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Http\Resources\ProductCategoryResource;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $productCategories = ProductCategory::all();
        return new AccountResource($productCategories, true, "Successfully fetched product categories");
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
        $productCategory = new ProductCategory();
        $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
            'prefix' => 'required|string|size:3|unique:product_categories,prefix',
        ]);

        $productCategory->name = $request->name;
        $productCategory->prefix = $request->prefix;
        $productCategory->save();

        return response()->json([
            'message' => 'Product category created successfully',
            'product_category' => $productCategory
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCategory $productCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductCategory $productCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductCategory $productCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $productCategory)
    {
        //
    }
}
