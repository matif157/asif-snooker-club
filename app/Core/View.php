<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'app'): void
    {
        extract($data);

        ob_start();
        require ROOT_PATH . "/resources/views/{$template}.php";
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutPath = ROOT_PATH . "/resources/views/layouts/{$layout}.php";
        require $layoutPath;
    }

    public static function partial(string $template, array $data = []): string
    {
        extract($data);
        ob_start();
        require ROOT_PATH . "/resources/views/partials/{$template}.php";
        return ob_get_clean();
    }
}