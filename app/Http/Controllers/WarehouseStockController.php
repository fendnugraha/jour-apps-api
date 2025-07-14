<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WarehouseStock;
use App\Http\Resources\AccountResource;

class WarehouseStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($warehouse = "All")
    {
        $warehouseStocks = WarehouseStock::with('product')
            ->where(fn($query) => $warehouse == "All" ? $query : $query->where('warehouse_id', $warehouse))
            ->get();

        return new AccountResource($warehouseStocks, true, "Successfully fetched warehouse stocks");
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
