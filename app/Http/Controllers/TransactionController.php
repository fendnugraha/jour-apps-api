<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Journal;
use App\Models\Product;
use App\Models\LogActivity;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;

class TransactionController extends Controller
{
    public $startDate;
    public $endDate;

    public function __construct()
    {
        $this->startDate = Carbon::now()->startOfDay();
        $this->endDate = Carbon::now()->endOfDay();
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = Transaction::with(['product', 'contact'])->orderBy('created_at', 'desc')->paginate(10);

        return new AccountResource($transactions, true, "Successfully fetched transactions");
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
            'cart' => 'required|array',
            'transaction_type' => 'required|string',
        ]);
        $warehouseId = auth()->user()->role->warehouse_id;
        $userId = auth()->user()->id;

        // $modal = $this->modal * $this->quantity;

        $invoice = Journal::invoice_journal();

        DB::beginTransaction();
        try {
            foreach ($request->cart as $item) {
                $journal = new Journal();
                $price = $item['price'] * $item['quantity'];
                $cost = Product::find($item['id'])->cost;
                $modal = $cost * $item['quantity'];

                $description = $request->transaction_type == 'Sales' ? "Penjualan Accessories" : "Pembelian Accessories";
                $fee = $price - $modal;

                if ($request->transaction_type == 'Sales') {
                    $journal->create([
                        'invoice' => $invoice,  // Menggunakan metode statis untuk invoice
                        'date_issued' => now(),
                        'debt_code' => 9,
                        'cred_code' => 9,
                        'amount' => $modal,
                        'fee_amount' => $fee,
                        'trx_type' => 'Accessories',
                        'description' => $description,
                        'user_id' => $userId,
                        'warehouse_id' => $warehouseId
                    ]);
                }

                $sale = new Transaction([
                    'date_issued' => now(),
                    'invoice' => $invoice,
                    'product_id' => $item['id'],
                    'quantity' => $request->transaction_type == 'Sales' ? $item['quantity'] * -1 : $item['quantity'],
                    'price' => $request->transaction_type == 'Sales' ? $item['price'] : 0,
                    'cost' => $request->transaction_type == 'Sales' ? $cost : $item['price'],
                    'transaction_type' => $request->transaction_type,
                    'contact_id' => 1,
                    'warehouse_id' => $warehouseId,
                    'user_id' => $userId
                ]);
                $sale->save();

                $product = Product::find($item['id']);
                $transaction = new Transaction();

                if ($request->transaction_type == 'Sales') {
                    $sold = Product::find($item['id'])->sold + $item['quantity'];
                    Product::find($item['id'])->update(['sold' => $sold]);

                    $product_log = $transaction->where('product_id', $product->id)->sum('quantity');
                    $end_Stock = $product->stock + $product_log;
                    Product::where('id', $product->id)->update([
                        'end_Stock' => $end_Stock,
                        'price' => $item['price'],
                    ]);

                    $updateWarehouseStock = WarehouseStock::where('warehouse_id', $warehouseId)->where('product_id', $product->id)->first();
                    $updateCurrentStock = $transaction->where('product_id', $product->id)->where('warehouse_id', $warehouseId)->sum('quantity');
                    if ($updateWarehouseStock) {
                        $updateWarehouseStock->current_stock = $updateCurrentStock;
                        $updateWarehouseStock->save();
                    } else {
                        $warehouseStock = new WarehouseStock();
                        $warehouseStock->warehouse_id = $warehouseId;
                        $warehouseStock->product_id = $product->id;
                        $warehouseStock->init_stock = 0;
                        $warehouseStock->current_stock = $updateCurrentStock;
                        $warehouseStock->save();
                    }
                } else {
                    Product::updateCostAndStock($item['id'], $item['quantity'], $item['price'], $warehouseId);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Penjualan accesories berhasil disimpan, invoice: ' . $invoice,
                'journal' => $journal
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create journal'
            ], 500);
        }
    }

    private function _addTransactionToJournal($dateIssued, $transaction_type, $invoice, $description, $price, $cost, $paymentAccountID, $userId, $warehouseId)
    {
        if ($transaction_type == 'Sales') {
            Journal::create([
                'invoice' => $invoice,  // Menggunakan metode statis untuk invoice
                'date_issued' => $dateIssued ?? now(),
                'debt_code' => $paymentAccountID,
                'cred_code' => 13,
                'amount' => $price,
                'fee_amount' => 0,
                'trx_type' => 'Penjualan Barang',
                'description' => $description,
                'user_id' => $userId,
                'warehouse_id' => $warehouseId
            ]);

            Journal::create([
                'invoice' => $invoice,  // Menggunakan metode statis untuk invoice
                'date_issued' => $dateIssued ?? now(),
                'debt_code' => 14,
                'cred_code' => 6,
                'amount' => $cost,
                'fee_amount' => 0,
                'trx_type' => 'Penjualan Barang',
                'description' => $description,
                'user_id' => $userId,
                'warehouse_id' => $warehouseId
            ]);
        } else {
            Journal::create([
                'invoice' => $invoice,  // Menggunakan metode statis untuk invoice
                'date_issued' => $dateIssued ?? now(),
                'debt_code' => 6,
                'cred_code' => $paymentAccountID,
                'amount' => $price,
                'fee_amount' => 0,
                'trx_type' => 'Pembelian Barang',
                'description' => $description,
                'user_id' => $userId,
                'warehouse_id' => $warehouseId
            ]);
        }
    }

    public function storeSalesWithDeposit(Request $request)
    {
        $request->validate([
            'cart' => 'required|array',
            'transaction_type' => 'required|string',
            'feeCustomer' => 'numeric',
            'paymentAccountID' => 'required|exists:chart_of_accounts,id',
        ]);

        $warehouseId = auth()->user()->role->warehouse_id;
        $userId = auth()->user()->id;

        // $modal = $this->modal * $this->quantity;

        $invoice = Journal::invoice_journal();

        DB::beginTransaction();
        try {
            foreach ($request->cart as $item) {
                $price = $item['price'] * $item['quantity'];
                $cost = $item['category'] === 'Deposit' ? $item['cost'] : Product::find($item['id'])->cost;
                $modal = $cost * $item['quantity'];

                $description = $request->transaction_type == 'Sales' ? "Penjualan " . $item['name'] . " (Product ID:" . $item['id'] . ")" : "Pembelian " . $item['name'] . " (Product ID:" . $item['id'] . ")";

                $this->_addTransactionToJournal($request->dateIssued, $request->transaction_type, $invoice, $description, $price, $modal, $request->paymentAccountID, $userId, $warehouseId);

                Transaction::create([
                    'date_issued' => $request->dateIssued ?? now(),
                    'invoice' => $invoice,
                    'product_id' => $item['id'],
                    'quantity' => $request->transaction_type == 'Sales' ? ($item['category'] === 'Deposit' ? $item['cost'] * -1 : $item['quantity'] * -1) : $item['quantity'],
                    'price' => $request->transaction_type == 'Sales' ? ($item['category'] === 'Deposit' ? $item['quantity'] : $item['price']) : 0,
                    'cost' => $request->transaction_type == 'Sales' ? $cost : $item['price'],
                    'transaction_type' => $request->transaction_type,
                    'contact_id' => 1,
                    'warehouse_id' => $warehouseId,
                    'user_id' => $userId
                ]);

                $product = Product::find($item['id']);
                $transaction = new Transaction();

                if ($request->transaction_type == 'Sales') {
                    $quantity = $product->category === 'Deposit' ? $item['cost'] : $item['quantity'];
                    $sold = Product::find($item['id'])->sold + $quantity;
                    Product::find($item['id'])->update(['sold' => $sold]);

                    $product_log = $transaction->where('product_id', $product->id)->sum('quantity');
                    $end_Stock = $product->stock + $product_log;
                    if (!$product->category === 'Deposit') {
                        Product::where('id', $product->id)->update([
                            'end_Stock' => $end_Stock,
                            'price' => $item['price'],
                        ]);
                    }

                    $updateWarehouseStock = WarehouseStock::where('warehouse_id', $warehouseId)->where('product_id', $product->id)->first();
                    $updateCurrentStock = $transaction->where('product_id', $product->id)->where('warehouse_id', $warehouseId)->sum('quantity');
                    if ($updateWarehouseStock) {
                        $updateWarehouseStock->current_stock += $updateCurrentStock;
                        $updateWarehouseStock->save();
                    } else {
                        $warehouseStock = new WarehouseStock();
                        $warehouseStock->warehouse_id = $warehouseId;
                        $warehouseStock->product_id = $product->id;
                        $warehouseStock->init_stock = 0;
                        $warehouseStock->current_stock = $updateCurrentStock;
                        $warehouseStock->save();
                    }
                } else {
                    Product::updateCostAndStock($item['id'], $item['quantity'], $item['price'], $warehouseId);
                }
            }

            if ($request->feeCustomer > 0) {
                Journal::create([
                    'invoice' => $invoice,  // Menggunakan metode statis untuk invoice
                    'date_issued' => $request->dateIssued ?? now(),
                    'debt_code' => 62,
                    'cred_code' => 9,
                    'amount' => $request->feeCustomer,
                    'fee_amount' => 0,
                    'trx_type' => 'Penjualan Barang',
                    'description' => $description,
                    'user_id' => $userId,
                    'warehouse_id' => $warehouseId
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Penjualan barang berhasil disimpan, invoice: ' . $invoice,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create journal'
            ], 500);
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
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $transaction->delete();
            $invoice = $transaction->invoice;
            $product_id = $transaction->product_id;

            // Delete journal
            Journal::where('invoice', $invoice)->where('description', 'like', '%(Product ID:' . $product_id . ')%')->delete();

            // Update product stock
            Product::updateCostAndStock($product_id, -$transaction->quantity, $transaction->cost, $transaction->warehouse_id);
            Product::updateWarehouseStock($product_id, $transaction->warehouse_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transaction'
            ], 500);
        }
    }

    public function getTrxByWarehouse($warehouse, $startDate, $endDate, Request $request)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfDay();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $transactions = Transaction::with(['product', 'contact'])
            ->whereBetween('date_issued', [$startDate, $endDate])
            ->where(function ($query) use ($request) {
                if ($request->search) {
                    $query->where('invoice', 'like', '%' . $request->search . '%')
                        ->orWhereHas('product', function ($query) use ($request) {
                            $query->where('name', 'like', '%' . $request->search . '%');
                        });
                } else {
                    $query;
                }
            })
            ->where(function ($query) use ($warehouse) {
                if ($warehouse === "all") {
                    $query;
                } else {
                    $query->where('warehouse_id', $warehouse);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return new AccountResource($transactions, true, "Successfully fetched transactions");
    }

    public function getTrxByProductId($productId, $startDate, $endDate, Request $request)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfMonth() : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfMonth() : Carbon::now()->endOfMonth();

        $product = Product::with('warehouseStock')->find($productId);

        $transactions = Transaction::with(['product', 'contact'])
            ->whereBetween('date_issued', [$startDate, $endDate])
            ->where('product_id', $productId)
            ->when($request->warehouse_id !== "all", fn($query) => $query->where('warehouse_id', $request->warehouse_id))
            ->orderBy('date_issued', 'desc')
            ->paginate(10);

        $data = [
            'transactions' => $transactions,
            'product' => $product
        ];

        return new AccountResource($data, true, "Successfully fetched transactions");
    }
}
