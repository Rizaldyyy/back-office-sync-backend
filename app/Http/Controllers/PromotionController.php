<?php

namespace App\Http\Controllers;

use App\Traits\GuzzleApi;
use App\Repositories\AdminRepository;
use App\Jobs\SyncJob;

class PromotionController extends Controller
{
    use GuzzleApi;

    /**
     * Used to get the list of promotions
     */
    public function show()
    {
        if (request('cluster_slug')) {

            $url = $this->getApiUrl(request('cluster_slug'));
            $endPoint = 'api/data-promotion-list';
            $params['where'] = " available_promotion.promotion_id <> 0";

            if ($url) {
                $url = $url . $endPoint;
                
                $results = $this->sendRequest('put', $url, $params);

                if ($results) {

                    $data = collect($results)->map(
                        fn ($result) => [
                            'cluster_name' => request('cluster_slug'),
                            'promotion_id' => $result->promotion_id,
                            'promotion_title' => $result->promotion_title,
                            'promotion_content' => $result->promotion_content,
                            'is_show' => true,
                            'rawData' => json_encode($result)
                        ]
                    );

                    return response()->json(['status' => 'success', 'data' => $data]);
                }
            }
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to fetch promotion lists. Please try again later.'], 400);
    }

    /**
     * Used to synchronize the promotions across the selected clusters
     */
    public function syncPromotions()
    {
        //Validate the request attributes/parameters
        $validator = $this->validate(request(), $this->customRules());

        $clusters = '';
        $params = [];
        $params['process'] = request('process');

        $method = 'put';
        $endPoint = 'api/data-sync-promotion';

        foreach (request('clusters') as $cluster) {

            $url = $this->getApiUrl($cluster);

            if ($url) {

                $apiUrl = $url . $endPoint;

                foreach (request('promotions') as $param) {
                    $params['content'] = json_decode($param)->promotion_content;
                    $params['promotion'] = is_string($param) ? $param : json_encode($param);
                    dispatch(new SyncJob($method, $apiUrl, $params))->onQueue('default');
                }
            }

            $clusters .= ucwords($cluster) . ", ";
        }

        $process = request('process') == 1 ? "Truncate then Add" : "Default";

        AdminRepository::createLog(
            request('adminId'),
            '',
            'Sync Promotion',
            "To cluster(s): " . $clusters . " | process: " . $process . " | promotion sync count: " . count(request('promotions'))
        );

        return response()->json(['status' => 'success', 'message' => "Promotion sync is currently processing... "]);
    }

    /**
     * Validation rules
     */
    private function customRules()
    {
        return [
            'clusters' => 'required',
            'process' => 'required',
            'promotions' => 'required'
        ];
    }
}
