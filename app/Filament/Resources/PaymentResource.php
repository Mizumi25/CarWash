<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'fas-wallet';
    
    protected static ?string $navigationLabel = 'Payments';
    
    protected static ?string $modelLabel = 'Payments';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $recordTitleAttribute = 'reservation.user.name';
    
    // public static function getGlobalSearchResultTitle(Model $record): string
    // {
    //   return $record->user->name;
    // }
    
    // public static function getGloballySearchableAttributes(): array
    // {
    //     return ['reservation.user.name', 'reservation.service.service_name', 'amount', 'payment_method', 'payment_status'];
    // }
    
    // public static function getGlobalSearchResultDetails(Model $record): array
    // {
    //     return [
    //         'Payment Date' => $record->created_at,
    //         'Amount' => $record->amount, 
    //     ];
    // }
    
    // public static function getGlobalSearchEloquentQuery(): Builder
    // {
    //     return parent::getGlobalSearchEloquentQuery()->with(['user', 'service', 'payment', 'reservation.vehicle.vehicleType']);
    // }
    
    public static function getNavigationBadge(): ?string
    {
      return static::getModel()::count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
      return static::getModel()::count() > 10 ? 'warning' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Payment ID')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('reservation.user.name')->label('User Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('reservation.service.service_name')->label('Service')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('reservation.vehicle_type_name')->label('Vehicle Type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('payment_status')->badge()
                ->color(function (string $state): string
                {
                  return match ($state) {
                    'partialy_paid' => 'info',
                    'fully_paid' => 'success',
                    'not_paid' => 'warning',
                    };
                  }
                 ),
                Tables\Columns\TextColumn::make('payment_method')->label('Payment Method')->searchable()->sortable(), 
                Tables\Columns\TextColumn::make('amount')->label('Total Amount')->prefix('P')->searchable()->sortable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array {
        return [
            PaymentResource\Widgets\TotalIncomeWidget::class,
        ];
    }
}
