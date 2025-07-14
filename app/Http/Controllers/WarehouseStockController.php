<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'init_stock' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $warehouseStock = WarehouseStock::where('warehouse_id', $request->warehouse_id)
                ->where('product_id', $request->product_id)
                ->first();

            if (!$warehouseStock) {
                WarehouseStock::create([
                    'warehouse_id' => $request->warehouse_id,
                    'product_id' => $request->product_id,
                    'init_stock' => $request->init_stock,
                    'current_stock' => $request->init_stock, // Optional: sync awal
                ]);
            } else {
                $warehouseStock->update([
                    'init_stock' => $request->init_stock,
                    // optionally sync current_stock too
                ]);

                Product::updateWarehouseStock($request->product_id, $request->warehouse_id);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Warehouse stock updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update warehouse stock: ' . $e->getMessage(),
            ]);
        }
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
