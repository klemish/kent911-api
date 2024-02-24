<?php

namespace App\Console\Commands;

use App\Jobs\Kent911CreateIncident;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class Kent911Refresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '1591:kent911refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Kent911 data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // $noLocation = DB::table('kent911_incidents')->leftJoin('kent911_locations', 'kent911_incidents.located_at', '=', 'kent911_locations.located_at')->get();
        // dd($noLocation);
        function convertTableToArray($url)
        {
            // Get the HTML content from the URL
            $html = Http::get($url)->body();

            // Use regular expressions to remove any body or html tags that are not table tags
            $html = preg_replace('/<body[^>]*>/', '', $html);
            $html = preg_replace('/<\/body>/', '', $html);
            $html = preg_replace('/<html[^>]*>/', '', $html);
            $html = preg_replace('/<\/html>/', '', $html);

            // Use PHP's DOMDocument class to parse the HTML content
            $dom = new \DOMDocument();
            $dom->loadHTML($html);

            // Get all the rows in the table
            $rows = $dom->getElementsByTagName('tr');

            // Initialize the array that will hold the table data
            $tableData = [];

            // Loop through each row
            foreach ($rows as $row) {
                // Get all the cells in the row
                $cells = $row->getElementsByTagName('td');

                // Initialize the array that will hold the row data
                $rowData = [];

                // Loop through each cell
                foreach ($cells as $cell) {
                    // Get the cell's text content
                    $cellText = $cell->textContent;

                    // Add the cell's text content to the row data array
                    $rowData[] = $cellText;
                }

                // Add the row data array to the table data array
                $tableData[] = $rowData;
            }

            // Return the table data array
            return $tableData;
        }

        try {
            // Store Kent911 Data
            $Kent911 = HTTP::get('https://gis.kentcountymi.gov/agisprod/rest/services/Kent_County_Public_Incidents/MapServer/0/query?f=json&cacheHint=true&resultOffset=0&resultRecordCount=1000&where=1%3D1&orderByFields=DateTime%20desc&outFields=*&returnGeometry=false&spatialRel=esriSpatialRelIntersects')->json();

            // dd($Kent911['features']);

            # Get last seen timestamp
            $last_seen = DB::table('job_last_seen')->where('job_name', 'kent911')->value('last_seen');
            if (!empty($Kent911['features'])) {
                foreach ($Kent911['features'] as $incident) {
                    $incident = $incident['attributes'];
                    $agency = 'kent911' . $incident['AgencyType'];
                    $description = $incident['IncidentTypeDescription'];
                    $created_at = $incident['DateTime'];
                    $located_at = $incident['FullAddress'];
                    # If the incident is newer than the last seen timestamp, create a new incident
                    $created_at = Carbon::createFromTimestamp($created_at / 1000)->toDateTimeString();
                    if ($created_at > $last_seen) {
                        Kent911CreateIncident::dispatch($agency, $description, $created_at, $located_at);
                    }
                }
            }
        } catch (\Exception $error) {
            \Log::error($error);
        }

        try {
            // Pull GR Police Data
            $GRPDDispatch = convertTableToArray("https://data.grcity.us/Dispatch/Dispatched_Calls.html");
            // dd($GRPDDispatch);
            // Remove banner and empty space underneath
            array_shift($GRPDDispatch);
            array_shift($GRPDDispatch);

            # Get last seen timestamp
            $last_seen = DB::table('job_last_seen')->where('job_name', 'grpd')->value('last_seen');
            foreach ($GRPDDispatch as $incident) {
                // Convert to UTC Time
                $convertedTime = Carbon::createFromFormat('m/d/Y H:i', $incident[0], 'America/Detroit')->setTimezone('UTC')->toDateTimeString();
                $agency = "grpd";
                $description = $incident[1];
                $created_at = $convertedTime;
                $located_at = $incident[2];
                if ($created_at > $last_seen) {
                    Kent911CreateIncident::dispatch($agency, $description, $created_at, $located_at);
                }
            }
        } catch (\Exception $error) {
            \Log::error($error);
        }


        try {
            // Pull GR Fire Data
            $GRFDDispatch = convertTableToArray("https://data.grcity.us/Fire_Dispatch/Dispatched_Calls.html");
            // dd($GRFDDispatch);
            // Remove banner and empty space underneath
            array_shift($GRFDDispatch);
            array_shift($GRFDDispatch);
            # Get last seen timestamp
            $last_seen = DB::table('job_last_seen')->where('job_name', 'grfd')->value('last_seen');

            foreach ($GRFDDispatch as $incident) {
                // Convert to UTC Time
                $convertedTime = Carbon::createFromFormat('m/d/Y H:i', $incident[0], 'America/Detroit')->setTimezone('UTC')->toDateTimeString();
                $agency = "grfd";
                $description = $incident[1];
                $created_at = $convertedTime;
                $located_at = $incident[2];
                if ($created_at > $last_seen) {
                    Kent911CreateIncident::dispatch($agency, $description, $created_at, $located_at);
                }
            }
        } catch (\Exception $error) {
            \Log::error($error);
        }
    }
}
