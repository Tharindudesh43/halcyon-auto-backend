<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        return response()->json(
            Faq::query()->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function adminIndex()
    {
        return response()->json(Faq::query()->orderBy('sort_order')->orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (int) Faq::max('sort_order') + 1;

        return response()->json(Faq::create($data), 201);
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($this->validated($request, partial: $request->isMethod('PATCH')));

        return response()->json($faq);
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer', 'exists:faqs,id'],
        ]);

        foreach ($data['ids'] as $position => $id) {
            Faq::whereKey($id)->update(['sort_order' => $position + 1]);
        }

        return response()->json(['message' => 'Reordered']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:4000'],
            'category' => ['nullable', 'string', 'max:60'],
            'is_published' => ['required', 'boolean'],
        ];

        if ($partial) {
            $rules = array_map(fn ($r) => array_merge(['sometimes'], $r), $rules);
        }

        return $request->validate($rules);
    }
}
