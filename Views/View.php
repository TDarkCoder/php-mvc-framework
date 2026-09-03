<?php

namespace TDarkCoder\Framework\Views;

class View implements ViewContract
{
    private ?string $layout = null;

    public function layout(?string $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function render(string $view, array $params = []): string
    {
        $content = $this->renderFile(basePath("/views/$view.php"), $params);

        if (is_null($this->layout)) {
            return $content;
        }

        return $this->renderFile(basePath("/views/layouts/$this->layout.php"), ['content' => $content] + $params);
    }

    private function renderFile(string $__file, array $__params): string
    {
        ob_start();

        extract($__params, EXTR_SKIP);

        include $__file;

        return ob_get_clean();
    }
}
