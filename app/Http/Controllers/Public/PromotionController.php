<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $now = now();
        $promotions = Promotion::query()
            ->where('status', 'active')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->orderBy('end_at')
            ->orderBy('id')
            ->paginate(12);

        return view('public.promotions.index', [
            'promotions' => $promotions,
            'savedCodes' => array_values(array_filter((array) $request->session()->get('public_saved_promotion_codes', []), 'is_string')),
        ]);
    }

    public function save(Request $request, Promotion $promotion)
    {
        abort_unless($promotion->status === 'active' && $promotion->start_at->isPast() && $promotion->end_at->isFuture(), 404);

        $saved = array_values(array_unique(array_filter((array) $request->session()->get('public_saved_promotion_codes', []), 'is_string')));
        if (! in_array($promotion->code, $saved, true)) {
            $saved[] = $promotion->code;
        }
        $request->session()->put('public_saved_promotion_codes', array_slice($saved, -10));

        return back()->with('success', 'Đã lưu mã '.$promotion->code.' trong phiên trình duyệt này.');
    }
}
