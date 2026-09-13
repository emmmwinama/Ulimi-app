<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Services\CalendarService;

final class CalendarController extends Controller
{
    public function __construct(private readonly CalendarService $calendar = new CalendarService())
    {
    }

    public function index(Request $request): Response
    {
        $farmId = FarmContext::current()->farmId();

        $year = (int) $request->query('year', date('Y'));
        $month = (int) $request->query('month', date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }

        $monthStartTs = (int) strtotime(sprintf('%04d-%02d-01', $year, $month));
        $prev = date('Y-m', strtotime('-1 month', $monthStartTs));
        $next = date('Y-m', strtotime('+1 month', $monthStartTs));

        return $this->view('calendar/index', [
            'title'      => 'Calendar',
            'active'     => 'calendar',
            'year'       => $year,
            'month'      => $month,
            'monthLabel' => date('F Y', $monthStartTs),
            'days'       => $this->calendar->forMonth($farmId, $year, $month),
            'prevYear'   => (int) substr($prev, 0, 4),
            'prevMonth'  => (int) substr($prev, 5, 2),
            'nextYear'   => (int) substr($next, 0, 4),
            'nextMonth'  => (int) substr($next, 5, 2),
            'todayIso'   => date('Y-m-d'),
        ]);
    }
}
