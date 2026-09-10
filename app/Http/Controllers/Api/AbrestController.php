<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\SubscriptionCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\DeviceToken;


class AbrestController extends Controller
{
    
    public function getSettings(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'last_ver' => 1,
            'real_price' => '0',
            'discounted_price' => '0',
        ]);
    }
    public function addStats(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => 1,
        ]);
    }
    public function userReadBooks(): JsonResponse
    {
        $mail = request('mail');
    
        $user = User::where('email', $mail)->first();
    
        if (! $user) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $books = $user->readBooks()
            ->where('type', 'book')
            ->pluck('folder')
            ->filter()
            ->values()
            ->all();
    
        return response()->json([
            'success' => 1,
            'books' => $books,
        ]);
    }
    public function addDeviceToken(): JsonResponse
    {
        $token = request('token');
        $env = request('env');
    
        if (! $token) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $type = $env === 'android' ? 1 : 0;
    
        try {
            DeviceToken::updateOrCreate(
                [
                    'token' => $token,
                ],
                [
                    'user_id' => null,
                    'platform' => $env,
                    'type' => $type,
                    'language' => request('language', 'ar'),
                    'registered_at' => now(),
                ]
            );
    
            return response()->json([
                'success' => 1,
            ]);
    
        } catch (\Throwable $e) {
            return response()->json([
                'success' => 0,
            ]);
        }
    }

    public function addUserReadBooks(): JsonResponse
    {
        $mail = request('mail');
        $booksJson = request('books');
    
        $books = json_decode($booksJson, true);
    
        if (! is_array($books) || empty($books)) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $user = User::where('email', $mail)->first();
    
        if (! $user) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $contentIds = Content::query()
            ->where('type', 'book')
            ->where('is_published', 1)
            ->whereIn('folder', $books)
            ->pluck('id')
            ->all();
    
        if (empty($contentIds)) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $user->readBooks()->syncWithoutDetaching($contentIds);
    
        $readBooks = $user->readBooks()
            ->where('type', 'book')
            ->pluck('folder')
            ->filter()
            ->values()
            ->all();
    
        return response()->json([
            'success' => 1,
            'num_read_books' => count($readBooks),
            'read_books' => $readBooks,
        ]);
    }
    
    public function sendMail(): JsonResponse
    {
        $to = request('to');
        $subject = request('subject');
        $messageText = request('message');
    
        if (
            ! $to ||
            ! filter_var($to, FILTER_VALIDATE_EMAIL) ||
            $subject === null ||
            $messageText === null
        ) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        try {
            Mail::raw(
                $messageText,
                function ($message) use ($to, $subject) {
                    $message
                        ->to($to)
                        ->subject($subject);
                }
            );
    
            return response()->json([
                'success' => 1,
            ]);
    
        } catch (\Throwable $e) {
            \Log::error('Abrest send_mail failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
    
            return response()->json([
                'success' => 0,
            ]);
        }
    }
    public function resetPass(): JsonResponse
    {
        $mail = request('mail');
    
        if (! $mail) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $user = User::where('email', $mail)->first();
    
        if (! $user) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $newPass = (string) random_int(10000, 99999);
    
        try {
            $user->password = $newPass;
            $user->legacy_password = null;
            $user->save();
    
            Mail::raw(
                'New Password: ' . $newPass,
                function ($message) use ($user) {
                    $message
                        ->to($user->email)
                        ->subject('Alburagh: New Password');
                }
            );
    
            return response()->json([
                'success' => 1,
            ]);
    
        } catch (\Throwable $e) {
            return response()->json([
                'success' => 0,
            ]);
        }
    }
    
    
    public function checkNewVersion(): JsonResponse
    {
        $currentVersion = request('version');
    
        $historyPath = base_path(
            'storage/app/update_history.json'
        );
    
        if (! file_exists($historyPath)) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $history = json_decode(
            file_get_contents($historyPath),
            true
        );
    
        if (! is_array($history) || empty($history)) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $versions = array_keys($history);
    
        $currentIndex = array_search(
            $currentVersion,
            $versions,
            true
        );
    
        if (
            $currentIndex !== false &&
            $currentIndex < count($versions) - 1
        ) {
            $lastVersion = array_key_last($history);
    
            return response()->json([
                'success' => 1,
                'new_version' => $lastVersion,
                'description' => $history[$lastVersion],
            ]);
        }
    
        return response()->json([
            'success' => 0,
        ]);
    }
    
    
    public function userCodeExpireDate(): JsonResponse
    {
        $mail = request('mail');
    
        $user = User::where('email', $mail)->first();
    
        if (! $user) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $expire = $this->getUserExpire($user->id);
    
        return response()->json($expire);
    }
    
    
    public function contentCount(?string $type = null): JsonResponse
    {
        if ($type === null) {
            $count = Content::query()
                ->whereIn('type', [
                    'book',
                    'game',
                    'quran',
                ])
                ->count();

            return response()->json([
                'success' => 1,
                'count' => (string) $count,
            ]);
        }

        $typeMap = [
            'book' => 'book',
            'game' => 'game',
            'quran' => 'quran',
            'sound_book' => 'sound_book',
        ];

        if (! isset($typeMap[$type])) {
            return response()->json([
                'success' => 0,
                'count' => '0',
            ]);
        }

        $count = Content::query()
            ->where('type', $typeMap[$type])
            ->count();

        return response()->json([
            'success' => 1,
            'count' => (string) $count,
        ]);
    }
    
    public function checkCode(): JsonResponse
    {
        $code = request('code');
    
        if (! $code) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $exists = SubscriptionCode::query()
            ->where('code', $code)
            ->where('status', 'printed')
            ->exists();
    
        return response()->json([
            'success' => $exists ? 1 : 0,
        ]);
    }
    
    public function checkMailValid(): JsonResponse
    {
        $mail = request('mail');
    
        if (! $mail || ! filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $exists = User::where('email', $mail)->exists();
    
        return response()->json([
            'success' => $exists ? 0 : 1,
        ]);
    }
    
    
    public function submitCode(): JsonResponse
    {
        $mail = request('mail');
        $codeValue = request('code');
    
        $user = User::where('email', $mail)->first();
    
        if (! $user) {
            return response()->json([
                'success' => 0,
                'error' => 'Email address is not right',
            ]);
        }
    
        $code = SubscriptionCode::query()
            ->where('code', $codeValue)
            ->where('status', 'printed')
            ->first();
    
        if (! $code) {
            return response()->json([
                'success' => 0,
                'error' => 'No such code',
            ]);
        }
    
        try {
            DB::transaction(function () use ($user, $code, $codeValue) {
    
                UserSubscription::create([
                    'drupal_nid' => null,
                    'user_id' => $user->id,
                    'subscription_code_id' => $code->id,
                    'amount' => $code->amount,
                    'subscription_date' => now(),
                    'type' => null,
                ]);
    
                if ($codeValue !== '1111221111') {
                    $code->status = 'used';
                    $code->save();
                }
            });
    
            $expire = $this->getUserExpire($user->id);
    
            return response()->json([
                'success' => 1,
                'expire' => $expire['expire'] ?? 0,
                'expire_timestamp' => $expire['expire_timestamp'] ?? 0,
            ]);
    
        } catch (\Throwable $e) {
    
            \Log::error('submitCode failed', [
                'mail' => $mail,
                'code' => $codeValue,
                'error' => $e->getMessage(),
            ]);
    
            return response()->json([
                'success' => 0,
                'error' => 'Erorr adding code',
            ]);
        }
    }
    public function register(): JsonResponse
    {
        $password = request('password');
        $mail = request('mail');
        $fullname = request('fullname');
        $country = request('country');
    
        if (! $password || ! $mail) {
            return response()->json([
                'success' => 0,
                'error' => 'Email Address is not valid',
            ]);
        }
    
        if (! filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => 0,
                'error' => 'Email Address is not valid',
            ]);
        }
    
        if (User::where('email', $mail)->exists()) {
            return response()->json([
                'success' => 0,
                'error' => 'Email is taken',
            ]);
        }
    
        $username = 'USER_' . time();
    
        while (User::where('username', $username)->exists()) {
            $username = 'USER_' . time() . '_' . Str::random(4);
        }
    
        $user = User::create([
            'name' => $username,
            'username' => $username,
            'email' => $mail,
            'fullname' => $fullname,
            'country' => $country
                ? strtoupper($country)
                : null,
            'is_active' => true,
            'password' => Hash::make($password),
    
            // کاربر جدید دیگر Drupal UID ندارد.
            'drupal_uid' => null,
            'legacy_password' => null,
        ]);
    
        return response()->json([
            'success' => $user ? 1 : 0,
        ]);
    }



    public function contentList(): JsonResponse
    {
        $baseUrl = 'https://alburagh.com/app/sites/default/files';

        $books = Content::with([
                'language',
                'bookDetail.series',
                'bookDetail.author',
                'bookDetail.illustrator',
                'bookDetail.publisher',
                'bookDetail.ageGroup',
                'samples',
            ])
            ->where('type', 'book')
            ->where('is_published', 1)
            ->orderByDesc('sort_order')
            ->get()
            ->map(function (Content $content) use ($baseUrl) {
                $detail = $content->bookDetail;

                $fileSize = '0.0 MB';

                if (
                    $content->file_path &&
                    Storage::disk('drupal')->exists($content->file_path)
                ) {
                    $bytes = Storage::disk('drupal')
                        ->size($content->file_path);

                    $fileSize = number_format(
                        $bytes / 1024 / 1024,
                        2,
                        '.',
                        ''
                    ) . ' MB';
                }

                return [
                    'id' => (string) $content->drupal_nid,
                    'title' => $content->title,
                    'lang' => $content->language?->short_name,
                    'series' => $detail?->series?->name,
                    'description' => $content->description,

                    'rate' => $detail?->rate !== null
                        ? (string) $detail->rate
                        : null,

                    'downloads' => $detail?->download_count !== null
                        ? (string) $detail->download_count
                        : null,

                    'readings' => $content->reading_count !== null
                        ? (string) $content->reading_count
                        : null,

                    'author' => $detail?->author?->name,
                    'illistrator' => $detail?->illustrator?->name,
                    'publisher' => $detail?->publisher?->name,
                    'age_group' => $detail?->ageGroup?->name,

                    'cover' => $content->main_image
                        ? $baseUrl . '/' . $content->main_image
                        : null,

                    'file' => $content->file_path
                        ? $baseUrl . '/' . $content->file_path
                        : null,

                    'order' => (string) $content->sort_order,
                    'folder' => $content->folder,
                    'version' => $content->version,

                    'date' => $content->drupal_created_at
                        ? (string) strtotime($content->drupal_created_at)
                        : null,

                    'file_size' => $fileSize,

                    'samples' => $content->samples
                        ->map(fn ($sample) =>
                            $baseUrl . '/' . $sample->image_path
                        )
                        ->values()
                        ->all(),
                ];
            });

        $games = $this->mapSimpleContents(
            'game',
            $baseUrl
        );

        $qurans = $this->mapSimpleContents(
            'quran',
            $baseUrl
        );

        $soundBooks = $this->mapSimpleContents(
            'sound_book',
            $baseUrl
        );

        return response()->json([
            'books' => $books,
            'games' => $games,
            'qurans' => $qurans,
            'sound_books' => $soundBooks,
        ]);
    }


    public function changePass(): JsonResponse
    {
        $mail = request('mail');
        $oldPass = request('old_pass');
        $newPass = request('new_pass');
    
        $response = [];
    
        $user = User::where('email', $mail)->first();
    
        if (! $user) {
            return response()->json([
                'success' => 0,
                'error_code' => '0101',
            ]);
        }
    
        if (! $user->checkAppPassword($oldPass)) {
            return response()->json([
                'success' => 0,
                'error_code' => '0102',
            ]);
        }
    
        if (! $newPass) {
            return response()->json([
                'success' => 0,
                'error_code' => '0103',
            ]);
        }
    
        try {
            $user->password = $newPass;
            $user->legacy_password = null;
            $user->save();
    
            return response()->json([
                'success' => 1,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => 0,
                'error_code' => '0103',
            ]);
        }
    }

    private function mapSimpleContents(
        string $type,
        string $baseUrl
    ) {
        return Content::with([
                'language',
                'samples',
            ])
            ->where('type', $type)
            ->where('is_published', 1)
            ->orderByDesc('sort_order')
            ->get()
            ->map(function (Content $content) use (
                $type,
                $baseUrl
            ) {
                $data = [
                    'id' => (string) $content->drupal_nid,
                    'title' => $content->title,
                    'lang' => $content->language?->short_name,
                    'description' => $content->description,

                    'readings' => null,

                    'image' => $content->main_image
                        ? $baseUrl . '/' . $content->main_image
                        : null,

                    'file' => $content->file_path
                        ? $baseUrl . '/' . $content->file_path
                        : null,

                    'order' => (string) $content->sort_order,
                    'folder' => $content->folder,
                    'version' => $content->version,

                    'date' => $content->drupal_created_at
                        ? (string) strtotime($content->drupal_created_at)
                        : null,
                ];

                if (in_array($type, ['quran', 'sound_book'], true)) {
                    $data['file_size'] = '0.0 MB';
                }

                if (
                    in_array($type, ['game', 'quran'], true) &&
                    $content->samples->isNotEmpty()
                ) {
                    $data['samples'] = $content->samples
                        ->map(fn ($sample) =>
                            $baseUrl . '/' . $sample->image_path
                        )
                        ->values()
                        ->all();
                }

                return $data;
            });
    }

    public function rate(int $bookId, float $rate): JsonResponse
    {
        if ($rate > 5) {
            $rate = 5;
        }

        $book = Content::with('bookDetail')
            ->where('type', 'book')
            ->where('drupal_nid', $bookId)
            ->first();

        if (! $book || ! $book->bookDetail) {
            return response()->json([
                'success' => 0,
            ]);
        }

        $detail = $book->bookDetail;

        $currentRate = (float) $detail->rate;
        $rateCount = (int) $detail->rate_count;

        $newRate = (
            ($rateCount * $currentRate) + $rate
        ) / ($rateCount + 1);

        $detail->update([
            'rate' => $newRate,
            'rate_count' => $rateCount + 1,
        ]);

        return response()->json([
            'success' => 1,
        ]);
    }

    public function increaseDownload(int $bookId): JsonResponse
    {
        $book = Content::with('bookDetail')
            ->where('type', 'book')
            ->where('drupal_nid', $bookId)
            ->first();

        if (! $book || ! $book->bookDetail) {
            return response()->json([
                'success' => 0,
            ]);
        }

        $book->bookDetail->increment('download_count');

        return response()->json([
            'success' => 1,
        ]);
    }

    public function increaseReadings(int $bookId): JsonResponse
    {
        $book = Content::query()
            ->where('type', 'book')
            ->where('drupal_nid', $bookId)
            ->first();

        if (! $book) {
            return response()->json([
                'success' => 0,
            ]);
        }

        $book->increment('reading_count');

        return response()->json([
            'success' => 1,
        ]);
    }

    public function login(): JsonResponse
    {
        $mail = request('mail');
        $password = request('password');
    
        if (! $mail || ! $password) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $user = User::where('email', $mail)->first();
    
        if (! $user || ! $user->is_active) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        if (! $user->checkAppPassword($password)) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $expire = $this->getUserExpire($user->id);
    
        return response()->json([
            'success' => 1,
    
            // کاربران قدیمی: Drupal UID
            // کاربران جدید: Laravel ID
            'userid' => $user->drupal_uid ?? $user->id,
    
            // Android این فیلدها را حتماً می‌خواند.
            'fullname' => $user->fullname ?? '',
            'country' => $user->country ?? '',
            'mail' => $user->email,
    
            'read_books' => $user->readBooks()
                ->where('type', 'book')
                ->pluck('folder')
                ->filter()
                ->values()
                ->all(),
    
            'created' => $user->drupal_created_at
                ? $user->drupal_created_at->timestamp
                : ($user->created_at?->timestamp ?? 0),
    
            'expire' => $expire['expire'] ?? 0,
    
            // بسیار مهم برای سازگاری با Android قدیمی:
            // حتی کاربر بدون اشتراک باید این کلید را داشته باشد.
            'expire_timestamp' => $expire['expire_timestamp'] ?? 0,
        ]);
    }

    public function setUserReceipt(): JsonResponse
    {
        $mail = request('mail');
        $receipt = request('receipt');
    
        if (! $mail || ! $receipt) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        $user = User::where('email', $mail)->first();
    
        if (! $user) {
            return response()->json([
                'success' => 0,
            ]);
        }
    
        try {
            $user->receipt = $receipt;
            $user->save();
    
            return response()->json([
                'success' => 1,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => 0,
            ]);
        }
    }

    private function getUserExpire(int $userId): array
    {
        $subscriptions = UserSubscription::query()
            ->where('user_id', $userId)
            ->orderBy('subscription_date')
            ->get();
    
        if ($subscriptions->isEmpty()) {
            return [
                'success' => 1,
                'expire' => 0,
                'expire_timestamp' => 0,
            ];
        }
    
        $expireDate = null;
    
        foreach ($subscriptions as $subscription) {
            $subscriptionDate = $subscription->subscription_date
                ? $subscription->subscription_date->copy()
                : $subscription->created_at?->copy();
    
            if (! $subscriptionDate) {
                continue;
            }
    
            $amount = (int) ($subscription->amount ?? 0);
    
            if ($expireDate === null) {
                $expireDate = $subscriptionDate
                    ->copy()
                    ->addDays($amount);
    
                continue;
            }
    
            if ($subscriptionDate->lt($expireDate)) {
                $expireDate->addDays($amount);
            } else {
                $expireDate = $subscriptionDate
                    ->copy()
                    ->addDays($amount);
            }
        }
    
        if (! $expireDate) {
            return [
                'success' => 1,
                'expire' => 0,
                'expire_timestamp' => 0,
            ];
        }
    
        return [
            'success' => 1,
            'expire' => $expireDate->format('Y-m-d H:i:s'),
            'expire_timestamp' => $expireDate->timestamp,
        ];
    }
}