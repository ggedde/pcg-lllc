<?php
/**
 * This file is to handle Entry Functionality
 */

namespace MapWidget;

/**
 * Class for managing and rendering the Entry
 */
class Entry
{
    /**
     * ID from Database
     *
     * @var int $id
     */
    public $id = 0;

    /**
     * Address
     *
     * @var string $address
     */
    public $address = '';

    /**
     * Library Name
     *
     * @var string $name
     */
    public $name = '';

    /**
     * Events URL
     *
     * @var string $url
     */
    public $url = '';

    /**
     * Error
     *
     * @var string $error
     */
    public $error = '';

    /**
     * Category
     *
     * @var string $category
     */
    public $category = '';

    /**
     * Category Color
     *
     * @var string $categoryColor
     */
    public $categoryColor = '';

    /**
     * Latitude
     *
     * @var string $latitude
     */
    public $latitude = '';

    /**
     * Longitude
     *
     * @var string $longitude
     */
    public $longitude = '';

    /**
     * Row Number
     *
     * @var int $rowNumber
     */
    public $rowNumber = 0;

    /**
     * Parse String into Object
     *
     * @param string|array $data        - String from CSV or Array from Database
     * @param string       $source      - csv|db|json
     * @param int          $rowNumber   - Row Number
     * @param array        $csvHeadings - Heading Numbers
     */
    public function __construct($data, $source, $rowNumber = null, $csvHeadings = null)
    {
        switch ($source) {
            case 'csv':
                $entryArr = $data;

                if (empty($csvHeadings) || !isset($csvHeadings['address']) || !isset($entryArr[$csvHeadings['address']])) {
                    $this->error = 'Error Parsing Row';
                    return;
                }
                if (isset($csvHeadings['address']) && !empty($entryArr[$csvHeadings['address']])) {
                    $this->address = $this->format($entryArr[$csvHeadings['address']]);
                }
                if (isset($csvHeadings['category']) && !empty($entryArr[$csvHeadings['category']])) {
                    $this->category = $this->format(ucwords($entryArr[$csvHeadings['category']]), true);
                }
                if (isset($csvHeadings['library_name']) && !empty($entryArr[$csvHeadings['library_name']])) {
                    $this->name = $this->format($entryArr[$csvHeadings['library_name']]);
                }
                if (isset($csvHeadings['events']) && !empty($entryArr[$csvHeadings['events']])) {
                    $this->url = trim($entryArr[$csvHeadings['events']]);
                }
                if (isset($csvHeadings['latitude']) && !empty($entryArr[$csvHeadings['latitude']])) {
                    $this->latitude = $this->format($entryArr[$csvHeadings['latitude']]);
                }
                if (isset($csvHeadings['longitude']) && !empty($entryArr[$csvHeadings['longitude']])) {
                    $this->longitude = $this->format($entryArr[$csvHeadings['longitude']]);
                }
                break;

            case 'db':
                $this->id        = intval(trim($data['id']));
                $this->address   = trim($data['address']);
                $this->category  = trim($data['category']);
                $this->name      = trim($data['name']);
                $this->url       = trim($data['url']);
                $this->error     = trim($data['error']);
                $this->latitude  = trim($data['latitude']);
                $this->longitude = trim($data['longitude']);
                $this->rowNumber = trim($data['number_row']);
                break;

            case 'json':
                $data = json_decode($data);
                $this->address   = !empty($data->address) ? trim($data->address) : '';
                $this->category  = !empty($data->category) ? trim($data->category) : '';
                $this->name      = !empty($data->name) ? trim($data->name) : '';
                $this->url       = !empty($data->url) ? trim($data->url) : '';
                $this->error     = !empty($data->error) ? trim($data->error) : '';
                $this->latitude  = !empty($data->latitude) ? trim($data->latitude) : '';
                $this->longitude = !empty($data->longitude) ? trim($data->longitude) : '';
                $this->rowNumber = !empty($data->rowNumber) ? trim($data->rowNumber) : '';
                break;
        }

        if ($rowNumber && empty($this->rowNumber)) {
            $this->rowNumber = $rowNumber;
        }

        $strLimits = [
            'address' => 120,
            'category' => 60,
            'name' => 60,
            'url' => 255,
            'error' => 120,
            'latitude' => 60,
            'longitude' => 60,
        ];

        foreach (get_class_vars(__CLASS__) as $name => $value) {
            if ($name === 'id') {
                continue;
            }
            $this->$name = str_replace("'", "&#39;", $this->$name);
            $this->$name = addslashes(str_replace(['{', '}', '[', ']'], "", $this->$name));
            if (!$this->error && !in_array($name, ['id', 'error', 'address', 'latitude', 'longitude', 'categoryColor'], true) && empty($this->$name)) {
                $this->error = 'Missing '.ucwords($name);
            }

            if (!$this->error && empty($this->address) && (empty($this->latitude) || empty($this->longitude))) {
                $this->error = 'Missing Address';
            }

            if (isset($strLimits[$name]) && strlen(strval($this->$name)) > $strLimits[$name]) {
                $this->$name = substr($this->$name, 0, $strLimits[$name]);
                if (!$this->error) {
                    $this->error = 'Field ['.$name.'] is too long. Cannot be longer then '.$strLimits[$name].' characters';
                }
            }
        }
    }

