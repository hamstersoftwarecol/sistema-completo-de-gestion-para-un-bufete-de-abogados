<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\HearingStatus;
use App\Models\Appointment;
use App\Models\Hearing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Calendario judicial: audiencias + citas en un solo calendario.
 */
class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar.index', [
            'lawyers' => User::query()->lawyers()->get(),
        ]);
    }

    public function events(Request $request)
    {
        $user = $request->user();
        $start = Carbon::parse($request->input('start', now()->startOfMonth()));
        $end = Carbon::parse($request->input('end', now()->endOfMonth()));
        $lawyer = $request->integer('lawyer') ?: null;
        $show = $request->input('show', 'all');

        $events = collect();

        if ($show !== 'appointments') {
            $hearings = Hearing::query()->visibleTo($user)
                ->with(['legalCase.client', 'user'])
                ->whereBetween('scheduled_at', [$start, $end])
                ->when($lawyer, fn ($q) => $q->where('user_id', $lawyer))
                ->get();

            foreach ($hearings as $h) {
                $color = match ($h->status) {
                    HearingStatus::Scheduled => '#e11d48',
                    HearingStatus::Held => '#16a34a',
                    HearingStatus::Postponed => '#d97706',
                    HearingStatus::Cancelled => '#9ca3af',
                };

                $events->push([
                    'id' => 'h'.$h->id,
                    'title' => '⚖ '.$h->title.' · '.$h->legalCase?->case_number,
                    'start' => $h->scheduled_at->toIso8601String(),
                    'end' => $h->ends_at->toIso8601String(),
                    'url' => route('hearings.edit', $h),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'kind' => 'Audiencia',
                        'status' => $h->status->label(),
                        'client' => $h->legalCase?->client?->name,
                        'location' => $h->location,
                        'lawyer' => $h->user?->name,
                    ],
                ]);
            }
        }

        if ($show !== 'hearings') {
            $appointments = Appointment::query()->visibleTo($user)
                ->with(['client', 'user'])
                ->whereBetween('starts_at', [$start, $end])
                ->when($lawyer, fn ($q) => $q->where('user_id', $lawyer))
                ->get();

            foreach ($appointments as $a) {
                $color = match ($a->status) {
                    AppointmentStatus::Cancelled, AppointmentStatus::NoShow => '#9ca3af',
                    AppointmentStatus::Completed => '#0d9488',
                    default => '#4f46e5',
                };

                $events->push([
                    'id' => 'a'.$a->id,
                    'title' => '👤 '.$a->title.' · '.$a->who,
                    'start' => $a->starts_at->toIso8601String(),
                    'end' => $a->ends_at->toIso8601String(),
                    'url' => route('appointments.edit', $a),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'kind' => 'Cita',
                        'status' => $a->status->label(),
                        'client' => $a->who,
                        'location' => $a->location ?: $a->mode->label(),
                        'lawyer' => $a->user?->name,
                    ],
                ]);
            }
        }

        return response()->json($events->values());
    }
}
