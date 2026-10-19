<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): View|RedirectResponse
    {
        if (auth()->user()->role === 'Customer') {
            return redirect()->route('shop.index');
        }

        return view('dashboard', $dashboard->data(auth()->user()->role));
    }
}
