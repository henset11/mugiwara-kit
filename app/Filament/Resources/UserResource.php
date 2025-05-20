<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Illuminate\Validation\Rules\Password;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use App\Filament\Resources\UserResource\Pages;
use TomatoPHP\FilamentUsers\Resources\UserResource\Table\UserActions;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-user';

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
                ->relationship('roles', 'name')
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
                ->circular(),
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
                ->sortable()
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
            ->bulkActions(config('filament-users.resource.table.bulkActions')::make())
            ->filters(config('filament-users.resource.table.filters')::make())
            ->actions(UserActions::make());

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
