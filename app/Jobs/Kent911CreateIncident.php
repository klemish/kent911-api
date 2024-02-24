<?php

namespace App\Jobs;

use App\Models\Kent911Incident;
use App\Models\Kent911Location;
use Carbon\Carbon;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class Kent911CreateIncident implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**

     * The number of times the job may be attempted.
     *
     * @var int
     */

    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */

    public $timeout = 120;

    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */

    public $failOnTimeout = true;

    private $agency;
    private $description;
    private $created_at;
    private $located_at;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public function __construct($agency, $description, $created_at, $located_at)
    {
        $this->agency = $agency;
        $this->description = $description;
        $this->created_at = $created_at;
        $this->located_at = $located_at;
    }

    /**
     * Get the location of the incident
     * @param $address
     * @param bool $bingOnly
     * @return array
     */

    public function getLatLon($address, $bingOnly = false)
    {
        // If the address contains a slash or "AT", use Bing for geocoding
        if (str_contains($address, "/") || str_contains($address, "AT") || $bingOnly) {
            print("[BING GEOCODE] AT " . $address . "\n");
            $pattern = '/\s([SWENO]O)/';
            // Log modified addresses
            if (preg_match($pattern, $address)) {
                $address = preg_replace($pattern, '', $address);
                print("[BING GEOCODE] MODIFIED AT " . $address . "\n");
            }
            // If address contains "OVER" replace with "&"
            if (str_contains($address, "OVER")) {
                $address = str_replace("OVER", "%26", $address);
                print("[BING GEOCODE] MODIFIED AT " . $address . "\n");
            }
            // If address contains "/" replace with "&"
            if (str_contains($address, "/")) {
                $address = str_replace("/", "%26", $address);
                print("[BING GEOCODE] MODIFIED AT " . $address . "\n");
            }

            // Use the docker endpoint in development environments
            if (App::environment(['local'])) {
                $host = 'http://localhost:4444/wd/hub';
            } else {
                $host = 'http://selenium-hub.selenium-grid.svc:4444/wd/hub';
            }

            // Error handling for Bing Geocode Lookup
            try {
                // Try to connect to Selenium
                try {
                    // Make a browser using the Chrome driver
                    $driver = RemoteWebDriver::create($host, DesiredCapabilities::chrome(), 15 * 1000, 15 * 1000);

                    // Use the Bing Maps site
                    $driver->get("https://www.bing.com/maps?q=lat+lon+" . $address);
                    // Select the element with lat, lon data
                    $latLongDiv = $driver->findElement(WebDriverBy::cssSelector('div.geochainModuleLatLong'));
                    $driver->quit();
                    print("[BING GEOCODE] FOUND " . $address . " AT RAW " . $latLongDiv->getText() . "\n");
                    // If we don't have a lat, lon div, return error case
                    if (!isset($latLongDiv)) {
                        print("[BING GEOCODE] MISSING LAT LONG TEXT " . $address . "\n");
                        return [-1, -1];
                    }
                    // Seperate lat, lon
                    // Expected format: "42.9634, -85.6681"
                    $latLongArray = explode(', ', $latLongDiv->getText());
                    // If we don't have two distinct items, return error case
                    if (count($latLongArray) !== 2) {
                        print("[BING GEOCODE] ERROR MISSING EITHER LAT OR LONG AT " . $address . " HAVE " . json_encode($latLongArray) . " FROM " . $latLongDiv->getText() . "\n");
                        return [-1, -1];
                    }
                    print("[BING GEOCODE] FOUND " . $address . " AT [" . $latLongArray[0] . ", " . $latLongArray[1] . "]\n");
                    // Check if Bing returned a valid location
                    if (!$this->isValidLocation($latLongArray[0], $latLongArray[1])) {
                        // If the address is missing Grand Rapids, MI, add it and try again
                        if (!str_contains($address, "Grand Rapids, MI")) {
                            print("[BING GEOCODE] RETRY ADDING GRAND RAPIDS, MI " . $address . "\n");
                            return $this->getLatLon($address . " Grand Rapids, MI", true);
                        } else {
                            print("[BING GEOCODE] INVALID LOCATION AT " . $address . "\n");
                            return [-1, -1];
                        }
                    } else {
                        print("[BING GEOCODE] VALID LOCATION AT " . $address . "\n");
                        return $latLongArray;
                    }
                } catch (\Exception $error) {
                    // The information block does not have a latitude, longitude div
                    if ($error == "Facebook\WebDriver\Exception\NoSuchElementException") {
                        // If the address is missing Grand Rapids, MI, add it and try again
                        if (!str_contains($address, "Grand Rapids, MI")) {
                            print("[BING GEOCODE] RETRY ADDING GRAND RAPIDS, MI " . $address . "\n");
                            return $this->getLatLon($address . " Grand Rapids, MI", true);
                        } else {
                            print("[BING GEOCODE] FAILED LOOKUP AT " . $address . " FOR " . $error . "\n");
                            return [-1, -1];
                        }
                    }
                }
            } catch (\Exception $error) {
                // Connection reset
                if ($error == "Facebook\WebDriver\Exception\WebDriverCurlException") {
                    return $this->getLatLon($address);
                } else {
                    // Other error
                    print("[BING GEOCODE] ERROR [" . $error . "]: " . $address . "\n");
                    return [-1, -1];
                }
            }
        } else {
            // Use Nominatim for geocoding
            print("[NOMINATIM] LOOKUP " . $address . "\n");
            $response = HTTP::get('https://nominatim.openstreetmap.org/search?format=json&q=' . $address)->json();
            // If address is not located by Nominatim, use backup provider
            if (empty($response)) {
                print("[NOMINATIM] USE BACKUP PROVIDER AT " . $address . "\n");
                return $this->getLatLon($address, true);
            }
            // Select the first result returned by Nominatim
            $lat = $response[0]['lat'];
            $lon = $response[0]['lon'];
            print("[NOMINATIM] " . $address . " GEOCODED [" . $lat . ',' . $lon . "]\n");
            // Check if Nominatim returned a valid location
            if (!$this->isValidLocation($lat, $lon)) {
                print("[NOMINATIM] INVALID LOCATION AT " . $address . "\n");
                return $this->getLatLon($address, true);
            } else {
                print("[NOMINATIM] VALID LOCATION AT " . $address . "\n");
                return [$lat, $lon];
            }
        }
        print("[GEOCODE] NO TRY ERROR " . $address . "\n");
        return [-1, -1];
    }

    /**
     * Reverse Geocode to check if the location is in Kent County.
     * @param $lat
     * @param $lon
     * @return bool
     */
    public function isValidLocation($lat, $lon)
    {
        $response = HTTP::get('https://nominatim.openstreetmap.org/reverse.php?format=jsonv2&lat=' . $lat . '&lon=' . $lon)->json();
        // Nominatim is unable to Geocode
        if (isset($response['error'])) {
            print("[NOMINATIM REVERSE GEOCODE] INVALID ADDRESS AT " . $lat . ", " . $lon . "\n");
            return false;
        }
        // Returns true if the county is Kent County
        if (isset($response['address']['county'])) {
            if ($response['address']['county'] == "Kent County") {
                print("[NOMINATIM REVERSE GEOCODE] VALID ADDRESS AT " . $lat . ", " . $lon . "\n");
                return true;
            } else {
                print("[NOMINATIM REVERSE GEOCODE] INVALID ADDRESS AT " . $lat . ", " . $lon . "\n");
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Create a new incident
     * @return bool
     */

    public function makeLocation($address)
    {
        // Check if the location already exists
        $count = Kent911Location::where('located_at', $address)->count();
        // If the location does not exist, create it
        if ($count == 0) {
            $newLocation = new Kent911Location();
            $newLocation->located_at = $address;
            $locations = $this->getLatLon($address);
            // If the location is not geocoded, return false
            if ($locations[0] == -1) {
                print("[KENT911 makeLocation] FAILED TO GEOCODE " . $address . "\n");
                return false;
            }
            $newLocation->lat = $locations[0];
            $newLocation->lon = $locations[1];
            $newLocation->save();
            print("[KENT911 makeLocation] CREATED LOCATION " . $address . " AT [" . $locations[0] . ", " . $locations[1] . "]\n");
            return true;
        } else if ($count == 1) {
            // If the location exists, return true
            return true;
        } else {
            return false;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        // Convert Grand Rapids Dispatch timestamps to UTC
        // if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4}) (\d{1,2}):(\d{1,2})$/', $this->created_at)) {
        //     $convertedTime = Carbon::createFromFormat('m/d/Y H:i', $this->created_at, 'America/Detroit')->setTimezone('UTC')->toDateTimeString();
        // } else {
        //     $convertedTime = Carbon::createFromTimestamp($this->created_at / 1000)->toDateTimeString();
        // }
        // Check if the incident already exists
        if (Kent911Incident::where('created_at', $this->created_at)->where('located_at', $this->located_at)->where('agency', $this->agency)->count() == 0) {
            $newIncident = new Kent911Incident();
            $newIncident->created_at = $this->created_at;
            $newIncident->description = $this->description;
            $newIncident->located_at = $this->located_at;
            $newIncident->agency = $this->agency;
            // Create the location if it does not exist
            if (!$this->makeLocation($newIncident->located_at)) {
                print("[911Incident] ERROR MAKING INCIDENT " . $newIncident . "\n");
                // Return job failure
                return false;
            }
            $newIncident->save();
            print("[911Incident] CREATED " . $newIncident . "\n");
            DB::table('job_last_seen')->where('job_name', $this->agency)->update(['last_seen' => Carbon::now()->toDateTimeString()]);
            // Return job success
            return true;
        } else {
            // Return job success
            return true;
        }
    }
}
