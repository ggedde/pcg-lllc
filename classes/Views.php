<?php
/**
 * This file is to handle View Templates
 */

namespace MapWidget;

/**
 * Class for managing and rendering views
 */
class Views
{
    /**
     * Check whether current user is logged in and is an Administrator
     *
     * @param string $template view file located in the views folder minus the "views-" prefix and ".php" extension.
     * @param array  $vars     array of variables to be extracted to the templates.
     *
     * @return string|void
     */
    public static function render($template, $vars = array())
    {
        $tempateFile = VIEWS_PATH.'/'.$template.'.php';

        if (!file_exists($tempateFile)) {
            die('Error - Missing Template file');
        }

        $vars = (object) $vars;

        require $tempateFile;
    }
}
