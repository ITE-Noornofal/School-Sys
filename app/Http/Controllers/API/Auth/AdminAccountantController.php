<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Accountant;

class AdminAccountantController extends Controller
{
    /**
     * Allow admin to add accountant email for registration approval.
     */
    public function addEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:accountants,email',
        ]);

        $accountant = Accountant::create([
            'email' => $request->email,
            'name' => 'Pending Registration', // Temporary name
        ]);

        return response()->json([
            'message' => '✅ Email added successfully. Accountant can now register.',
            'accountant' => $accountant
        ]);
    }
}
