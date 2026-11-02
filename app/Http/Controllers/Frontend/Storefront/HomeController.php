<?php

namespace App\Http\Controllers\Frontend\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\HomePageDataService;

class HomeController extends Controller
{
    public function index(HomePageDataService $homePageData)
    {
        return view('frontend.storefront.home.index', $homePageData->get());
    }
}
