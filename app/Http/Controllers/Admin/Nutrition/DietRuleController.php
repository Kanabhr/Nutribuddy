<?php

namespace App\Http\Controllers\Admin\Nutrition;

use App\Http\Controllers\Controller;

class DietRuleController extends Controller
{
    public function index()
    {
        return view('admin.nutrition.rules.index');
    }
}
