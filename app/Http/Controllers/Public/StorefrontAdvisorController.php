<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StorefrontAdvisorRequest;
use App\Services\StorefrontAdvisorService;
use Illuminate\Http\JsonResponse;

class StorefrontAdvisorController extends Controller
{
    public function reply(StorefrontAdvisorRequest $request, StorefrontAdvisorService $advisor): JsonResponse
    {
        return response()->json($advisor->reply($request->validated('message')));
    }
}
