<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function show(License $license)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(License $license)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, License $license)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(License $license)
    {
        //
    }

    public function acceptAgreement(Request $request)
    {
        $request->validate([
            'agreement_snapshot' => 'required|string'
        ]);

        $license = License::where('id', 1)->firstOrFail();

        $license->update([
            'is_active' => true,
        ]);

        $license->agreements()->create([
            'agreement_version' => 'v1.0',
            'agreement_snapshot' => $request->agreement_snapshot,
            'accepted_at' => now(),
            'accepted_ip' => $request->ip(),
            'accepted_user_agent' => $request->header('User-Agent'),
        ]);

        return response()->json(['success' => true]);
    }
}
