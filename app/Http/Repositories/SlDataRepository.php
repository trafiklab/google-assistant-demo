<?php


namespace App\Http\Repositories;


class SlDataRepository
{
    public function getStationId(string $name): string
    {
        // get the station
        $stations = json_decode(
            file_get_contents('http://api.sl.se/api2/typeahead.json?searchstring=' . $name
                . '%20city&stationsonly=true&maxresults=5&key=' . env("KEY_SL_AUTOCOMPLETE"), true));

        if (strpos(strtolower($stations['ResponseData'][0]['Name']), strtolower($name)) === 0) {
            return $stations['ResponseData'][0]['SiteId'];
        } else {
            return null;
        }
    }
}