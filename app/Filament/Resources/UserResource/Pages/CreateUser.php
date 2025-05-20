<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return trans('filament-users::user.resource.title.create');
    }

    public function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['is_active'])) {
            $data['email_verified_at'] = now();
        } else {
            $data['email_verified_at'] = null;
        }

        unset($data['is_active']);

        return $data;
    }
}
