<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use Illuminate\Http\Request;

class CarListingController extends Controller
{

    public function index(Request $request)
    {
        $query = CarListing::query();

        $query->when($request->filled('brand'), fn ($q) => $q->where('brand', $request->brand))
            ->when($request->filled('transmission'), fn ($q) => $q->where('transmission', $request->transmission))
            ->when($request->filled('power_source'), fn ($q) => $q->where('power_source', $request->power_source))
            ->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', $request->min_price))
            ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', $request->max_price))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'));

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $perPage = min(max((int) $request->get('per_page', 12), 1), 100);

        return response()->json($query->paginate($perPage));
    }


    public function show(string $key)
    {
        $listing = CarListing::where('slug', $key)->first()
            ?? (ctype_digit($key) ? CarListing::findOrFail($key) : abort(404));

        return response()->json($listing);
    }


    public function store(Request $request)
    {
        $data = $this->validated($request);
        $listing = CarListing::create($data);

        return response()->json($listing, 201);
    }


    public function update(Request $request, CarListing $carListing)
    {
        $data = $this->validated($request);
        $carListing->update($data);

        return response()->json($carListing);
    }


    public function updateStatus(Request $request, CarListing $carListing)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,sold'],
        ]);

        $carListing->update($data);

        return response()->json($carListing);
    }


    public function updateDiscount(Request $request, CarListing $carListing)
    {
        $data = $request->validate([
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:90'],
        ]);

        $carListing->update([
            'discount_percent' => empty($data['discount_percent']) ? null : $data['discount_percent'],
        ]);

        return response()->json($carListing);
    }

    public function destroy(CarListing $carListing)
    {
        $carListing->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:90'],
            'down_payment' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'mileage' => ['nullable', 'string', 'max:255'],
            'cc' => ['nullable', 'string', 'max:255'],
            'power_source' => ['nullable', 'string', 'max:255'],
            'transmission' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['string'],
            'status' => ['nullable', 'in:pending,sold'],
        ]);
    }
}
