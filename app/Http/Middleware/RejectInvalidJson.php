<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectInvalidJson
{
    /**
     * Answer "400 Bad Request" when a JSON request body cannot be parsed.
     *
     * Without this, Laravel treats a broken JSON body as an empty one, and the client
     * would get a misleading "422 field is required" error instead.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isJson() && $request->getContent() !== '') {
            json_decode($request->getContent());

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'message' => 'Malformed JSON in the request body: '.json_last_error_msg().'.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        return $next($request);
    }
}
