<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function create(): View
    {
        return view('public.reservations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'regex:/^[0-9+().\s-]{9,20}$/'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:30'],
            'reservation_start_at' => ['required', 'date', 'after:now', 'before:+30 days'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $start = Carbon::parse($data['reservation_start_at']);
        $reservation = Reservation::create([
            ...$data,
            'reservation_start_at' => $start,
            'reservation_end_at' => $start->copy()->addHours(2),
        ]);
        $request->session()->put('last_reservation_id', $reservation->id);

        return redirect()->route('reservation.confirmation');
    }

    public function confirmation(Request $request): View
    {
        abort_unless($request->session()->has('last_reservation_id'), 404);

        return view('public.reservations.confirmation', ['reservation' => Reservation::findOrFail($request->session()->get('last_reservation_id'))]);
    }
}
