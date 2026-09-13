<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\SeasonalTemplates;

final class SeasonalTemplatesController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('templates/index', [
            'title'     => 'Seasonal templates',
            'active'    => 'templates',
            'templates' => SeasonalTemplates::all(),
        ]);
    }

    public function show(Request $request): Response
    {
        $template = SeasonalTemplates::find((string) $request->route('id'));
        if ($template === null) {
            return $this->redirect(url('templates'));
        }
        return $this->view('templates/show', [
            'title'    => $template['name'],
            'active'   => 'templates',
            'template' => $template,
        ]);
    }
}
