<?php
/**
 * This file is to handle GeoLocation Functionality
 */

namespace MapWidget;

/**
 * Class for managing GeoLocation Functionality
 */
class GeoLocation
{
    /**
     * Get Lat Long from Google
     *
     * @param Entry $entry
     *
     * @return object
     */
    public static function getLatLong($entry)
    {
        $googleApiKey = Database::getSetting('google_api_key');
        if (empty($googleApiKey)) {
            return (object) [
                'error' => 'Missing Google API Key. Please contact Site Administrator.',
            ];
        }
        $addressString = urlencode($entry->address);
        $request = @file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$addressString.'&sensor=true&key='.$googleApiKey, false, stream_context_create(array('http' =>
            array(
                'timeout' => 15,  //1200 Seconds is 20 Minutes
            ),
        )));
        if (empty($request)) {
            return (object) [
                'error' => 'Google API timed out and could not find location.',
            ];
        }
        $json = json_decode($request, true);
        $results = ($json && isset($json['results']) && is_array($json['results'])) ? $json['results'][0] : false;
        if ($results && isset($results['geometry']['location'])) {
            $latitude  = (isset($results['geometry']['location']['lat'])) ? $results['geometry']['location']['lat'] : false;
            $longitude = (isset($results['geometry']['location']['lng'])) ? $results['geometry']['location']['lng'] : false;
            if ($latitude && $longitude) {
                return (object) [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ];
            }
            return (object) [
                'error' => 'Google API Could not find location. Please check that the Address information is correct.',
            ];
        }

        return (object) [
            'error' => 'Unknown Google API Error. - '.json_encode($json),
        ];
    }
}
