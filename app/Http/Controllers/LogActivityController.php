<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use App\Http\Resources\AccountResource;

class LogActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($startDate, $endDate)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfDay();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $log = LogActivity::with(['user', 'warehouse'])->whereBetween('created_at', [$startDate, $endDate])->orderBy('created_at', 'desc')->paginate(5)->onEachSide(0);

        return new AccountResource($log, true, "Successfully fetched logs");
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
