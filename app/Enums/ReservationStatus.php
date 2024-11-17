<?

namespace App\Enums;

use Mokhosh\FilamentKanban\Concerns\IsKanbanStatus;

enum ReservationStatus: string
{
    use IsKanbanStatus;

    case Ongoing = 'ongoing';
    case InProgress = 'in_progress';
    case Done = 'done';
}