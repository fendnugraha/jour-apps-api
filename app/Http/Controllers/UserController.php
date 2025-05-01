<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\AccountResource;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('role.warehouse')->paginate(10)->onEachSide(0);

        return new AccountResource($users, true, "Successfully fetched all users");
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
            'name' => 'required|min:3|max:90',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'confirmPassword' => 'required|same:password',
            'warehouse' => 'required|exists:warehouses,id',
            'role' => 'required|in:Administrator,Staff'
        ]);

        DB::beginTransaction();
        try {
            // Create and save the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'email_verified_at' => now(),
                'password' => $request->password
            ]);

            // Create and save the user role
            $user->role()->create([
                'role' => $request->role,
                'warehouse_id' => $request->warehouse
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // Flash an error message
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the user'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['role.warehouse'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ], 200);
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
        $request->validate([
            'name' => 'required|min:3|max:90',
            'email' => 'required|email|unique:users,email,' . $id,
            'warehouse' => 'required|exists:warehouses,id',
            'role' => 'required|in:Administrator,Staff'
        ]);

        DB::beginTransaction();
        try {
            // Update the user
            $user = User::find($id);
            $user->update([
                'name' => $request->name,
                'email' => $request->email
            ]);

            // Update the user role
            $user->role()->update([
                'role' => $request->role,
                'warehouse_id' => $request->warehouse
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // Flash an error message
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the user'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $journalExists = $user->journals()->exists();
        $transactionExists = $user->transactions()->exists();

        if ($journalExists || $transactionExists || $user->id === 1) {
            return response()->json([
                'success' => false,
                'message' => 'User cannot be deleted because they have transactions or journals'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $user->role()->delete();
            $user->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // Flash an error message
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the user'
            ], 500);
        }
    }

    public function getAllUsers()
    {
        $users = User::with(['role.warehouse'])->orderBy('name', 'asc')->get();
        return new AccountResource($users, true, "Successfully fetched users");
    }

    public function updatePassword(Request $request, string $id)
    {
        $request->validate([
            // 'oldPassword' => 'required',
            'password' => 'required|min:6',
            'confirmPassword' => 'required|same:password'
        ]);

        $user = User::find($id);
        if (auth()->user()->role->role !== 'Administrator') {
            if (!password_verify($request->oldPassword, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Old password is incorrect'
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            $user->update([
                'password' => $request->password
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // Flash an error message
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the password'
            ], 500);
        }
    }
}
