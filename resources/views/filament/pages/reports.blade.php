<x-filament-panels::page>

    {{-- {{ __('admin.report_filter') }} --}}
    <x-filament::section>
        <x-slot name="heading">
            {{ __('admin.report_filter') }}
        </x-slot>

        {{ $this->form }}
    </x-filament::section>


    {{-- آمار اصلی --}}
    <x-filament::section>
        <x-slot name="heading">
            {{ __('admin.general_statistics') }}
        </x-slot>

        <div style="
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
            gap:16px;
        ">
            @php
                $cards = [
                    [__('admin.users_total'), $stats['users_total'] ?? 0],
                    [__('admin.users_active'), $stats['users_active'] ?? 0],
                    [__('admin.users_admin'), $stats['users_admin'] ?? 0],
                    [__('admin.users_today'), $stats['users_today'] ?? 0],
                    [__('admin.users_month'), $stats['users_month'] ?? 0],

                    [__('admin.subscriptions_total'), $stats['subscriptions_total'] ?? 0],
                    [__('admin.subscriptions_month'), $stats['subscriptions_month'] ?? 0],

                    [__('admin.codes_total'), $stats['codes_total'] ?? 0],
                    [__('admin.codes_printed'), $stats['codes_printed'] ?? 0],
                    [__('admin.codes_used'), $stats['codes_used'] ?? 0],
                    [__('admin.codes_generated'), $stats['codes_generated'] ?? 0],

                    [__('admin.books'), $stats['books'] ?? 0],
                    [__('admin.games'), $stats['games'] ?? 0],
                    [__('admin.sound_books'), $stats['sound_books'] ?? 0],
                    [__('admin.quran'), $stats['quran'] ?? 0],

                    [__('admin.published_contents'), $stats['published_contents'] ?? 0],
                    [__('admin.unpublished_contents'), $stats['unpublished_contents'] ?? 0],

                    [__('admin.downloads_total'), number_format($stats['downloads_total'] ?? 0)],
                    [__('admin.readings_total'), number_format($stats['readings_total'] ?? 0)],
                    [__('admin.read_books_total'), number_format($stats['read_books_total'] ?? 0)],

                    [__('admin.device_tokens_total'), number_format($stats['device_tokens_total'] ?? 0)],
                    [__('admin.android_devices'), number_format($stats['device_tokens_android'] ?? 0)],
                ];
            @endphp

            @foreach ($cards as $card)
                <div style="
                    border:1px solid rgba(128,128,128,.25);
                    border-radius:12px;
                    padding:18px;
                ">
                    <div style="
                        font-size:13px;
                        opacity:.65;
                        margin-bottom:8px;
                    ">
                        {{ $card[0] }}
                    </div>

                    <div style="
                        font-size:28px;
                        font-weight:700;
                    ">
                        {{ $card[1] }}
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>


    {{-- {{ __('admin.latest_users') }} --}}
    <x-filament::section>
        <x-slot name="heading">
            {{ __('admin.latest_users') }}
        </x-slot>

        <div style="overflow-x:auto;">
            <table style="
                width:100%;
                border-collapse:collapse;
                text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            ">
                <thead>
                    <tr>
                        <th style="padding:10px;border-bottom:1px solid #8884;">
                            {{ __('admin.email') }}
                        </th>

                        <th style="padding:10px;border-bottom:1px solid #8884;">
                            {{ __('admin.username') }}
                        </th>

                        <th style="padding:10px;border-bottom:1px solid #8884;">
                            {{ __('admin.country') }}
                        </th>

                        <th style="padding:10px;border-bottom:1px solid #8884;">
                            {{ __('admin.membership_date') }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($latestUsers ?? [] as $user)
                        <tr>
                            <td style="padding:10px;border-bottom:1px solid #8882;">
                                {{ $user['email'] ?? '-' }}
                            </td>

                            <td style="padding:10px;border-bottom:1px solid #8882;">
                                {{ $user['username'] ?? '-' }}
                            </td>

                            <td style="padding:10px;border-bottom:1px solid #8882;">
                                {{ $user['country'] ?? '-' }}
                            </td>

                            <td style="padding:10px;border-bottom:1px solid #8882;">
                                @if (!empty($user['created_at']))
                                    {{ \Carbon\Carbon::parse($user['created_at'])->format('Y-m-d H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding:16px;text-align:center;opacity:.6;">
                                {{ __('admin.no_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>


    {{-- {{ __('admin.latest_subscriptions') }} --}}
    <x-filament::section>
        <x-slot name="heading">
            {{ __('admin.latest_subscriptions') }}
        </x-slot>

        <div style="overflow-x:auto;">
            <table style="
                width:100%;
                border-collapse:collapse;
                text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            ">
                <thead>
                    <tr>
                        <th style="padding:10px;border-bottom:1px solid #8884;">
                            {{ __('admin.user') }}
                        </th>

                        <th style="padding:10px;border-bottom:1px solid #8884;">
                            {{ __('admin.subscription_code') }}
                        </th>

                        <th style="padding:10px;border-bottom:1px solid #8884;">
                            {{ __('admin.duration') }}
                        </th>

                        <th style="padding:10px;border-bottom:1px solid #8884;">
                            {{ __('admin.date') }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($latestSubscriptions ?? [] as $subscription)
                        <tr>
                            <td style="padding:10px;border-bottom:1px solid #8882;">
                                {{ $subscription['email'] ?? '-' }}
                            </td>

                            <td style="padding:10px;border-bottom:1px solid #8882;">
                                {{ $subscription['code'] ?? '-' }}
                            </td>

                            <td style="padding:10px;border-bottom:1px solid #8882;">
                                {{ $subscription['amount'] ?? 0 }} {{ __('admin.days') }}
                            </td>

                            <td style="padding:10px;border-bottom:1px solid #8882;">
                                @if (!empty($subscription['date']))
                                    {{ \Carbon\Carbon::parse($subscription['date'])->format('Y-m-d H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding:16px;text-align:center;opacity:.6;">
                                {{ __('admin.no_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>


    {{-- آمار {{ __('admin.book') }}‌ها --}}
    <div style="
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(400px,1fr));
        gap:20px;
    ">

        <x-filament::section>
            <x-slot name="heading">
                {{ __('admin.top_downloaded_books') }}
            </x-slot>

            <div style="overflow-x:auto;">
                <table style="
                    width:100%;
                    border-collapse:collapse;
                    text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
                ">
                    <thead>
                        <tr>
                            <th style="padding:10px;border-bottom:1px solid #8884;">
                                {{ __('admin.book') }}
                            </th>

                            <th style="padding:10px;border-bottom:1px solid #8884;">
                                {{ __('admin.downloads') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($topBooksByDownloads ?? [] as $book)
                            <tr>
                                <td style="padding:10px;border-bottom:1px solid #8882;">
                                    {{ $book['title'] ?? '-' }}
                                </td>

                                <td style="padding:10px;border-bottom:1px solid #8882;">
                                    {{ number_format($book['downloads'] ?? 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="padding:16px;text-align:center;opacity:.6;">
                                    {{ __('admin.no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>


        <x-filament::section>
            <x-slot name="heading">
                {{ __('admin.top_read_books') }}
            </x-slot>

            <div style="overflow-x:auto;">
                <table style="
                    width:100%;
                    border-collapse:collapse;
                    text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
                ">
                    <thead>
                        <tr>
                            <th style="padding:10px;border-bottom:1px solid #8884;">
                                {{ __('admin.book') }}
                            </th>

                            <th style="padding:10px;border-bottom:1px solid #8884;">
                                {{ __('admin.readings') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($topBooksByReadings ?? [] as $book)
                            <tr>
                                <td style="padding:10px;border-bottom:1px solid #8882;">
                                    {{ $book['title'] ?? '-' }}
                                </td>

                                <td style="padding:10px;border-bottom:1px solid #8882;">
                                    {{ number_format($book['readings'] ?? 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="padding:16px;text-align:center;opacity:.6;">
                                    {{ __('admin.no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

    </div>

</x-filament-panels::page>