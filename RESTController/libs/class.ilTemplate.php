<?php

use \Slim\View as View;

/**
 *
 */
class ilTemplate extends View {
    /**
     *
     */
    protected function render($template, $data = null) {
        
        $templatePathname = $this->getTemplatePathname($template);
        if (!is_file($templatePathname)) {
            throw new \RuntimeException("View cannot render `$template` because the template does not exist");
        }

        $data = array_merge($this->data->all(), (array) $data);
        if (isset($data['templatePathname']);) {
            throw new \RuntimeException("View cannot render `$template` with given data because data contains illegal entry `templatePathname`");
        }
        
        extract($data);
        ob_start();
        require $templatePathname;
        $html = ob_get_clean();
        
        
        return parent::render('template\index.php', array(
            'html' => $html;
        ));
    }
}
