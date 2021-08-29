<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class Products extends Controller
{
    public function index($product_handle, Request $request) {
        $destination = $request->all()['destination'];
        $site_url = $request->all()['shop'];
        $site_id = User::first()['site_id'];
        $client = new \GuzzleHttp\Client();

        $request = $client->get('https://webservices.catalog-on-demand.com/api/csm/v1/GetCellSet?siteURL='.$site_url.'&destination='.$destination.'&handle='.$product_handle.'&expand=1');
        $response = json_decode($request->getBody());

        return response()->json(['html' => $response->html, 'styles' => $response->styles]);
    }
}
