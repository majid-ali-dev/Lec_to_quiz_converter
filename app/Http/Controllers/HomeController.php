<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Main selection page dikhata hai
     */
    public function index()
    {
        return view('home');
    }
}