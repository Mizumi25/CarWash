<?php

namespace App\Filament\Resources\PaymentResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use App\Models\Payment;

class TotalIncomeWidget extends BaseWidget
{
    protected function getStats(): array
    {

        $todayTotal = Payment::whereDate('created_at', Carbon::today())->sum('amount');
        $yesterdayTotal = Payment::whereDate('created_at', Carbon::yesterday())->sum('amount');

        if ($yesterdayTotal > 0) {
            $percentageChange = (($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100;
        } else {
            $percentageChange = 0;
        }

        $chartData = [0, $yesterdayTotal, $todayTotal];

        $color = $percentageChange >= 0 ? 'success' : 'danger';

        return [
            Stat::make('Total Income', number_format($todayTotal, 2))
            -> description('Today\'s Income')
            ->chart($chartData)
            ->color($color),
        ];
    }
}
