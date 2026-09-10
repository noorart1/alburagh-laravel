نسخه Floating Pinned Header

این نسخه دیگر به sticky بودن داخل ساختار Filament وابسته نیست.

کار اصلی:
- وقتی ردیف اصلی نام ستون‌ها از بالای صفحه رد شود، یک کپی دقیق از Header ساخته می‌شود.
- کپی با position:fixed مستقیماً به بالای viewport می‌چسبد.
- Scroll افقی بالای همان Header قرار دارد.
- عرض تک‌تک ستون‌های Header کپی‌شده از جدول واقعی گرفته می‌شود.
- Scroll بالایی و جدول واقعی Sync هستند.

فقط جایگزین:
resources/views/filament/components/book-catalog-fullscreen.blade.php

سپس:
php artisan optimize:clear

و Ctrl+F5
