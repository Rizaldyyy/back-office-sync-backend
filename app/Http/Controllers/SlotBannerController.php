<?php

namespace App\Http\Controllers;

use App\Traits\GuzzleApi;
use App\Repositories\AdminRepository;
use App\Jobs\SyncJob;

class SlotBannerController extends Controller
{
    use GuzzleApi;

    /**
     * Used to get the list of slot banners
     */
    public function show()
    {
        if (request('cluster_slug')) {

            $url = $this->getApiUrl(request('cluster_slug'));
            $endPoint = 'api/data-slot-banner-list';
            $params['where'] = " whitelabel_site.site_id <> 0";

            if ($url) {
                $url = $url . $endPoint;

                $results = $this->sendRequest('put', $url, $params);

                if ($results) {

                    $data = collect($results)->map(
                        fn ($result) => [
                            'cluster_name' => request('cluster_slug'),
                            'slotbanner_name' => $result->site_name,
                            'site_id' => $result->site_id,
                            'is_show' => true,
                            'rawData' => $result->slot_banner
                        ]
                    );

                    return response()->json(['status' => 'success', 'data' => $data]);
                }
            }
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to fetch slot banner lists. Please try again later.'], 400);
    }

    /**
     * Used to synchronize the slot banners across the selected clusters
     */
    public function syncSlotBanners()
    {
        //Validate the request attributes/parameters
        $validator = $this->validate(request(), $this->customRules());

        $clusters = '';
        $params = [];

        $method = 'put';
        $endPoint = 'api/data-sync-slot-banner';

        foreach (request('clusters') as $cluster) {

            $url = $this->getApiUrl($cluster);

            if ($url) {

                $apiUrl = $url . $endPoint;

                $params['banner'] = request('slotbanner');
                dispatch(new SyncJob($method, $apiUrl, $params))->onQueue('default');
            }

            $clusters .= ucwords($cluster) . ", ";
        }

        AdminRepository::createLog(
            request('adminId'),
            '',
            'Sync Slot Banner',
            "To cluster(s): " . $clusters . " | Slot Banner Name: " . request('slotbanner_name')
        );

        return response()->json(['status' => 'success', 'message' => "Slot banner sync is currently processing... "]);
    }

    /**
     * Validation rules
     */
    private function customRules()
    {
        return [
            'clusters' => 'required',
            'slotbanner' => 'required',
            'slotbanner_name' => 'required'
        ];
    }
}
