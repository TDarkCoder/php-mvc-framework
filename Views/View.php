<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Views;

use TDarkCoder\Framework\Contracts\View as ViewContract;
use TDarkCoder\Framework\Exceptions\ViewNotFoundException;
use Throwable;

class View implements ViewContract
{
    private ?string $layout = null;

    public function exists(string $view): bool
    {
        return is_file($this->path($view));
    }

    public function layout(?string $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @throws ViewNotFoundException
     */
    public function render(string $view, array $params = []): string
    {
        $content = $this->renderFile($view, $params);

        if (is_null($this->layout)) {
            return $content;
        }

        return $this->renderFile("layouts/$this->layout", ['content' => $content] + $params);
    }

    private function path(string $view): string
    {
        $directory = rtrim(config('views.path') ?? basePath('/views'), '/');

        return $directory . '/' . str_replace('.', '/', $view) . '.php';
    }

    /**
     * Templates run inside a static closure so they cannot reach into the
     * View instance through $this.
     *
     * @throws ViewNotFoundException
     */
    private function renderFile(string $view, array $params): string
    {
        $file = $this->path($view);

        if (!is_file($file)) {
            throw new ViewNotFoundException("View [$view] not found");
        }

        $render = static function (string $__file, array $__params): string {
            ob_start();

            extract($__params, EXTR_SKIP);

            try {
                include $__file;
            } catch (Throwable $exception) {
                ob_end_clean();

                throw $exception;
            }

            return ob_get_clean();
        };

        return $render($file, $params);
    }
}
