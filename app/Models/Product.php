<?php

namespace App\Models;

use App\Models\Transaction;
use App\Models\WarehouseStock;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
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
        $end_Stock = $product->end_stock + $newQty;
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
        $newEndStock = $warehouseStock->init_stock + $product_log;

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
    }

    public static function updateCost($id, $condition = [])
    {
        $product = Product::find($id);

        if (!$product) {
            return false; // Exit if the product does not exist
        }

        $transaction = Transaction::select(
            'product_id',
            DB::raw('SUM(cost * quantity) as totalCost'),
            DB::raw('SUM(quantity) as totalQuantity')
        )
            ->where('product_id', $product->id)
            ->where('transaction_type', 'Purchase')
            ->when(!empty($condition), function ($query) use ($condition) {
                $query->where($condition);
            })
            ->groupBy('product_id')
            ->first();

        if (!$transaction || $transaction->totalQuantity == 0) {
            // No transactions or zero quantity
            Product::where('id', $product->id)->update([
                'cost' => 0, // Set cost to 0 or leave unchanged based on requirements
            ]);
            return false;
        }

        // Calculate new cost
        $newCost = $transaction->totalCost / $transaction->totalQuantity;

        // Update the product's cost
        Product::where('id', $product->id)->update([
            'cost' => $newCost,
        ]);

        return true;
    }


    public static function updateCostAndStock($id, $newQty, $newCost, $warehouse_id)
    {
        $product = Product::find($id);
        if ($product->category === 'Deposit') {
            return;
        }

        //update Stock
        $product_log = Transaction::where('product_id', $product->id)->sum('quantity');
        $end_Stock = $product->end_stock + $newQty;

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
            $initSumStock = $product->end_stock * $product->cost;
            $newSumStock = $newQty * $newCost;
            $newCost = ($initSumStock + $newSumStock) / ($end_Stock === 0 ? 1 : $end_Stock);
            Product::where('id', $product->id)->update([
                'cost' => $newCost,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
}
