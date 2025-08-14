<?php
/**
 * This file is to handle the DB
 */

namespace MapWidget;

/**
 * Class for managing DB requests
 */
class Database
{

    /**
     * The DB Connection
     *
     * @var object $db
     */
    private static $db = null;

    /**
     * The DB Schema
     *
     * @var array $schema
     */
    private static $schema = [
        'mek_entries' => [
            [
                'name' => 'address',
                'type' => 'VARCHAR(120)',
                'null' => false,
                'default' => '',
            ],
            [
                'name' => 'name',
                'type' => 'VARCHAR(120)',
                'null' => false,
                'default' => '',
            ],
            [
                'name' => 'category',
                'type' => 'VARCHAR(60)',
                'null' => false,
                'default' => '',
            ],
            [
                'name' => 'url',
                'type' => 'VARCHAR(255)',
                'null' => false,
                'default' => '',
            ],
            [
                'name' => 'error',
                'type' => 'VARCHAR(120)',
                'null' => false,
                'default' => '',
            ],
            [
                'name' => 'latitude',
                'type' => 'VARCHAR(60)',
                'null' => false,
                'default' => '',
            ],
            [
                'name' => 'longitude',
                'type' => 'VARCHAR(60)',
                'null' => false,
                'default' => '',
            ],
            [
                'name' => 'number_row',
                'type' => 'MEDIUMINT',
                'null' => false,
                'default' => 0,
            ],
        ],
        'mek_geo_cache' => [
            [
                'name' => 'address',
                'type' => 'VARCHAR(120)',
                'null' => false,
                'default' => '',
            ],
            [
                'name' => 'latitude',
                'type' => 'VARCHAR(60)',
                'null' => false,
                'default' => '',
            ],
            [
                'name' => 'longitude',
                'type' => 'VARCHAR(60)',
                'null' => false,
                'default' => '',
            ],
        ],
        'mek_settings' => [
            [
                'name' => 'setting_name',
                'type' => 'VARCHAR(120)',
                'null' => false,
                'default' => '',
            ],
            [
                'name' => 'setting_value',
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
        ],
    ];

    /**
     * Initiate the DB
     *
     * @return void
     */
    public static function init()
    {

        // $proBranch = 'main';
        // $appBranch = strtolower(getenv('APP_BRANCH'));
        // $envPrefix = $appBranch && strtolower(trim($appBranch)) !== $proBranch ? strtoupper($appBranch).'_' : (!empty($_SERVER['APP_BRANCH']) && $_SERVER['APP_BRANCH'] !== $proBranch ? strtoupper($_SERVER['APP_BRANCH']).'_' : '');

        // MySQL Variables
        $hostname = getenv('DB_HOST');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASS');
        $database = getenv('DB_NAME');
        $socket   = null;

        if (strpos($hostname, ':')) {
            $serverinfo = explode(':', $hostname);
            $hostname = $serverinfo[0];
            $socket = $serverinfo[1];
        }

        // Connect to MySQL
        self::$db = mysqli_connect($hostname, $username, $password, null, null, $socket);

        // Check if connection is Successful or not
        if (!self::$db) {
            die('Server Connection failed: '.mysqli_connect_error());
        }

        if (!mysqli_select_db(self::$db, $database)) {
            die('Database Connection failed: '.mysqli_connect_error());
        }

        self::updateSchema();
    }

    /**
     * Get Setting
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return string|bool
     */
    public static function getSetting($name, $default = null)
    {
        $sql = "SELECT setting_value FROM mek_settings WHERE setting_name = '".trim(addslashes(strval($name)))."' LIMIT 1";

        // check db for lat lng
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
        }

        if (!isset($row['setting_value'])) {
            return $default;
        }

        return trim(stripslashes(strval($row['setting_value'])));
    }

