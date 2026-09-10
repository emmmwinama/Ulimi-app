<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect(url('dashboard'));
        }
        return $this->view('pages/home', ['title' => 'AgriVault — the record vault your farm can prove']);
    }
}
