<?php

namespace App\Http\Controllers\HelpCenter;

use App\Http\Controllers\Controller;
use App\Http\Resources\HelpCenterResource;
use App\Models\HelpCenter;
use Illuminate\Http\Request;

class HelpCenterController extends Controller
{
   public function index(Request $request)
    {
        $filters = $request->only(['search']);

        $helpCenter = HelpCenter::filter($filters)->paginate(5)->withQueryString();

        return [
            'data' => $helpCenter,
            'filters' => $filters
        ];

    }
}