    /**
     * Save Setting
     *
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    public static function saveSetting($name, $value)
    {
        if (self::getSetting($name)) {
            $sql = "UPDATE mek_settings SET setting_value = '".strval($value)."' WHERE setting_name = '".trim(addslashes(strval($name)))."'";
        } else {
            $sql = "INSERT INTO mek_settings (setting_name, setting_value) VALUES ('".trim(addslashes(strval($name)))."', '".trim(addslashes(strval($value)))."')";
        }

        return mysqli_query(self::$db, $sql);
    }

    /**
     * Get all Entries
     *
     * @param bool $withOutErrors
     *
     * @return Entry[]
     */
    public static function getAll($withOutErrors = false)
    {
        $entries = [];

        $sql = 'SELECT * FROM mek_entries '.($withOutErrors ? " WHERE error = ''" : '').' ORDER BY number_row ASC';

        // check db for lat lng
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $entries[] = new Entry($row, 'db');
            }
        }

        return $entries;
    }

    /**
     * Delete all Entries
     *
     * @return Entry[]
     */
    public static function deleteAll()
    {
        $sql = "DELETE FROM mek_entries";

        // check db for lat lng
        return mysqli_query(self::$db, $sql) ? true : false;
    }

    /**
     * Insert Entry
     *
     * @param Entry $entry
     * @param bool  $storeCache
     *
     * @return bool
     */
    public static function insert($entry, $storeCache)
    {
        $existingEntry = self::getEntryWhere(
            [
                'name'    => $entry->name,
            ]
        );

        if (!empty($existingEntry->id) && is_int($existingEntry->id)) {
            $sql = "UPDATE mek_entries SET 
                address = '".str_replace("'", "\\'", $entry->address)."',
                name = '".str_replace("'", "\\'", $entry->name)."',
                category = '".str_replace("'", "\\'", $entry->category)."',
                url = '".str_replace("'", "\\'", $entry->url)."',
                error = '".str_replace("'", "\\'", $entry->error)."',
                latitude = '".str_replace("'", "\\'", $entry->latitude)."',
                longitude = '".str_replace("'", "\\'", $entry->longitude)."',
                number_row = '".str_replace("'", "\\'", intval($entry->rowNumber))."',
                WHERE id = ".$existingEntry->id;
        } else {
            $sql = "INSERT INTO mek_entries 
            (
                address,
                name,
                category,
                url,
                error,
                latitude,
                longitude,
                number_row
            ) 
            VALUES 
            (
                '".str_replace("'", "\\'", $entry->address)."',
                '".str_replace("'", "\\'", $entry->name)."',
                '".str_replace("'", "\\'", $entry->category)."',
                '".str_replace("'", "\\'", $entry->url)."',
                '".str_replace("'", "\\'", $entry->error)."',
                '".str_replace("'", "\\'", $entry->latitude)."',
                '".str_replace("'", "\\'", $entry->longitude)."',
                '".str_replace("'", "\\'", intval($entry->rowNumber))."'
            )";
        }

        // $sql = "INSERT INTO mek_entries (project) VALUES ('test')";
        if (!mysqli_query(self::$db, $sql)) {
            return false;
        }

        if ($storeCache) {
            $cacheSql = "INSERT INTO mek_geo_cache
            (
                address,
                latitude,
                longitude
            ) 
            VALUES 
            (
                '".$entry->address."',
                '".$entry->latitude."',
                '".$entry->longitude."'
            )";

            mysqli_query(self::$db, $cacheSql);
        }

        return true;
    }

    /**
     * Update Entry
     *
     * @param int   $id
     * @param Entry $entry
     *
     * @return bool
     */
    public static function update($id, $entry)
    {
        $sql = "UPDATE mek_entries SET 
            address = '".str_replace("'", "\\'", $entry->address)."',
            name = '".str_replace("'", "\\'", $entry->name)."',
            category = '".str_replace("'", "\\'", $entry->category)."',
            url = '".str_replace("'", "\\'", $entry->url)."',
            error = '".str_replace("'", "\\'", $entry->error)."',
            latitude = '".str_replace("'", "\\'", $entry->latitude)."',
            longitude = '".str_replace("'", "\\'", $entry->longitude)."',
            number_row = '".str_replace("'", "\\'", intval($entry->rowNumber))."'
            WHERE id = ".$id;

        if (!mysqli_query(self::$db, $sql)) {
            return false;
        }

        return true;
    }

    /**
     * Check DB to see if we already have the Entry
     *
     * @param array $fields - The fields to search by
     *
     * @return Entry|null {latitude, longitude}
     */
    public static function getEntryWhere($fields)
    {
        if (empty($fields)) {
            return null;
        }

        $where = [];

        foreach ($fields as $key => $value) {
            $where[] = $key." = '".trim($value)."'";
        }

        $sql = "SELECT * FROM mek_entries WHERE ".implode(' AND ', $where)." LIMIT 1";

        // check db for lat lng
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            if (!empty($row)) {
                return new Entry($row, 'db');
            }
        }

        return null;
    }

    /**
     * Check DB to see if we already have the Lat and Long for Address
     *
     * @param Entry $entry
     *
     * @return bool|object {latitude, longitude}
     */
    public static function checkForLatLong($entry)
    {
        // check if lat lng exist in database
        if (empty($entry->error)) {
            $sql = "SELECT * FROM mek_geo_cache WHERE address = '".$entry->address."' LIMIT 1";

            // check db for lat lng
            $result = mysqli_query(self::$db, $sql);
            if ($result) {
                $row = (object) mysqli_fetch_assoc($result);
                if (!empty($row->latitude) && !empty($row->longitude)) {
                    return (object) [
                        'latitude' => $row->latitude,
                        'longitude' => $row->longitude,
                    ];
                }
            }
        }

        return false;
    }

    /**
     * Get all Entries
     *
     * @param bool $empty - Get an Empty list
     *
     * @return Entry[]
     */
    public static function downloadAll($empty = false)
    {
        $entries = self::getAll();

        foreach ($entries as $entry) {
            unset($entry->rowNumber);
            unset($entry->error);
            unset($entry->id);
            unset($entry->rowNumber);
            unset($entry->categoryColor);
        }

        $headings = implode(',', array_map('ucwords', array_keys((array) $entries[0])));

        $replacements = [
            'Name' => 'Library Name',
            'Url' => 'Events',
        ];

        $headings = str_replace(array_keys($replacements), $replacements, $headings);

        $csv = $headings."\n";

        if ($empty) {
            for ($i = 0; $i < 10; $i++) {
                for ($c = 0; $c < (count((array) $entry) - 1); $c++) {
                    $csv .= ',';
                }
                $csv .= "\n";
            }
        } else {
            foreach ($entries as $entry) {
                $csv .= '"'.implode('","', (array) $entry).'"'."\n";
            }
        }

        header('Content-Type: text/csv');
        // tell the browser we want to save it instead of displaying it
        header('Content-Disposition: attachment; filename="map-entries'.($empty ? '-example' : '').'.csv";');

        echo $csv;
        exit;
    }

    /**
     * Check and Update Tables
     *
     * @return void
     */
    private static function updateSchema()
    {
        foreach (self::$schema as $table => $columns) {
            try {
                $test = mysqli_query(self::$db, 'SELECT 1 FROM '.$table.' LIMIT 1');
            } catch (\Exception $e) {
                // Could not connect.
            }
            if (empty($test)) {
                $sql = 'CREATE TABLE '.$table.' (id MEDIUMINT(9) NOT NULL AUTO_INCREMENT, PRIMARY KEY (id))';
                if (!mysqli_query(self::$db, $sql)) {
                    die('Adding Table ('.$table.') failed: '.mysqli_connect_error().' SQL:'.$sql);
                }
            }

            $existingColumns = [];
            $result = mysqli_query(self::$db, 'SHOW COLUMNS FROM '.$table);
            if ($result) {
                while ($row = mysqli_fetch_array($result)) {
                    $existingColumns[] = (object) [
                        'name' => $row['Field'],
                        'type' => strtoupper($row['Type']),
                        'null' => !empty($row['Null']) && strtolower($row['Null']) === 'yes' ? true : false,
                        'default' => $row['Default'],
                    ];
                }
            }

            foreach ($columns as $column) {
                $column = (object) $column;
                if (!in_array($column->name, array_values(array_column($existingColumns, 'name')), true)) {
                    $sql = 'ALTER TABLE '.$table.' ADD '.$column->name.' '.$column->type.' '.($column->null ? 'NULL' : 'NOT NULL').' DEFAULT "'.$column->default.'"';
                    if (!mysqli_query(self::$db, $sql)) {
                        die('Adding Column ('.$column->name.') failed: '.mysqli_connect_error().' SQL:'.$sql);
                    }
                    $existingColumns[] = (object) [
                        'name' => $column->name,
                        'type' => $column->type,
                        'null' => $column->null,
                        'default' => $column->default,
                    ];
                }
                foreach ($existingColumns as $existingColumn) {
                    if ($existingColumn->name !== 'id' && !in_array($existingColumn->name, array_values(array_column($columns, 'name')), true)) {
                        $sql = 'ALTER TABLE '.$table.' DROP COLUMN '.$existingColumn->name;
                        if (!mysqli_query(self::$db, $sql)) {
                            die('Dropping Column ('.$column->name.') failed: '.mysqli_connect_error().' SQL:'.$sql);
                        }
                    } elseif ($column->name === $existingColumn->name) {
                        if ((!empty($column->type) && !empty($existingColumn->type) && trim(strtolower($column->type)) !== trim(strtolower($existingColumn->type))) || (!empty($column->default) && !empty($existingColumn->default) && trim(strtolower($column->default)) !== trim(strtolower($existingColumn->default))) || $column->null !== $existingColumn->null) {
                            $sql = 'ALTER TABLE '.$table.' MODIFY '.$column->name.' '.strtoupper($column->type).' '.($column->null ? 'NULL' : 'NOT NULL').' DEFAULT "'.$column->default.'"';
                            if (!mysqli_query(self::$db, $sql)) {
                                die('Updating Column ('.$column->name.') failed: '.mysqli_connect_error().' SQL:'.$sql);
                            }
                        }
                    }
                }
            }
        }
    }
}
