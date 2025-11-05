<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index() {
        return Inertia::render('Home');
    }

    public function aboutUs() {
        return Inertia::render('AboutUs');
    }

    public function download() {
        return Inertia::render('Download');
    }
}
