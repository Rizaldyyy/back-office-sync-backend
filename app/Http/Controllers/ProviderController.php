<?php

namespace App\Http\Controllers;

use App\Traits\GuzzleApi;

class ProviderController extends Controller
{
    use GuzzleApi;

    /**
     * Used to get the list of available vendors
     */
    public function show()
    {
        if (request('cluster_slug')) {

            $url = $this->getApiUrl(request('cluster_slug'));
            $endPoint = 'api/data-vendor';

            if ($url) {
                $url = $url . $endPoint;

                $results = $this->sendRequest('put', $url);

                if ($results) {
                    $data = collect($results)->map(
                        fn ($result) => [
                            'cluster_name' => $result->cluster,
                            'vendor_id' => $result->game_provider_id,
                            'vendor_name' => $result->game_provider_name,
                            'vendor_slug' => $result->game_provider_slug
                        ]
                    );
    
                    return response()->json(['status' => 'success', 'data' => $data]);
                }
            }
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to fetch vendors. Please try again later.'], 400);
    }   
}
