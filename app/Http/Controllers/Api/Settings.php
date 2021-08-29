<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class Settings extends Controller
{
    public function index() {
        $site_id = Auth::user()->site_id;

        return response()->json(['data' => $site_id]);
    }

    public function save(Request $request) {
        $input = $request->all();
        $user = User::first();
        $user->site_id = $input['site_id'];
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Site ID has been updated successfully']);
    }
}
