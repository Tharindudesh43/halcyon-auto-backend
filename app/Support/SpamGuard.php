<?php

namespace App\Support;

use Illuminate\Http\Request;

class SpamGuard
{
    /**
     * Cheap bot checks for public forms (no third-party account needed):
     *  - "website" is a hidden field humans never see; bots fill it in.
     *  - "elapsed_ms" is how long the form was on screen; bots submit instantly.
     * Callers should answer a bot with a normal "Sent" response so it learns nothing.
     */
    public static function looksLikeBot(Request $request, int $minMs = 2000): bool
    {
        if (filled($request->input('website'))) {
            return true;
        }

        return (int) $request->input('elapsed_ms', 0) < $minMs;
    }

    /** The visitor's IP. The Next.js server forwards it as X-Client-IP, because Laravel only sees Next's address. */
    public static function clientIp(Request $request): string
    {
        return (string) ($request->header('X-Client-IP') ?: $request->ip());
    }
}
