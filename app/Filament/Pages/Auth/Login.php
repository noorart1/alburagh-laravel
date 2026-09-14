<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return 'بيانات إصدارات دار البراق لثقافة الأطفال';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }
}