<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

/**
 * Shared controller conveniences. Deliberately thin.
 */
abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::view($template, $data, $status);
    }

    protected function redirect(string $to, int $status = 302): Response
    {
        return Response::redirect($to, $status);
    }

    protected function back(Request $request, string $fallback = '/'): Response
    {
        $ref = $request->header('Referer');
        $target = ($ref !== null && $this->isSameHost($ref)) ? $ref : url($fallback);
        return Response::redirect($target);
    }

    /**
     * Run validation. On success returns the validated data array. On failure
     * flashes errors + old input and returns a redirect Response — callers do:
     *
     *   $data = $this->validate($request, [...]);
     *   if ($data instanceof Response) return $data;
     *
     * @param array<string,list<string>> $rules
     * @param array<string,string> $messages
     * @return array<string,mixed>|Response
     */
    protected function validate(Request $request, array $rules, array $messages = []): array|Response
    {
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->passes()) {
            return $validator->validated();
        }

        $session = Session::instance();
        $session->flash('errors', $validator->errors());
        $session->flashInput($request->all());
        Flash::error($validator->firstError() ?? 'Please fix the highlighted fields.');

        return $this->back($request, $request->path);
    }

    /** Flash a single field error and bounce back (for checks outside Validator). */
    protected function fieldError(Request $request, string $field, string $message): Response
    {
        $session = Session::instance();
        $session->flash('errors', [$field => [$message]]);
        $session->flashInput($request->all());
        Flash::error($message);
        return $this->back($request, $request->path);
    }

    private function isSameHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host === null || $host === ($_SERVER['HTTP_HOST'] ?? null);
    }
}
