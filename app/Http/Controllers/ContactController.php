<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccountResource;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contacts = Contact::orderBy('name', 'asc')->paginate(10);
        return new AccountResource($contacts, true, "Successfully fetched contacts");
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
            'name' => 'required|string|max:60',
            'type' => 'required|string|max:15',
            'phone_number' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:160',
            'description' => 'nullable|string|max:255'
        ]);

        $contact = Contact::create([
            'name' => $request['name'],
            'type' => $request['type'],
            'phone_number' => $request['phone_number'],
            'address' => $request['address'],
            'description' => $request['description'] ?? 'General Contact'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Contact created successfully',
            'data' => $contact
        ]);
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
    public function destroy(Contact $contact)
    {
        $transactionsExist = $contact->transactions()->exists();
        $financesExist = $contact->finances()->exists();

        if ($transactionsExist || $financesExist || $contact->id === 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete contact with existing transactions or finances'
            ], 400);
        }

        $contact->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Contact deleted successfully'
        ]);
    }

    public function getAllContacts()
    {
        $contacts = Contact::orderBy('name', 'asc')->get();
        return new AccountResource($contacts, true, "Successfully fetched contacts");
    }
}
