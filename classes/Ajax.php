<?php

/**
 * This file is to handle Ajax Functionality
 */

namespace MapWidget;

/**
 * Class for managing and rendering Ajax Functionality
 */
class Ajax
{
    /**
     * Array of ajax errors
     *
     * @var array $ajaxErrors
     */
    public static $ajaxErrors = [];

    /**
     * Initiate Admin Class
     *
     * @return bool
     */
    public static function checkAndRun()
    {
        if (User::isLoggedIn() && !empty($_POST['action']) && method_exists(__CLASS__, $_POST['action'])) {
            $action = $_POST['action'];
            if (!in_array($action, ['importFile', 'importEntry', 'updateGoogleKey', 'updateSettings'], true)) {
                exit;
            }
            $data = !empty($_POST['data']) ? $_POST['data'] : null;
            $response = (object) self::$action($data);
            echo json_encode($response);
            exit;
        }
    }

    /**
     * Update Google API Key
     *
     * @return string
     */
    public static function updateGoogleKey()
    {
        sleep(1);
        // Check to make sure we have Post Data
        if (empty($_POST['google_api_key'])) {
            return [
                'success' => false,
                'error' => 'Missing Post Data',
            ];
        }

        if (!Database::saveSetting('google_api_key', $_POST['google_api_key'])) {
            return [
                'success' => false,
                'error' => 'Error Updating DB',
            ];
        }

        return [
            'success' => true,
            'error' => '',
        ];
    }

    /**
     * Update Category Colors
     *
     * @return string
     */
    public static function updateSettings()
    {
        sleep(1);
        // Check to make sure we have Post Data
        if (empty($_POST['category_colors'])) {
            return [
                'success' => false,
                'error' => 'Missing Post Data',
            ];
        }

        if (!Database::saveSetting('category_colors', $_POST['category_colors'])) {
            return [
                'success' => false,
                'error' => 'Error Updating DB with ' . $_POST['category_colors'],
            ];
        }

        return [
            'success' => true,
            'error' => '',
        ];
    }

    /**
     * Import File
     *
     * @return string
     */
    public static function importFile()
    {
        if (empty($_POST['process'])) {
            return [
                'success' => false,
                'error' => 'Error, Missing Action',
            ];
        }

        if (trim($_POST['process']) === 'override') {
            Database::deleteAll();
        }

        $entries = [];

        if (!empty($_FILES['file']['tmp_name'])) {
            $fh = fopen($_FILES['file']['tmp_name'], 'r');
            $csvHeadings = [];

            $csvRow = 1;

            while ($csvLine = fgetcsv($fh)) {
                if (empty($csvHeadings)) {
                    $csvHeadings = array_flip(array_map(function ($elem) {
                        return strtolower(trim(str_replace(' ', '_', trim($elem))));
                    }, array_unique($csvLine)));

                    if (empty($csvHeadings) || !isset($csvHeadings['address'])) {
                        return [
                            'success' => false,
                            'error' => 'Error Parsing File Headers.',
                        ];
                    }
                    continue;
                }

                $csvRow++;

                $entry = new Entry($csvLine, 'csv', $csvRow, $csvHeadings);

                $entries[] = $entry;
            }

            fclose($fh);

            return [
                'success' => true,
                'error' => '',
                'entries' => json_decode(json_encode($entries)),
            ];
        }

        return [
            'success' => false,
            'error' => 'Error with File',
        ];
    }

    /**
     * Import File
     *
     * @return string
     */
    public static function importEntry()
    {
        // Check to make sure we have Post Data
        if (empty($_POST['entry'])) {
            return [
                'success' => false,
                'error' => 'Missing Post Data',
            ];
        }

        $entry = new Entry($_POST['entry'], 'json');

        if ($entry->error) {
            return [
                'success' => false,
                'error' => $entry->error,
            ];
        }

        $storeCache = false;

        // Check Cache Table for Lat Long
        if (empty($entry->latitude) || empty($entry->longitude)) {
            $checkResult = Database::checkForLatLong($entry);
            if (empty($checkResult->latitude) || empty($checkResult->longitude)) {
                $storeCache = true;
                $geoInfo = GeoLocation::getLatLong($entry);
                if (empty($geoInfo->latitude) || empty($geoInfo->longitude)) {
                    $entry->error = !empty($geoInfo->error) ? $geoInfo->error : 'Unknown Google API Error. Check that the Address is correct.';
                }
            }

            $entry->latitude = !empty($checkResult->latitude) ? $checkResult->latitude : (!empty($geoInfo->latitude) ? $geoInfo->latitude : '');
            $entry->longitude = !empty($checkResult->longitude) ? $checkResult->longitude : (!empty($geoInfo->longitude) ? $geoInfo->longitude : '');
        }

        $existingEntry = Database::getEntryWhere(
            [
                'name' => $entry->name,
            ]
        );

        $mode = '';

        if (!empty($existingEntry->id)) {
            $mode = 'updated';
            if (!Database::update($existingEntry->id, $entry)) {
                return [
                    'success' => false,
                    'error' => 'Error saving the Entry to the Database. Please contact Site Administrator.',
                    'mode' => $mode,
                ];
            }
        } else {
            $mode = 'added';
            // Insert Entry into Database
            if (!Database::insert($entry, $storeCache)) {
                return [
                    'success' => false,
                    'error' => 'Error saving the Entry to the Database. Please contact Site Administrator.',
                    'mode' => $mode,
                ];
            }
        }

        return [
            'success' => empty($entry->error),
            'error' => $entry->error,
            'mode' => $mode,
        ];
    }
}
