<?php

declare(strict_types=1);

// SPDX-License-Identifier: Apache-2.0
// Copyright (c) 2024-2026 FinAegis Contributors

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class ProductionRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect('/', 301);
    }
}
