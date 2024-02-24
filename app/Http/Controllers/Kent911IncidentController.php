<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKent911IncidentRequest;
use App\Http\Requests\UpdateKent911IncidentRequest;
use App\Models\Kent911Incident;
use Carbon\Carbon;

class Kent911IncidentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ago = request()->input('ago');
        if (is_numeric($ago)) {
            return response()->json([
                'incidents' => Kent911Incident::where('created_at', '>', Carbon::now()->subMinutes($ago)->toDateTimeString())->orderBy('id', 'desc')->get(),
            ]);
        } else {
            return response()->json([
                'incidents' => Kent911Incident::where('created_at', '>', Carbon::now()->subHours(6)->toDateTimeString())->orderBy('id', 'desc')->get(),
            ]);
        }
    }

    public function geoJSON()
    {
        $features = [];
        $incidents = Kent911Incident::where('created_at', '>', Carbon::now()->subHours(6)->toDateTimeString())->orderBy('id', 'desc')->get();
        foreach ($incidents as $incident) {
            // dd($item);
            $item = [];
            $item['type'] = "Feature";
            $item['geometry']["type"] = "Point";
            $item['geometry']['coordinates'] = [floatval($incident->location->lon), floatval($incident->location->lat)];
            $item["properties"]["description"] = $incident->description;
            $item["properties"]["located_at"] = $incident->located_at;
            $item["properties"]["created_at"] = $incident->created_at;
            $item["properties"]["agency"] = $incident->agency;
            $features[] = $item;
        }
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreKent911IncidentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreKent911IncidentRequest $request)
    {
        $validated = $request->validated()->collect();

        $kent911Incident = new Kent911Incident();

        $kent911Incident->save();

        return response()->json([
            'incident' => $kent911Incident,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Kent911Incident  $kent911Incident
     * @return \Illuminate\Http\Response
     */
    public function show(Kent911Incident $kent911Incident)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateKent911IncidentRequest  $request
     * @param  \App\Models\Kent911Incident  $kent911Incident
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateKent911IncidentRequest $request, Kent911Incident $kent911Incident)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Kent911Incident  $kent911Incident
     * @return \Illuminate\Http\Response
     */
    public function destroy(Kent911Incident $kent911Incident)
    {
        //
    }
}