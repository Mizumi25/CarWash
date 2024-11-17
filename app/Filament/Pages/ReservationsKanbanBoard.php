<?php

namespace App\Filament\Pages;

use App\Models\Reservation;
use Illuminate\Support\Collection;
use App\Enums\ReservationStatus;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Carbon\Carbon;

class ReservationsKanbanBoard extends KanbanBoard
{
    protected static string $model = Reservation::class;
    protected static string $statusEnum = ReservationStatus::class;
    
    protected static ?string $navigationLabel = 'Ongoing Reservations';
    
    protected static ?string $modelLabel = 'Ongoing Reservations';
    
    protected static ?int $navigationSort = 3;
    
    public static function getNavigationBadge(): ?string
    {
        return Reservation::getModel()::whereIn('status', ['ongoing', 'in_progress', 'done'])->count(); 
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
      return Reservation::getModel()::whereIn('status', ['ongoing', 'in_progress', 'done'])->count() > 10 ? 'warning' : 'success';
    }
    
    protected static ?string $navigationIcon = 'fas-bars-progress';
    
    public bool $disableEditModal = true;
    
    protected static string $recordTitleAttribute = 'id';
    
    protected static string $recordStatusAttribute = 'status';

    protected static string $headerView = 'reservation-kanban.kanban-header';
    
    protected static string $recordView = 'reservation-kanban.kanban-record';
    
    protected static string $statusView = 'reservation-kanban.kanban-status';
    
    protected function records(): Collection
    {
        return Reservation::with('service')->whereIn('status', [
                ReservationStatus::Ongoing->value,
                ReservationStatus::InProgress->value,
                ReservationStatus::Done->value,
            ])
            ->get()
            ->map(function ($record) {
                return $record->setAttribute(
                    'additional_data',
                    $this->additionalRecordData($record)->toArray()
                );
            });
    }

    
    public function onStatusChanged(int $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {
        $reservation = Reservation::with('schedule')->find($recordId);
    
        if ($status === 'done') {
            $schedule = $reservation->schedule;
            $schedule->update([
                'end_time' => Carbon::now(),
            ]);
        } elseif (in_array($status, ['ongoing', 'pending'])) {
            $schedule = $reservation->schedule;
            $schedule->update([
                'end_time' => null,  
            ]);
        }
    
        $reservation->update([
            'status' => $status,
        ]);
    }



    
    public function onSortChanged(int $recordId, string $status, array $orderedIds): void
    {
        Reservation::find($recordId)->update(['status' => $status]);
    }
    
    
    protected function additionalRecordData(Reservation $record): Collection
    {
        $statusProgressMapping = [
            'pending' => 20,
            'approve' => 40,
            'ongoing' => 60,
            'in_progress' => 80,
            'done' => 100,
        ];
    
        $progress = $statusProgressMapping[$record->status] ?? 0;
        
        
    
        return collect([
            'progress' => $progress,
            'services' => $record->service->service_name,
            'names' => $record->user->name,
            'profile_pictures' => $record->user->profile_picture,
            'vehicles' => $record->vehicleTypeName,
            'status' => $record->status,
            'payment_statuss' => $record->payment->payment_status,
            'amounts' => $record->payment->amount,
            'schedule_date' => $record->schedule->date,
            'schedule_time' => $record->schedule->time_slot,
            'end_times' => $record->schedule->end_time,
        ]);
    }

    
}
