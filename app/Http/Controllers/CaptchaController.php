<?php

namespace App\Http\Controllers;

/**
 * Controller untuk menangani captcha functionality
 */
class CaptchaController extends Controller
{
    /**
     * Reload captcha image via AJAX
     */
    public function reloadCaptcha()
    {
        return response()->json([
            'captcha' => app('captcha')->img('flat')
        ]);
    }
}
