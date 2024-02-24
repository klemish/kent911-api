<?php

namespace Tests\Unit;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Jobs\Kent911CreateIncident;
use App\Models\Kent911Location;
use Illuminate\Support\Facades\Http;
use Mockery;

class Kent911Test extends TestCase
{
    // test[method name]Returns[value]For[circumstance/input]

    public function testIsValidLocationReturnsFalseForNoCounty()
    {
        // Mock the HTTP response from Nominatim
        $mockedResponse = [];
        HTTP::fake([
            'https://nominatim.openstreetmap.org/*' => HTTP::response($mockedResponse)
        ]);

        // Create an instance of YourClass
        $Kent911Incident = new Kent911CreateIncident('kent911', '', '', '');

        // Call the isValidLocation() method
        $result = $Kent911Incident->isValidLocation(123.456, 789.012);

        // Assert that the result is true for Kent County
        $this->assertFalse($result);
    }

    public function testIsValidLocationReturnsTrueForValidAddress()
    {
        // Mock the HTTP response from Nominatim
        $mockedResponse = [
            'address' => [
                'county' => 'Kent County'
            ]
        ];
        HTTP::fake([
            'https://nominatim.openstreetmap.org/*' => HTTP::response($mockedResponse)
        ]);

        // Create an instance of YourClass
        $Kent911Incident = new Kent911CreateIncident('kent911', '', '', '');

        // Call the isValidLocation() method
        $result = $Kent911Incident->isValidLocation(123.456, 789.012);

        // Assert that the result is true for Kent County
        $this->assertTrue($result);
    }

    public function testIsValidLocationReturnsFalseForInvalidAddress()
    {
        // Mock the HTTP response from Nominatim with an error
        $mockedResponse = [
            'address' => [
                'county' => 'Kern County'
            ]
        ];
        HTTP::fake([
            'https://nominatim.openstreetmap.org/*' => HTTP::response($mockedResponse)
        ]);

        // Create an instance of YourClass
        $Kent911Incident = new Kent911CreateIncident('kent911', '', '', '');

        // Call the isValidLocation() method
        $result = $Kent911Incident->isValidLocation(123.456, 789.012);

        // Assert that the result is false for an invalid address
        $this->assertFalse($result);
    }
}
