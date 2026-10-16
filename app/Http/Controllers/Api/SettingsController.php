<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public const GROUPS = ['hero', 'contact', 'about', 'trust', 'location', 'popup', 'finance'];

    public function index()
    {
        $settings = SiteSetting::all()->mapWithKeys(fn ($s) => [$s->key => $s->value])->all();

        return response()->json((object) $settings);
    }


    public function update(Request $request, string $group)
    {
        abort_unless(in_array($group, self::GROUPS, true), 404);

        $data = $request->validate($this->rules($group));

        $setting = SiteSetting::updateOrCreate(['key' => $group], ['value' => $data]);

        return response()->json($setting->value);
    }

    private function rules(string $group): array
    {
        return match ($group) {
            'hero' => [
                'image' => ['nullable', 'string', 'max:2048'],
                'media_desktop' => ['nullable', 'array'],
                'media_desktop.type' => ['required_with:media_desktop', 'in:image,video'],
                'media_desktop.url' => ['required_with:media_desktop', 'string', 'max:2048'],
                'media_desktop.focus' => ['nullable', 'in:center,top,bottom,left,right'],
                'media_mobile' => ['nullable', 'array'],
                'media_mobile.type' => ['required_with:media_mobile', 'in:image,video'],
                'media_mobile.url' => ['required_with:media_mobile', 'string', 'max:2048'],
                'media_mobile.focus' => ['nullable', 'in:center,top,bottom,left,right'],
                'title' => ['required', 'string', 'max:100'],
                'accent' => ['nullable', 'string', 'max:100'],
                'subtitle' => ['nullable', 'string', 'max:300'],
            ],
            'contact' => [
                'heading' => ['nullable', 'string', 'max:120'],
                'intro' => ['nullable', 'string', 'max:500'],
                'owner_name' => ['nullable', 'string', 'max:120'],
                'phone' => ['nullable', 'string', 'max:50'],
                'whatsapp' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
                'hours' => ['nullable', 'string', 'max:255'],
                'facebook' => ['nullable', 'url', 'max:2048'],
                'instagram' => ['nullable', 'url', 'max:2048'],
                'tiktok' => ['nullable', 'url', 'max:2048'],
            ],
            'about' => [
                'eyebrow' => ['nullable', 'string', 'max:80'],
                'title' => ['required', 'string', 'max:120'],
                'body' => ['nullable', 'string', 'max:6000'],
                'image' => ['nullable', 'string', 'max:2048'],
            ],
            'location' => [
                'name' => ['nullable', 'string', 'max:150'],
                'address' => ['nullable', 'string', 'max:255'],
                'map_url' => ['nullable', 'url', 'max:2048'],
                'map_query' => ['nullable', 'string', 'max:255'],
                'directions_url' => ['nullable', 'url', 'max:2048'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ],
            'popup' => [
                'enabled' => ['required', 'boolean'],
                'image' => ['nullable', 'string', 'max:2048'],
                'link' => ['nullable', 'url', 'max:2048'],
                'alt' => ['nullable', 'string', 'max:150'],
                'version' => ['nullable', 'integer'],
            ],
            'finance' => [
                'default_rate' => ['required', 'numeric', 'min:0', 'max:60'],
                'rate_type' => ['required', 'in:reducing,flat'],
                'default_years' => ['required', 'integer', 'min:1', 'max:10'],
                'max_years' => ['required', 'integer', 'min:1', 'max:10'],
                'ltv_used' => ['required', 'integer', 'min:10', 'max:100'],
                'ltv_new' => ['required', 'integer', 'min:10', 'max:100'],
                'note' => ['nullable', 'string', 'max:500'],
            ],
            'trust' => [
                'eyebrow' => ['nullable', 'string', 'max:80'],
                'title' => ['required', 'string', 'max:120'],
                'subtitle' => ['nullable', 'string', 'max:400'],
                'points' => ['required', 'array', 'min:1', 'max:6'],
                'points.*.icon' => ['required', 'in:shield,trending,users,star,award,thumbs'],
                'points.*.title' => ['required', 'string', 'max:80'],
                'points.*.desc' => ['required', 'string', 'max:300'],
            ],
        };
    }
}
