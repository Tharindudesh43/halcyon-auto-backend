<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if (SpamGuard::looksLikeBot($request)) {
            return response()->json(['message' => 'Sent']);
        }

        $row = fn (string $label, string $value) => '<tr>'
            .'<td style="padding:10px 0;width:110px;color:#6b7c85;font-size:14px;vertical-align:top;border-bottom:1px solid #e5eff0;">'.e($label).'</td>'
            .'<td style="padding:10px 0;color:#0a2530;font-size:15px;font-weight:600;vertical-align:top;border-bottom:1px solid #e5eff0;">'.$value.'</td>'
            .'</tr>';

        $rows = $row('Name', e($data['name']))
            .$row('Email', '<a href="mailto:'.e($data['email']).'" style="color:#0b7285;text-decoration:none;">'.e($data['email']).'</a>');

        // Buttons: call / WhatsApp only when a phone number was given
        $buttons = '<a href="mailto:'.e($data['email']).'" style="display:inline-block;background:#0b7285;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:12px 22px;border-radius:999px;margin:0 8px 8px 0;">Reply by email</a>';

        if (! empty($data['phone'])) {
            // Sri Lankan numbers: 0771234567 -> 94771234567 (what WhatsApp links need)
            $digits = preg_replace('/\D/', '', $data['phone']);
            if (str_starts_with($digits, '0')) {
                $digits = '94'.substr($digits, 1);
            }
            $waText = rawurlencode('Hi '.$data['name'].', thanks for contacting Halcyon Auto.');

            $rows .= $row('Phone', '<a href="tel:+'.e($digits).'" style="color:#0b7285;text-decoration:none;">'.e($data['phone']).'</a>');
            $buttons .= '<a href="https://wa.me/'.e($digits).'?text='.$waText.'" style="display:inline-block;background:#15803d;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:12px 22px;border-radius:999px;margin:0 0 8px 0;">Reply on WhatsApp</a>';
        }

        $html = '<div style="background:#f1f6f7;padding:24px 12px;font-family:Arial,Helvetica,sans-serif;">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;">'
            .'<tr><td style="background:#0b3c49;padding:22px 28px;">'
            .'<div style="color:#ffb703;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">Halcyon Auto</div>'
            .'<div style="color:#ffffff;font-size:22px;font-weight:bold;margin-top:4px;">New contact message</div>'
            .'</td></tr>'
            .'<tr><td style="padding:20px 28px 4px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">'.$rows.'</table></td></tr>'
            .'<tr><td style="padding:16px 28px 4px;">'
            .'<div style="font-size:13px;color:#6b7c85;margin-bottom:8px;">Message</div>'
            .'<div style="background:#f1f6f7;border-radius:12px;padding:16px 18px;font-size:15px;line-height:1.6;color:#0a2530;">'.nl2br(e($data['message'])).'</div>'
            .'</td></tr>'
            .'<tr><td style="padding:20px 28px 28px;">'.$buttons.'</td></tr>'
            .'<tr><td style="background:#f1f6f7;padding:14px 28px;font-size:12px;color:#6b7c85;">Sent from the Halcyon Auto website · '.e(now()->format('j M Y, g:i A')).'</td></tr>'
            .'</table></div>';

        $response = Http::withToken(config('services.resend.key'))
            ->post('https://api.resend.com/emails', [
                'from' => config('services.snippet.resend.from'),
                'to' => [config('services.snippet.resend.to')],
                'reply_to' => $data['email'],
                'subject' => 'Contact message from '.$data['name'],
                'html' => $html,
            ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Could not send message'], 502);
        }

        return response()->json(['message' => 'Sent']);
    }
}