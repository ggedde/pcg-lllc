<?php
/**
 * This file is to handle Users
 */

namespace MapWidget;

/**
 * Class for managing and rendering users
 */
class User
{
    /**
     * Login Error
     *
     * @var array $loginError
     */
    public static $loginError = '';

    /**
     * Check if the user is logged in.
     *
     * @return bool
     */
    public static function init()
    {
        if (isset($_POST['username']) || isset($_POST['password'])) {
            if ($_POST['username'] === getenv('USER_NAME') && $_POST['password'] === getenv('USER_PASS')) {
                $cookieValue = md5(getenv('USER_NAME').getenv('USER_PASS'));
                setcookie('admin', $cookieValue, time() + 3600, ROOT_URI, $_SERVER['HTTP_HOST'], true, true);
                $_COOKIE['admin'] = $cookieValue;
                header("Location: ".ROOT_URI);
                exit;
            } else {
                self::$loginError = 'Invalid Username and Password. Please Try Again.';
            }
        }

        if (!empty($_GET['page']) && $_GET['page'] === 'logout') {
            setcookie('admin', '', time() + 3600, ROOT_URI, $_SERVER['HTTP_HOST'], true, true);
            $_COOKIE['admin'] = '';
            header("Location: ".ROOT_URI);
            exit;
        }
    }

    /**
     * Check if the user is logged in.
     *
     * @return bool
     */
    public static function isLoggedIn()
    {
        return (!empty($_COOKIE['admin']) && $_COOKIE['admin'] === md5(getenv('USER_NAME').getenv('USER_PASS')));
    }
}
