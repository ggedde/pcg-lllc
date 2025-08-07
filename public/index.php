<?php
/**
 * Index file
 * This file controls the entire App.
 */

namespace MapWidget;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!empty($_SERVER['HTTP_HOST']) && ((empty($_SERVER['HTTPS']) && getenv('FORCE_HTTPS')) || count(explode('.', $_SERVER['HTTP_HOST'])) < 3)) {
    header('Location: '.( getenv('FORCE_HTTPS') ? 'https' : 'http' ).'://'.(count(explode('.', $_SERVER['HTTP_HOST'])) < 3 ? 'www.' : '').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    exit;
}

require_once dirname(__DIR__).'/inc/def.php';
require_once dirname(__DIR__).'/inc/env.php';
require_once dirname(__DIR__).'/inc/classes.php';

\MapWidget\Ajax::checkAndRun();

if (!empty($_GET['page']) && $_GET['page'] === 'map') {
    Views::render('head', [
        'htmlClass' => 'map-html',
    ]);

    $categoryColors = Database::getSetting('category_colors');

    Views::render('map', [
        'entries' => Database::getAll(true),
        'googleApiKey' => Database::getSetting('google_api_key'),
        'categoryColors' => !empty($categoryColors) ? json_decode($categoryColors) : (object) [],
    ]);
    exit;
}

if (!empty($_GET['page']) && $_GET['page'] === 'download') {
    if (User::isLoggedIn()) {
        Database::downloadAll();
        exit;
    } else {
        Views::render('head');
        Views::render('login');
    }
}

if (!empty($_GET['page']) && $_GET['page'] === 'download-example') {
    if (User::isLoggedIn()) {
        Database::downloadAll(true);
        exit;
    } else {
        Views::render('head');
        Views::render('login');
    }
}

Views::render('head');

if (User::isLoggedIn()) {
    Views::render('admin');
} else {
    Views::render('login');
}
