<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareerOpening;
use Illuminate\Http\Request;

class CareerController extends Controller
{
    
    public function index()
    {
        $openings = CareerOpening::query()
            ->where('is_open', true)
            ->where(fn ($q) => $q->whereNull('closing_date')->orWhere('closing_date', '>=', now()->toDateString()))
            ->latest()
            ->get();

        return response()->json($openings);
    }


    public function adminIndex()
    {
        return response()->json(CareerOpening::query()->latest()->get());
    }


    public function show(CareerOpening $careerOpening)
    {
        return response()->json($careerOpening);
    }

    public function store(Request $request)
    {
        $opening = CareerOpening::create($this->validated($request));

        return response()->json($opening, 201);
    }

    public function update(Request $request, CareerOpening $careerOpening)
    {
        $careerOpening->update($this->validated($request, partial: $request->isMethod('PATCH')));

        return response()->json($careerOpening);
    }

    public function destroy(CareerOpening $careerOpening)
    {
        $careerOpening->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:150'],
            'type' => ['nullable', 'in:Full-time,Part-time,Contract,Internship'],
            'salary' => ['nullable', 'string', 'max:100'],
            'closing_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:8000'],
            'requirements' => ['nullable', 'string', 'max:4000'],
            'is_open' => ['required', 'boolean'],
        ];

        if ($partial) {
            $rules = array_map(fn ($r) => array_merge(['sometimes'], $r), $rules);
        }

        return $request->validate($rules);
    }
}
