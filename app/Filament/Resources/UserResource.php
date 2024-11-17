<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Forms\Components\FileUpload;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'fas-user';
    
    protected static ?string $navigationLabel = 'User';
    
    protected static ?string $modelLabel = 'Manage Users';
    
    protected static ?string $navigationGroup = 'Manage';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('profile_picture')->disk('public') ->directory('profile_pictures'),
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('email')->email()->required(),
                Forms\Components\TextInput::make('phone_number'),
                Forms\Components\TextInput::make('password')->default(Str::random(12))->required(),
                Forms\Components\Select::make('role')->label('User Role')->options([
                    'client' => 'Client',
                    'admin' => 'Admin',
                ])->placeholder(' ')
                ->required()
                ->default('client'), 
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('is_active')->sortable()->icon(function (bool $state): string
                {
                  return match ($state) {
                    true => 'heroicon-s-check-circle',
                    false => 'heroicon-s-x-circle'
                    };
                  })->color(function (bool $state): string {
                    return $state ? 'success' : 'gray'; 
                }),
                Tables\Columns\ImageColumn::make('profile_picture')->circular(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone_number')->searchable(),
                Tables\Columns\TextColumn::make('role')->badge()
                ->color(function (string $state): string
                {
                  return match ($state) {
                    'admin' => 'danger',
                    'client' => 'info'
                    };
                  }
                  
                  ),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
