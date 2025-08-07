<?php
/**
 * Head View
 */

?>
<!doctype html>
<html lang="en-US"<?= (!empty($vars->htmlClass) ? ' class="'.$vars->htmlClass.'"' : ''); ?>>

<head>
<title>Map Widget</title>
<meta name="description" content="Map Widget Admin">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5" />
<meta name="application-name" content="Map Widget Admin" />
<link rel="apple-touch-icon" sizes="76x76" href="<?= ASSETS_URI; ?>/favicons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?= ASSETS_URI; ?>/favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= ASSETS_URI; ?>/favicons/favicon-16x16.png">

<link rel="mask-icon" href="<?= ASSETS_URI; ?>/favicons/safari-pinned-tab.svg" color="#5bbad5">
<meta name="msapplication-TileColor" content="#da532c">
<meta name="theme-color" content="#000000">
<meta name="robots" content="noindex, nofollow" />
<link rel="stylesheet" id="main-css"  href="<?= ASSETS_URI; ?>/main.min.css?ver=<?= filemtime(dirname(__DIR__)); ?>" type="text/css" media="all" />

</head>
