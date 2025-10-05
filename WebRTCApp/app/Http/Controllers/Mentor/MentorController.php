<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MentorController extends Controller
{
    public function index()
    {
        return Inertia::render('Mentor/Dashboard/Index');
    }
}
