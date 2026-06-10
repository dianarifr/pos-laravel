<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use App\Filament\Resources\UserResource\Pages;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Manajemen';
    protected static ?string $navigationLabel = 'User';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->required(),
            Forms\Components\TextInput::make('email')
                ->label('Username')
                ->unique(ignoreRecord: true)
                ->required()
                ->readonly(fn (string $context): bool => $context === 'edit'),
            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->required(fn (string $context): bool => $context === 'create')
                ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                ->dehydrated(fn ($state) => filled($state))
                ->minLength(8)
                ->maxLength(255)
                ->placeholder(fn (string $context): string => $context === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : ''),
            Forms\Components\Textarea::make('address')
                ->label('Alamat'),
            Forms\Components\Select::make('roles')
                ->label('Role Akses')
                ->relationship('roles', 'name')
                ->preload()
                ->required(),
            Forms\Components\FileUpload::make('profile_picture')
                ->label('Profile Picture')
                ->image()
                ->imageEditor()
                ->circleCropper()
                ->directory('profile-pictures')
                ->maxSize(1024),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
            Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
            Tables\Columns\TextColumn::make('roles.name')
            ->label('Role')
            ->badge()
            ->color(fn (string $state): string => match ($state) {
                'Admin' => 'danger',
                'Owner' => 'success',
                default => 'gray',
            }),
            Tables\Columns\ImageColumn::make('profile_picture')->label('Foto')->circular(),
            Tables\Columns\TextColumn::make('address')->label('Alamat')->limit(20),
        ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ]);
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
