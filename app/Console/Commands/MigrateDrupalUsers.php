<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MigrateDrupalUsers extends Command
{
    protected $signature = 'drupal:migrate-users';

    protected $description = 'Migrate users from Drupal database to Laravel without overwriting existing Laravel passwords';

    public function handle(): int
    {
        $this->info('Starting Drupal users migration...');

        $total = DB::connection('drupal')
            ->table('users')
            ->where('uid', '>', 0)
            ->count();

        $this->info("Total Drupal users: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('drupal')
            ->table('users')
            ->where('uid', '>', 0)
            ->orderBy('uid')
            ->chunkById(500, function ($users) use ($bar) {

                $uids = $users->pluck('uid')->all();

                $fullnames = DB::connection('drupal')
                    ->table('field_data_field_user_fullname')
                    ->where('entity_type', 'user')
                    ->where('deleted', 0)
                    ->whereIn('entity_id', $uids)
                    ->pluck('field_user_fullname_value', 'entity_id');

                $countries = DB::connection('drupal')
                    ->table('field_data_field_user_country')
                    ->where('entity_type', 'user')
                    ->where('deleted', 0)
                    ->whereIn('entity_id', $uids)
                    ->pluck('field_user_country_iso2', 'entity_id');

                foreach ($users as $user) {

                    $existing = DB::table('users')
                        ->where('drupal_uid', $user->uid)
                        ->first();

                    $email = $user->mail ?: null;

                    $data = [
                        'name' => $fullnames[$user->uid] ?? $user->name,
                        'username' => $user->name,
                        'email' => $email,
                        'email_verified_at' => null,

                        // رمز Drupal فقط در legacy_password نگه داشته می‌شود.
                        'legacy_password' => $user->pass,

                        'fullname' => $fullnames[$user->uid] ?? null,
                        'country' => $countries[$user->uid] ?? null,

                        'is_active' => (bool) $user->status,

                        'drupal_created_at' =>
                            $user->created
                                ? Carbon::createFromTimestamp($user->created)
                                : null,

                        'drupal_last_access_at' =>
                            $user->access
                                ? Carbon::createFromTimestamp($user->access)
                                : null,

                        'drupal_last_login_at' =>
                            $user->login
                                ? Carbon::createFromTimestamp($user->login)
                                : null,

                        'updated_at' => now(),
                    ];

                    if ($existing) {
                        // بسیار مهم:
                        // password موجود Laravel دست‌نخورده باقی می‌ماند.
                        DB::table('users')
                            ->where('id', $existing->id)
                            ->update($data);
                    } else {
                        // فقط برای کاربر جدید یک رمز موقت تصادفی ساخته می‌شود.
                        $data['drupal_uid'] = $user->uid;
                        $data['password'] = Hash::make(
                            bin2hex(random_bytes(32))
                        );
                        $data['created_at'] = now();

                        DB::table('users')->insert($data);
                    }

                    $bar->advance();
                }
            }, 'uid');

        $bar->finish();

        $this->newLine(2);

        $laravelCount = DB::table('users')
            ->whereNotNull('drupal_uid')
            ->count();

        $this->info("Migrated Laravel users: {$laravelCount}");

        return Command::SUCCESS;
    }
}