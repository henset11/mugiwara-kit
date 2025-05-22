<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Validation\Rules\Password;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use App\Filament\Resources\UserResource\Pages;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class UserResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function getPermissionPrefixes(): array
    {
        return [
            ...config('filament-shield.permission_prefixes.resource'),
            'impersonate'
        ];
    }

    public static function getNavigationLabel(): string
    {
        return trans('filament-users::user.resource.label');
    }

    public static function getPluralLabel(): string
    {
        return trans('filament-users::user.resource.label');
    }

    public static function getLabel(): string
    {
        return trans('filament-users::user.resource.single');
    }

    public static function getNavigationGroup(): ?string
    {
        if (config('filament-users.shield')) {
            return __('filament-shield::filament-shield.nav.group');
        }

        return config('filament-users.group') ?: trans('filament-users::user.group');
    }

    public function getTitle(): string
    {
        return trans('filament-users::user.resource.title.resource');
    }

    public static function form(Form $form): Form
    {
        $rows = [
            TextInput::make('name')
                ->columnSpanFull()
                ->required()
                ->label(trans('filament-users::user.resource.name')),
            TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->label(trans('filament-users::user.resource.email')),
            TextInput::make('password')
                ->label(trans('filament-users::user.resource.password'))
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->required(fn($record) => ! $record)
                ->rule(Password::default())
                ->dehydrated(fn($state) => filled($state))
                ->dehydrateStateUsing(fn($state) => Hash::make($state))
                ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute')),
            FileUpload::make('avatar_url')
                ->columnSpanFull()
                ->image()
                ->disk('public')
                ->directory('profile-photos'),
            Toggle::make('is_active')
                ->onColor('success')
                ->label('Verifikasi Email'),
        ];


        if (config('filament-users.shield') && class_exists(\BezhanSalleh\FilamentShield\FilamentShield::class)) {
            $rows[] = Forms\Components\Select::make('roles')
                ->columnSpanFull()
                ->multiple()
                ->preload()
                ->relationship(
                    'roles',
                    'name',
                    fn($query) => auth()->user()->hasRole('super_admin')
                        ? $query
                        : $query->where('name', '!=', 'super_admin')
                )
                ->label(trans('filament-users::user.resource.roles'));
        }

        $form->schema($rows)->columns(2);

        return $form;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('User Details')
                    ->icon('heroicon-o-user')
                    ->description('User information details.')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('avatar_url')
                            ->visible(fn($record) => $record->avatar_url)
                            ->circular()
                            ->columnSpanFull()
                            ->label('User Avatar'),
                        TextEntry::make('name')
                            ->columnSpanFull()
                            ->label('Name'),
                        TextEntry::make('email'),
                        TextEntry::make('email_verified_at')
                            ->visible(fn($record) => $record->email_verified_at)
                            ->label('Email Verified')
                            ->date('d M Y, H:i:s'),
                        TextEntry::make('roles.name')
                            ->visible(fn($record) => $record->roles->isNotEmpty())
                            ->columnSpanFull()
                            ->badge()
                            ->icon('heroicon-o-shield-check')
                            ->color('success')
                            ->label(trans('filament-users::user.resource.roles'))
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            ImageColumn::make('avatar_url')
                ->label('')
                ->circular()
                ->state(fn($record) => $record->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($record->name)),
            TextColumn::make('name')
                ->sortable()
                ->searchable()
                ->label(trans('filament-users::user.resource.name')),
            TextColumn::make('email')
                ->sortable()
                ->searchable()
                ->label(trans('filament-users::user.resource.email')),
            IconColumn::make('email_verified_at')
                ->state(fn($record) => (bool) $record->email_verified_at)
                ->boolean()
                ->label(trans('filament-users::user.resource.email_verified_at'))
                ->toggleable(),
        ];

        if (config('filament-users.shield') && class_exists(\BezhanSalleh\FilamentShield\FilamentShield::class)) {
            $columns[] = TextColumn::make('roles.name')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->toggleable()
                ->badge()
                ->label(trans('filament-users::user.resource.roles'));
        }

        $table
            ->columns($columns)
            ->modifyQueryUsing(fn($query) =>
            auth()->user()->hasRole('super_admin')
                ? $query
                : $query->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin')))
            ->bulkActions(config('filament-users.resource.table.bulkActions')::make())
            ->checkIfRecordIsSelectableUsing(
                fn(User $record): bool => !$record->hasRole('super_admin')
            )
            ->filters(config('filament-users.resource.table.filters')::make())
            ->actions([
                ViewAction::make()
                    ->iconButton()
                    ->hidden(fn(User $record) =>
                    $record->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')),
                EditAction::make()->iconButton()
                    ->hidden(fn(User $record) =>
                    $record->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')),
                DeleteAction::make()
                    ->iconButton()
                    ->hidden(fn(User $record) =>
                    auth()->id() === $record->id ||
                        ($record->hasRole('super_admin') && !auth()->user()->hasRole('super_admin'))),
                Impersonate::make()
                    ->visible(auth()->user()->can('impersonate_user'))
                    ->redirectTo(fn() => filament()->getCurrentPanel()->getUrl()),
            ]);

        return $table;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
