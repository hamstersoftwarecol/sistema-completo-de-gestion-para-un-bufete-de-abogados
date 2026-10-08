<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
use App\Models\Hearing;
use App\Models\LegalCase;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $q = trim((string) $request->input('q'));

        $results = ['clients' => collect(), 'cases' => collect(), 'documents' => collect(), 'hearings' => collect()];

        if (mb_strlen($q) >= 2) {
            $results['clients'] = Client::query()->visibleTo($user)->search($q)->withCount('cases')->limit(20)->get();
            $results['cases'] = LegalCase::query()->visibleTo($user)->search($q)->with(['client', 'status'])->limit(20)->get();
            $results['documents'] = Document::query()
                ->whereHas('legalCase', fn ($c) => $c->visibleTo($user))
                ->where(fn ($w) => $w->where('title', 'like', "%{$q}%")->orWhere('original_name', 'like', "%{$q}%"))
                ->with('legalCase')->limit(20)->get();
            $results['hearings'] = Hearing::query()->visibleTo($user)
                ->where(fn ($w) => $w->where('title', 'like', "%{$q}%")->orWhere('judge', 'like', "%{$q}%")->orWhere('location', 'like', "%{$q}%"))
                ->with('legalCase')->latest('scheduled_at')->limit(20)->get();
        }

        return view('search.index', compact('q', 'results'));
    }
}
