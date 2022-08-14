<?php

namespace App\Http\Controllers;
use App\Traits\GuzzleApi;

class WhitelabelController extends Controller
{
    use GuzzleApi;

    /**
     * Used to fetch the available cluster's whitelabel
     */
    public function showClusterWhitelabel()
    {

        $data = [];
        if (request('cluster')) {

            $cluster = request('cluster');
            $url = $this->getApiUrl($cluster);
            $endPoint = 'api/data-cluster-whitelabel';

            if ($url) {
                $url = $url . $endPoint;

                $results = $this->sendRequest('put', $url);

                if ($results) {

                    $data = collect($results)->map(
                        fn ($result) => [
                            'whitelabel_id' => $result->whitelabel_id,
                            'whitelabel_name' => ucwords($result->whitelabel_name)
                        ]
                    );
                } 
                else {
                    return response()->json(['status' => 'fail', 'message' => 'Unable to fetch the whitelabel clusters of ' . ucwords($cluster)], 400);
                }
            }
        }

        return response()->json(['status' => 'success', 'data' => $data]);
    }
}
