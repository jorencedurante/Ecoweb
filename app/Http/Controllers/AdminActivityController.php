<?php

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use Illuminate\Http\Request;

class AdminActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminActivity::with('user');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%");
            });
        }

        $activities = $query->latest()->paginate(15)->withQueryString();
        return view('pages.admin-activities', compact('activities'));
    }
}
