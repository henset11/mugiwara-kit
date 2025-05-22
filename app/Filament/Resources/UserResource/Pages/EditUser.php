<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Models\User;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;


    protected function beforeFill(): void
    {
        $record = $this->getRecord();
        abort_if(
            $record->hasRole('super_admin') && !auth()->user()->hasRole('super_admin'),
            403,
            'You do not have permission to edit super admin users.'
        );
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        if ($data['email_verified_at']) {
            $data['is_active'] = true;
        } else {
            $data['is_active'] = false;
        }
        unset($data['email_verified_at']);

        return $data;
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        $getUser = User::where('email', $data['email'])->first();
        if ($getUser) {
            if (empty($data['password'])) {
                $data['password'] = $getUser->password;
            }

            if (!$data['is_active'] && $getUser->email_verified_at) {
                $data['email_verified_at'] = null;
            } elseif ($data['is_active'] && !$getUser->email_verified_at) {
                $data['email_verified_at'] = now();
            }

            unset($data['is_active']);
        }

        return $data;
    }

    public function getTitle(): string
    {
        return trans('filament-users::user.resource.title.edit');
    }

    protected function getActions(): array
    {
        !config('filament-users.impersonate') ?: $ret[] = Impersonate::make()->record($this->getRecord())->visible(auth()->user()->can('impersonate_user'))->redirectTo(fn() => filament()->getCurrentPanel()->getUrl());
        $ret[] = DeleteAction::make()->hidden(fn(User $record) => auth()->id() === $record->id);

        return $ret;
    }
}
