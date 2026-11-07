<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Film;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class FilmController extends Controller
{
    /**
     * Aturan validasi dipakai bersama oleh store() dan update().
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'director' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'between:1888,'.(date('Y') + 5)],
            'status' => ['required', Rule::in(Film::STATUSES)],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** GET /api/films */
    public function index(): JsonResponse
    {
        return response()->json(Film::latest()->get());
    }

    /** POST /api/films */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $film = Film::create($validated);

        Log::info('Film created', [
            'event' => 'film.created',
            'actor_id' => $request->user()?->id,
            'action' => 'create film',
            'film_id' => $film->id,
            'fields' => array_keys($validated),
            'reason' => 'valid film data submitted',
        ]);

        return response()->json($film, 201);
    }

    /** GET /api/films/{film} */
    public function show(Film $film): JsonResponse
    {
        return response()->json($film);
    }

    /** PUT/PATCH /api/films/{film} */
    public function update(Request $request, Film $film): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $film->update($validated);

        Log::info('Film updated', [
            'event' => 'film.updated',
            'actor_id' => $request->user()?->id,
            'action' => 'update film',
            'film_id' => $film->id,
            'fields' => array_keys($validated),
            'reason' => 'valid film changes submitted',
        ]);

        return response()->json($film);
    }

    /** DELETE /api/films/{film} */
    public function destroy(Film $film): JsonResponse
    {
        $filmId = $film->id;
        $film->delete();

        Log::info('Film deleted', [
            'event' => 'film.deleted',
            'actor_id' => request()->user()?->id,
            'action' => 'delete film',
            'film_id' => $filmId,
            'reason' => 'user requested deletion',
        ]);

        return response()->json(['message' => 'Film berhasil dihapus.']);
    }
}
