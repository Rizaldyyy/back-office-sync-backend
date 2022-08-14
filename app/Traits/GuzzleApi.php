<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use App\Models\Cluster;

trait GuzzleApi
{
    /**
     * Makes an API request to cluster brand(s)
     */
    public function sendRequest(string $method, string $url, mixed $params = null)
    {
        //Send request
        $response = Http::{$method}($url, $params);

        return $response->successful() ? $response->object()->result : false;
    }

    /**
     * Returns the cluster API url of specific brand
     */
    public function getApiUrl(string $cluster)
    {
        return (Cluster::firstWhere('slug', $cluster))->url ?? false;
    }
}
