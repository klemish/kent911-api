<?php

namespace App\Http\Controllers;

use App\Models\Kent911Location;
use App\Http\Requests\StoreKent911LocationRequest;
use App\Http\Requests\UpdateKent911LocationRequest;

class Kent911LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreKent911LocationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreKent911LocationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Kent911Location  $kent911Location
     * @return \Illuminate\Http\Response
     */
    public function show(Kent911Location $kent911Location)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateKent911LocationRequest  $request
     * @param  \App\Models\Kent911Location  $kent911Location
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateKent911LocationRequest $request, Kent911Location $kent911Location)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Kent911Location  $kent911Location
     * @return \Illuminate\Http\Response
     */
    public function destroy(Kent911Location $kent911Location)
    {
        //
    }
}
