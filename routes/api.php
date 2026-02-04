<?php

use App\Models\Finance;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\LogActivityController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\WarehouseStockController;
use App\Http\Controllers\ProductCategoryController;

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load('role.warehouse');
    });

    Route::apiResource('users', UserController::class);
    Route::get('get-all-users', [UserController::class, 'getAllUsers']);
    Route::put('users/{id}/update-password', [UserController::class, 'updatePassword']);

    Route::apiResource('accounts', ChartOfAccountController::class);
    Route::get('get-all-accounts', [ChartOfAccountController::class, 'getAllAccounts']);
    Route::get('get-account-by-account-id', [ChartOfAccountController::class, 'getAccountByAccountId']);
    Route::get('get-cash-and-bank', [ChartOfAccountController::class, 'getCashAndBank']);
    Route::apiResource('category-accounts', AccountController::class);
    Route::delete('delete-selected-account', [ChartOfAccountController::class, 'deleteAll']);
    Route::put('warehouse/{warehouse}/add-cash-bank/{id}', [ChartOfAccountController::class, 'addCashAndBankToWarehouse']);
    Route::get('get-cash-bank-by-warehouse/{warehouse}', [ChartOfAccountController::class, 'getCashAndBankByWarehouse']);
    Route::get('get-expense-accounts', [ChartOfAccountController::class, 'getExpenses']);
    Route::get('get-cash-bank-balance/{warehouse}/{endDate}', [ChartOfAccountController::class, 'getCashBankBalance']);
    Route::get('daily-dashboard/{warehouse}/{endDate}', [ChartOfAccountController::class, 'dailyDashboard']);
    Route::get('balance-sheet-report/{startDate}/{endDate}', [ChartOfAccountController::class, 'balanceSheetReport']);
    Route::get('profit-loss-report/{startDate}/{endDate}', [ChartOfAccountController::class, 'profitLossReport']);
    Route::get('cash-flow-report/{startDate}/{endDate}', [ChartOfAccountController::class, 'cashFlowReport']);

    Route::apiResource('products', ProductController::class);
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::get('get-all-products', [ProductController::class, 'getAllProducts']);

    //contacts
    Route::apiResource('contacts', ContactController::class);
    Route::get('get-all-contacts', [ContactController::class, 'getAllContacts']);
    Route::get('get-all-contacts-by-type/{type}', [ContactController::class, 'getContactByType']);

    Route::apiResource('warehouse', WarehouseController::class);
    Route::get('get-all-warehouses', [WarehouseController::class, 'getAllWarehouses']);

    //journals
    Route::apiResource('journals', JournalController::class);
    Route::post('create-transfer', [JournalController::class, 'createTransfer']);
    Route::post('create-voucher', [JournalController::class, 'createVoucher']);
    Route::post('create-deposit', [JournalController::class, 'createDeposit']);
    Route::post('create-mutation', [JournalController::class, 'createMutation']);
    Route::post('create-sales-by-value', [JournalController::class, 'createSalesByValue']);
    Route::get('get-journal-by-warehouse/{warehouse}/{startDate}/{endDate}', [JournalController::class, 'getJournalByWarehouse']);
    Route::get('get-expenses/{warehouse}/{startDate}/{endDate}', [JournalController::class, 'getExpenses']);
    Route::get('get-warehouse-balance/{endDate}', [JournalController::class, 'getWarehouseBalance']);
    Route::get('get-revenue-report/{startDate}/{endDate}', [JournalController::class, 'getRevenueReport']);
    Route::get('get-revenue-report-by-warehouse/{warehouseId}/{month}/{year}', [JournalController::class, 'getRevenueReportByWarehouse']);
    Route::get('mutation-history/{account}/{startDate}/{endDate}', [JournalController::class, 'mutationHistory']);
    Route::get('get-rank-by-profit', [JournalController::class, 'getRankByProfit']);
    Route::get('get-daily-profit/{month}/{year}', [JournalController::class, 'getDailyProfit']);


    //transactions
    Route::apiResource('transactions', TransactionController::class);
    Route::get('get-trx-vcr/{warehouse}/{startDate}/{endDate}', [TransactionController::class, 'getTrxVcr']);
    Route::get('get-trx-by-warehouse/{warehouse}/{startDate}/{endDate}', [TransactionController::class, 'getTrxByWarehouse']);
    Route::post('store-with-deposit', [TransactionController::class, 'storeSalesWithDeposit']);
    Route::get('get-trx-by-product-id/{productId}/{startDate}/{endDate}', [TransactionController::class, 'getTrxByProductId']);
    Route::get('get-trx-all-product-by-warehouse/{warehouse}/{endDate}', [TransactionController::class, 'getTrxAllProductByWarehouse']);
    Route::get('get-trx-by-date/{startDate}/{endDate}', [TransactionController::class, 'getTrxByDate']);
    Route::post('stock-adjustment', [TransactionController::class, 'stockAdjustment']);
    Route::post('reverse-stock-transaction', [TransactionController::class, 'reverseStockTransaction']);

    //Finance
    Route::apiResource('finance', FinanceController::class);
    Route::get('finance-by-type/{contact}/{financeType}', [FinanceController::class, 'getFinanceByType']);
    Route::get('get-finance-by-contact-id/{contactId}', [FinanceController::class, 'getFinanceByContactId']);
    Route::post('store-payment', [FinanceController::class, 'storePayment']);
    Route::get('get-finance-data/{invoice}', [FinanceController::class, 'getFinanceData']);
    Route::get('get-finance-yearly/{year}', [FinanceController::class, 'getFinanceYearly']);

    Route::get('log-activity/{startDate}/{endDate}', [LogActivityController::class, 'index']);


    //warehouseStock

    Route::apiResource('warehouse-stocks', WarehouseStockController::class);
    Route::get('get-stocks-by-warehouse/{warehouse}', [WarehouseStockController::class, 'getStocksByWarehouse']);
    Route::get('sync-stock', [WarehouseStockController::class, 'syncStock']);
    Route::get('sync-all-stock', [WarehouseStockController::class, 'syncAllStock']);
    Route::get('sync-all-stock-warehouse', [WarehouseStockController::class, 'syncAllStockWarehouse']);

    Route::post('license/accept-agreement', [LicenseController::class, 'acceptAgreement']);
    Route::get('get-license', function () {
        $license = License::with('agreements')->first();

        return response()->json([
            'client_name' => $license->client_name,
            'is_active' => $license->is_active,
            'is_paid' => $license->is_paid,
            'preriod_end' => $license->period_end,
            'agreements' => $license->agreements,
        ]);
    });
});
