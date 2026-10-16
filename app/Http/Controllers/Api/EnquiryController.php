<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\Enquiry;
use App\Support\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(Enquiry::TYPES)],
            'car_listing_id' => ['required', 'integer', 'exists:car_listings,id'],
            'name' => ['required_if:type,test_drive', 'nullable', 'string', 'max:120'],
            'phone' => ['required_if:type,test_drive', 'nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:1000'],
            'source_path' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['type'] === 'test_drive' && SpamGuard::looksLikeBot($request)) {
            return response()->json(['message' => 'Sent']);
        }

        $car = CarListing::find($data['car_listing_id']);

        $enquiry = Enquiry::create([
            ...$data,
            'car_title' => $car?->title,
        ]);

        if ($enquiry->type === 'test_drive') {
            $this->notify($enquiry);
        }

        return response()->json(['message' => 'Sent'], 201);
    }


    public function index(Request $request)
    {
        $query = Enquiry::query()
            ->with('car:id,slug,title,images')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->status === 'open', fn ($q) => $q->whereNull('handled_at'))
            ->when($request->status === 'handled', fn ($q) => $q->whereNotNull('handled_at'))
            ->latest();

        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);

        return response()->json($query->paginate($perPage));
    }


    public function summary(Request $request)
    {
        $days = min(max((int) $request->get('days', 30), 1), 365);
        $since = now()->subDays($days);

        $rows = Enquiry::query()
            ->whereNotNull('car_listing_id')
            ->where('created_at', '>=', $since)
            ->selectRaw("car_listing_id,
                COUNT(*) as total,
                SUM(CASE WHEN type = 'whatsapp' THEN 1 ELSE 0 END) as whatsapp,
                SUM(CASE WHEN type = 'call' THEN 1 ELSE 0 END) as calls,
                SUM(CASE WHEN type = 'test_drive' THEN 1 ELSE 0 END) as test_drives")
            ->groupBy('car_listing_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $cars = CarListing::whereIn('id', $rows->pluck('car_listing_id'))
            ->get(['id', 'slug', 'title', 'images', 'status'])
            ->keyBy('id');

        $top = $rows->map(fn ($r) => [
            'car_listing_id' => $r->car_listing_id,
            'title' => $cars[$r->car_listing_id]->title ?? 'Deleted car',
            'slug' => $cars[$r->car_listing_id]->slug ?? null,
            'image' => $cars[$r->car_listing_id]->images[0] ?? null,
            'status' => $cars[$r->car_listing_id]->status ?? null,
            'total' => (int) $r->total,
            'whatsapp' => (int) $r->whatsapp,
            'calls' => (int) $r->calls,
            'test_drives' => (int) $r->test_drives,
        ])->values();

        return response()->json([
            'days' => $days,
            'top' => $top,
            'totals' => [
                'whatsapp' => Enquiry::where('type', 'whatsapp')->where('created_at', '>=', $since)->count(),
                'calls' => Enquiry::where('type', 'call')->where('created_at', '>=', $since)->count(),
                'test_drives' => Enquiry::where('type', 'test_drive')->where('created_at', '>=', $since)->count(),
                'open_test_drives' => Enquiry::where('type', 'test_drive')->whereNull('handled_at')->count(),
            ],
        ]);
    }

    /** PATCH /api/enquiries/{enquiry} — admin only. Body: { handled: true|false } */
    public function update(Request $request, Enquiry $enquiry)
    {
        $data = $request->validate(['handled' => ['required', 'boolean']]);
        $enquiry->update(['handled_at' => $data['handled'] ? now() : null]);

        return response()->json($enquiry);
    }

    /** DELETE /api/enquiries/{enquiry} — admin only. */
    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /** Email the showroom about a new test-drive booking. A mail failure must never lose the enquiry. */
        /** Email the showroom about a new test-drive booking. A mail failure must never lose the enquiry. */
    private function notify(Enquiry $e): void
    {
        try {
            // Sri Lankan numbers: 0771234567 -> 94771234567 (what WhatsApp links need)
            $digits = preg_replace('/\D/', '', (string) $e->phone);
            if (str_starts_with($digits, '0')) {
                $digits = '94'.substr($digits, 1);
            }

            $row = fn (string $label, string $value) => '<tr>'
                .'<td style="padding:10px 0;width:130px;color:#6b7c85;font-size:14px;vertical-align:top;border-bottom:1px solid #e5eff0;">'.e($label).'</td>'
                .'<td style="padding:10px 0;color:#0a2530;font-size:15px;font-weight:600;vertical-align:top;border-bottom:1px solid #e5eff0;">'.$value.'</td>'
                .'</tr>';

            $rows = $row('Car', e($e->car_title ?? '—'))
                .$row('Name', e($e->name))
                .$row('Phone', '<a href="tel:+'.e($digits).'" style="color:#0b7285;text-decoration:none;">'.e($e->phone).'</a>')
                .($e->email ? $row('Email', '<a href="mailto:'.e($e->email).'" style="color:#0b7285;text-decoration:none;">'.e($e->email).'</a>') : '')
                .($e->preferred_date ? $row('Preferred date', e($e->preferred_date->format('D, j M Y'))) : '')
                .($e->message ? $row('Message', nl2br(e($e->message))) : '');

            $waText = rawurlencode('Hi '.$e->name.', thanks for booking a test drive of the '.($e->car_title ?? 'car').' at Halcyon Auto.');

            $html = '<div style="background:#f1f6f7;padding:24px 12px;font-family:Arial,Helvetica,sans-serif;">'
                .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;">'
                .'<tr><td style="background:#0b3c49;padding:22px 28px;">'
                .'<div style="color:#ffb703;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">Halcyon Auto</div>'
                .'<div style="color:#ffffff;font-size:22px;font-weight:bold;margin-top:4px;">New test-drive request</div>'
                .'</td></tr>'
                .'<tr><td style="padding:24px 28px 8px;">'
                .'<div style="font-size:13px;color:#6b7c85;">Someone wants to see</div>'
                .'<div style="font-size:20px;font-weight:bold;color:#0a2530;margin-top:2px;">'.e($e->car_title ?? 'a car').'</div>'
                .'</td></tr>'
                .'<tr><td style="padding:8px 28px 4px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">'.$rows.'</table></td></tr>'
                .'<tr><td style="padding:20px 28px 28px;">'
                .'<a href="tel:+'.e($digits).'" style="display:inline-block;background:#0b7285;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:12px 22px;border-radius:999px;margin:0 8px 8px 0;">Call '.e($e->name).'</a>'
                .'<a href="https://wa.me/'.e($digits).'?text='.$waText.'" style="display:inline-block;background:#15803d;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:12px 22px;border-radius:999px;margin:0 0 8px 0;">Reply on WhatsApp</a>'
                .'</td></tr>'
                .'<tr><td style="background:#f1f6f7;padding:14px 28px;font-size:12px;color:#6b7c85;">Sent from the Halcyon Auto website · '.e(now()->format('j M Y, g:i A')).'</td></tr>'
                .'</table></div>';

            Http::withToken(config('services.resend.key'))
                ->post('https://api.resend.com/emails', array_filter([
                    'from' => config('services.snippet.resend.from'),
                    'to' => [config('services.snippet.resend.to')],
                    'reply_to' => $e->email ?: null,
                    'subject' => 'Test drive: '.($e->car_title ?? 'a car').' — '.$e->name,
                    'html' => $html,
                ]));
        } catch (\Throwable $ex) {
            Log::warning('Test-drive email failed: '.$ex->getMessage());
        }
    }
}
