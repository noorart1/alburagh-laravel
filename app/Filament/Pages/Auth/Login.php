<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return 'تسجيل الدخول إلى لوحة الإدارة';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'دار البراق لثقافة الأطفال';
    }
}