<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleAjaxFormResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only transform redirect responses when the request was initiated by our CRM AJAX form utility
        if ($request->hasHeader('X-CRM-Form-Ajax') && $response instanceof RedirectResponse) {
            $session = $response->getSession();

            // 1. If controller manually redirected back with a MessageBag of errors
            if ($session && $session->has('errors')) {
                $errorBag = $session->get('errors');
                $bag = method_exists($errorBag, 'getBag') ? $errorBag->getBag('default') : $errorBag;
                $errors = method_exists($bag, 'toArray') ? $bag->toArray() : (array) $bag;

                return new JsonResponse([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            // 2. If controller redirected back with an error flash message
            $errorMessage = $session ? $session->get('error') : null;
            if ($errorMessage !== null && $errorMessage !== '') {
                return new JsonResponse([
                    'success' => false,
                    'message' => (string) $errorMessage,
                ], 422);
            }

            // 3. Successful redirect: extract success flash and target URL
            $successMessage = $session
                ? ($session->get('success') ?? $session->get('status') ?? $session->get('info'))
                : null;

            // Retain flash data so when client navigates to the redirect URL, the flash message is displayed
            $session?->reflash();

            return new JsonResponse([
                'success' => true,
                'redirect' => $response->getTargetUrl(),
                'message' => $successMessage ? (string) $successMessage : 'Operation completed successfully.',
            ], 200);
        }

        return $response;
    }
}
