<?php

/**
 * @package ThemePlate
 * @since   0.1.0
 */

namespace CardanoPress\Traits;

use CardanoPress\Foundation\AbstractTemplates;

trait Templatable
{
    protected AbstractTemplates $templates;

    protected function setTemplates(AbstractTemplates $templates): void
    {
        $this->templates = $templates;
    }

    protected function getTemplates(): AbstractTemplates
    {
        return $this->templates;
    }

    public function template(string $name, array $variables = []): void
    {
        $name .= '.php';
        $file = locate_template($this->templates->getOverridesPrefix() . $name);

        if (! $file) {
            $file = $this->templates->getPath() . $name;
        }

        if (file_exists($file)) {
            extract($variables, EXTR_OVERWRITE);
            include $file;
        }
    }

    public function compatibleHeader(): void
    {
        if (! wp_is_block_theme()) {
            get_header();
        } else {
            echo wp_kses_post('<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->');
        }
    }

    public function compatibleFooter(): void
    {
        if (! wp_is_block_theme()) {
            get_footer();
        } else {
            echo wp_kses_post('<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->');
        }
    }
}
