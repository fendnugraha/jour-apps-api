<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Http\Resources\ChartOfAccountResource;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $warehouses = Warehouse::with('ChartOfAccount')->paginate(5);
        return new AccountResource($warehouses, true, "Successfully fetched warehouses");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|size:3|unique:warehouses,code',
            'name' => 'required|min:3|max:90',
            'address' => 'required|min:3|max:160',
            'acc_code' => 'required',
        ]);

        DB::beginTransaction();
        try {
            // Create and save the warehouse
            $warehouse = Warehouse::create([
                'code' => strtoupper($request->code),
                'name' => strtoupper($request->name),
                'address' => $request->address,
                'chart_of_account_id' => $request->acc_code
            ]);

            // Update the related ChartOfAccount with the warehouse ID
            ChartOfAccount::where('id', $request->acc_code)->update(['warehouse_id' => $warehouse->id]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Warehouse created successfully',
                'data' => $warehouse
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // Flash an error message
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Warehouse creation failed',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $warehouse = Warehouse::with('ChartOfAccount')->find($id);

        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $warehouse
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'name' => 'required|min:3|max:90',
            'address' => 'required|min:3|max:160',
            'chart_of_account_id' => 'required',
        ]);

        DB::beginTransaction();
        try {
            // Update the warehouse
            $warehouse->update([
                'name' => strtoupper($request->name),
                'address' => $request->address,
                'chart_of_account_id' => $request->chart_of_account_id
            ]);

            // Update the related ChartOfAccount with the warehouse ID
            ChartOfAccount::where('id', $request->chart_of_account_id)->update(['warehouse_id' => $warehouse->id]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Warehouse updated successfully',
                'data' => $warehouse
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // Flash an error message
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Warehouse update failed',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse is locked and cannot be deleted.'
            ], 403);
        }

        $journalExists = Journal::where('warehouse_id', $warehouse->id)->exists();
        if ($journalExists || $warehouse->id == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse cannot be deleted because it has related transactions.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Delete the warehouse
            $warehouse->delete();

            // Update the related ChartOfAccount with the warehouse ID
            ChartOfAccount::where('id', $warehouse->chart_of_account_id)->update(['warehouse_id' => null]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Warehouse deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // Flash an error message
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Warehouse deletion failed',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllWarehouses()
    {
        $warehouses = Warehouse::where('status', 1)->orderBy('name', 'asc')->get();
        return response()->json([
            'success' => true,
            'data' => $warehouses
        ], 200);
    }
}
