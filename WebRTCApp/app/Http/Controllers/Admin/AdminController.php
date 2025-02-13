<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;

class AdminController extends Controller
{
    public function index(): Response
    {
        $users = User::where('role', '!=', 'admin')->get();

        return Inertia::render('Admin/Dashboard/Index', [
            'users' => $users,
        ]);
    }
}
