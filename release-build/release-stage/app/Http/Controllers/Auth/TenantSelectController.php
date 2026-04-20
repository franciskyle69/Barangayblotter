<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantSelectController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Auth/TenantSelect', [
            'tenants' => [],
        ]);
    }

    public function select(Request $request): RedirectResponse
    {
        return redirect()->route('dashboard');
    }
}
