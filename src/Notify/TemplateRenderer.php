<?php

declare(strict_types=1);

namespace Shabstagram\Notify;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

/**
 * Rendu des gabarits Mustache (courriels et partiels partagés avec les
 * pages HTML). Aucune logique de présentation n'est écrite en PHP.
 */
final class TemplateRenderer
{
    private Mustache_Engine $mustache;

    public function __construct(string $templatesPath)
    {
        $this->mustache = new Mustache_Engine([
            'loader' => new Mustache_Loader_FilesystemLoader($templatesPath, ['extension' => '.mustache']),
            'partials_loader' => new Mustache_Loader_FilesystemLoader($templatesPath . '/partials', ['extension' => '.mustache']),
        ]);
    }

    public function render(string $templateName, array $data): string
    {
        return $this->mustache->render($templateName, $data);
    }
}
