<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class PreventRequestForgery extends Middleware
{
    /**
     * ASVS 3.3.1: cookie XSRF-TOKEN bernama tetap (tanpa prefix __Host-) dan hanya
     * dibutuhkan klien AJAX. Form aplikasi memakai token @csrf, jadi cookie dimatikan.
     *
     * @var bool
     */
    protected $addHttpCookie = false;
}
