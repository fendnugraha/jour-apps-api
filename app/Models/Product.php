<?php

namespace App\Models;

use App\Models\Transaction;
use App\Models\WarehouseStock;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function warehouseStock()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public static function newCode($category)
    {
        $lastCode = Product::select(DB::raw('MAX(RIGHT(code,4)) AS lastCode'))
            ->where('category', $category)
            ->get();

        $lastCode = $lastCode[0]->lastCode;
        if ($lastCode != null) {
            $kd = $lastCode + 1;
        } else {
            $kd = "0001";
        }

        $category_slug = ProductCategory::where('name', $category)->first();

        return $category_slug->prefix . '' . \sprintf("%04s", $kd);
    }

    public static function updateStock($id, $newQty, $warehouse_id)
    {
        $product = Product::find($id);
        $product_log = Transaction::where('product_id', $product->id)->sum('quantity');
        $end_Stock = $newQty;
        Product::where('id', $product->id)->update([
            'end_Stock' => $end_Stock,
        ]);

        $updateWarehouseStock = WarehouseStock::where('warehouse_id', $warehouse_id)->where('product_id', $product->id)->first();
        if ($updateWarehouseStock) {
            $updateWarehouseStock->current_stock += $newQty;
            $updateWarehouseStock->save();
        } else {
            $warehouseStock = new WarehouseStock();
            $warehouseStock->warehouse_id = $warehouse_id;
            $warehouseStock->product_id = $product->id;
            $warehouseStock->current_stock = $newQty;
            $warehouseStock->save();
        }

        return true;
    }

    public static function updateWarehouseStock($id, $warehouse_id): void
    {
        $product = Product::find($id);
        $warehouseStock = WarehouseStock::where('warehouse_id', $warehouse_id)->where('product_id', $product->id)->first();
        $product_log = Transaction::where('product_id', $product->id)->where('warehouse_id', $warehouse_id)->sum('quantity');
        $newEndStock = $product_log;

        if ($warehouseStock) {
            $warehouseStock->current_stock = $newEndStock;
            $warehouseStock->save();
        } else {
            $warehouseStock = new WarehouseStock();
            $warehouseStock->warehouse_id = $warehouse_id;
            $warehouseStock->product_id = $product->id;
            $warehouseStock->init_stock = $newEndStock;
            $warehouseStock->current_stock = $newEndStock;
            $warehouseStock->save();
        }

        Log::info("
            Product: {$product->name}
            Warehouse: {$warehouseStock->warehouse->name}
            Quantity: {$warehouseStock->current_stock}
        ");
    }

    public static function updateAllWarehouseStock(): void
    {
        $products = Product::all();
        foreach ($products as $product) {
            $warehouseStocks = WarehouseStock::where('product_id', $product->id)->get();
            $totalCurrentStock = 0;
            foreach ($warehouseStocks as $warehouseStock) {
                $product_log = Transaction::where('product_id', $product->id)->where('warehouse_id', $warehouseStock->warehouse_id)->sum('quantity');
                $newEndStock = $product_log;
                $warehouseStock->current_stock = $newEndStock;
                $warehouseStock->save();

                $totalCurrentStock += $warehouseStock->current_stock;
            }

            $product->end_stock = $totalCurrentStock;
            $product->save();
        }
    }

    public static function udpateAllStockInWarehouse($warehouse_id): void
    {
        $products = Product::all();
        foreach ($products as $product) {
            self::updateWarehouseStock($product->id, $warehouse_id);
        }
    }

    public static function updateCost($id, $condition = [])
    {
        $product = Product::find($id);
        $product_log = Transaction::where('product_id', $product->id)
            ->selectRaw('SUM(quantity) as total_qty, SUM(quantity * cost) as total_value')
            ->first();
        $newCost = $product_log->total_value / $product_log->total_qty;

        if ($product->category !== 'Deposit') {
            Log::info($newCost);
            Product::where('id', $product->id)->update([
                'cost' => $newCost,
            ]);
        }

        return true;
    }


    public static function updateCostAndStock($id, $newQty, $warehouse_id)
    {
        $product = Product::find($id);

        //update Stock
        $product_log = Transaction::where('product_id', $product->id)
            ->selectRaw('SUM(quantity) as total_qty, SUM(quantity * cost) as total_value')
            ->first();
        $end_Stock = $newQty;

        DB::beginTransaction();
        try {
            Product::where('id', $product->id)->update([
                'end_Stock' => $end_Stock,
            ]);

            $updateWarehouseStock = WarehouseStock::where('warehouse_id', $warehouse_id)->where('product_id', $product->id)->first();
            if ($updateWarehouseStock) {
                $updateWarehouseStock->current_stock += $newQty;
                $updateWarehouseStock->save();
            } else {
                $warehouseStock = new WarehouseStock();
                $warehouseStock->warehouse_id = $warehouse_id;
                $warehouseStock->product_id = $product->id;
                $warehouseStock->current_stock = $newQty;
                $warehouseStock->save();
            }

            //update cost

            $newCost = $product_log->total_value / $product_log->total_qty;

            if ($product->category !== 'Deposit') {
                Log::info($newCost);
                Product::where('id', $product->id)->update([
                    'cost' => $newCost,
                ]);
            }

            DB::commit();
            Log::info($newCost);
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return false;
        }
    }
}
