<?php

namespace App\Filament\Resources\SubscriptionCodes\Pages;

use App\Filament\Resources\SubscriptionCodes\SubscriptionCodeResource;
use App\Models\SubscriptionCode;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateSubscriptionCode extends CreateRecord
{
    protected static string $resource = SubscriptionCodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $code = trim((string) ($data['code'] ?? ''));
        $serial = trim((string) ($data['serial'] ?? ''));

        if ($code === '') {
            throw ValidationException::withMessages([
                'data.code' => __('admin.code_required'),
            ]);
        }

        if (SubscriptionCode::query()->where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'data.code' => __('admin.code_already_exists'),
            ]);
        }

        $data['code'] = $code;

        if ($serial !== '') {
            $data['serial'] = $serial;
        }

        return $data;
    }
}