    /**
     * Format Value
     *
     * @param string $value - Value to Format
     *
     * @return string
     */
    public function format($value)
    {
        $value = preg_replace('/[^0-9a-zA-Z\.\-\,\$\/\&\ ]/m', '', $value);
        return trim(mb_convert_encoding(trim($value), 'UTF-8', 'UTF-8'));
    }

    /**
     * List of States and abbreviations.
     *
     * @return array
     */
    public static function getStates()
    {
        return array(
            'Alabama'              => 'AL',
            'Alaska'               => 'AK',
            'American Samoa'       => 'AS',
            'Arizona'              => 'AZ',
            'Arkansas'             => 'AR',
            'California'           => 'CA',
            'Colorado'             => 'CO',
            'Connecticut'          => 'CT',
            'District of Columbia' => 'DC',
            'Delaware'             => 'DE',
            'Florida'              => 'FL',
            'Georgia'              => 'GA',
            'Guam'                 => 'GU',
            'Hawaii'               => 'HI',
            'Idaho'                => 'ID',
            'Illinois'             => 'IL',
            'Indiana'              => 'IN',
            'Iowa'                 => 'IA',
            'Kansas'               => 'KS',
            'Kentucky'             => 'KY',
            'Louisiana'            => 'LA',
            'Maine'                => 'ME',
            'Maryland'             => 'MD',
            'Massachusetts'        => 'MA',
            'Michigan'             => 'MI',
            'Minnesota'            => 'MN',
            'Mississippi'          => 'MS',
            'Missouri'             => 'MO',
            'Montana'              => 'MT',
            'Nebraska'             => 'NE',
            'Nevada'               => 'NV',
            'New Hampshire'        => 'NH',
            'New Jersey'           => 'NJ',
            'New Mexico'           => 'NM',
            'New York'             => 'NY',
            'North Carolina'       => 'NC',
            'North Dakota'         => 'ND',
            'Ohio'                 => 'OH',
            'Oklahoma'             => 'OK',
            'Oregon'               => 'OR',
            'Pennsylvania'         => 'PA',
            'Puerto Rico'          => 'PR',
            'Rhode Island'         => 'RI',
            'South Carolina'       => 'SC',
            'South Dakota'         => 'SD',
            'Tennessee'            => 'TN',
            'Texas'                => 'TX',
            'Utah'                 => 'UT',
            'Virgin Islands'       => 'VI',
            'Vermont'              => 'VT',
            'Virginia'             => 'VA',
            'Washington'           => 'WA',
            'West Virginia'        => 'WV',
            'Wisconsin'            => 'WI',
            'Wyoming'              => 'WY',
        );
    }
}
