<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    private const LIST_COLUMNS = [
        'id', 'title', 'slug', 'category', 'excerpt', 'cover_image', 'author',
        'is_published', 'published_at', 'created_at', 'updated_at',
    ];

    private function published()
    {
        return Article::query()
            ->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function index(Request $request)
    {
        $query = $this->published()->select(self::LIST_COLUMNS)
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('excerpt', 'like', $term));
            })
            ->orderByDesc('published_at')->orderByDesc('id');

        $perPage = min(max((int) $request->get('per_page', 9), 1), 24);

        return response()->json($query->paginate($perPage));
    }

    public function show(string $slug)
    {
        return response()->json($this->published()->where('slug', $slug)->firstOrFail());
    }

    public function adminIndex()
    {
        return response()->json(Article::query()->select(self::LIST_COLUMNS)->latest()->get());
    }

    public function adminShow(Article $article)
    {
        return response()->json($article);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['title']);
        $data['published_at'] = ($data['is_published'] ?? false) ? now() : null;

        return response()->json(Article::create($data), 201);
    }

    public function update(Request $request, Article $article)
    {
        $data = $this->validated($request, partial: $request->isMethod('PATCH'));

        if (array_key_exists('slug', $data)) {
            $data['slug'] = empty($data['slug'])
                ? $article->slug
                : $this->uniqueSlug($data['slug'], $article->title, $article->id);
        }

        if (($data['is_published'] ?? $article->is_published) && ! $article->published_at) {
            $data['published_at'] = now();
        }

        $article->update($data);

        return response()->json($article);
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'category' => ['required', Rule::in(Article::CATEGORIES)],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'content' => ['required', 'string', 'max:100000'],
            'cover_image' => ['nullable', 'string', 'max:2048'],
            'author' => ['nullable', 'string', 'max:100'],
            'is_published' => ['required', 'boolean'],
        ];

        if ($partial) {
            $rules = array_map(fn ($r) => array_merge(['sometimes'], $r), $rules);
        }

        return $request->validate($rules);
    }

    private function uniqueSlug(?string $wanted, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($wanted ?: $title) ?: 'article-'.Str::lower(Str::random(6)); // e.g. Sinhala-only titles
        $slug = $base;
        $n = 2;

        while (Article::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
