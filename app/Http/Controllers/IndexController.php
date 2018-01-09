<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    
    public function index ()
    {
        $currencies = config('constants.currencies');
        return view('index')->with('currencies', $currencies);
    }
}
