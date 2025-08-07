<?php
/**
 * Environment Variables
 */

// Get Environment Variables
if (defined('ENV_FILE') && !empty(ENV_FILE) && file_exists(ENV_FILE)) {
    foreach (parse_ini_file(ENV_FILE) as $envVarKey => $envVarValue) {
        putenv($envVarKey.'='.$envVarValue);
    }
}
