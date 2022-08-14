<?php

namespace App\Http\Controllers;

use App\Traits\GuzzleApi;
use App\Repositories\AdminRepository;
use App\Jobs\SyncJob;

class BankController extends Controller
{
    use GuzzleApi;

    /**
     * Used to get the list of blacklisted banks
     */
    public function show()
    {
        if (request('cluster_slug')) {

            $url = $this->getApiUrl(request('cluster_slug'));
            $endPoint = 'api/data-blacklist';
            
            if ($url) {
                $url = $url . $endPoint;
                
                $results = $this->sendRequest('put', $url);
                
                if ($results) {

                    $data = collect($results)->map(
                        fn ($result) => [
                            'cluster_name' => request('cluster_slug'),
                            'bank_blacklist_id' => $result->bank_blacklist_id,
                            'bank_code' => $result->bank_code,
                            'bank_account_number' => $result->bank_account_number,
                            'is_show' => true,
                            'rawData' => json_encode($result)
                        ]
                    );

                    return response()->json(['status' => 'success', 'data' => $data]);
                }
            }
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to fetch blacklisted banks. Please try again later.'], 400);
    }

    /**
     * Used to synchronize the blacklisted banks across the selected clusters
     */
    public function syncBlacklistedBanks()
    {
        //Validate the request attributes/parameters
        $validator = $this->validate(request(), $this->customRules());

        $clusters = '';
        $params = [];
        $params['process'] = request('process');

        $method = 'put';
        $endPoint = 'api/data-sync-blacklist';

        foreach (request('clusters') as $cluster) {

            $url = $this->getApiUrl($cluster);

            if ($url) {

                $apiUrl = $url . $endPoint;

                foreach (request('blacklists') as $param) {
                    $params['blacklist'] = is_string($param) ? $param : json_encode($param);
                    dispatch(new SyncJob($method, $apiUrl, $params))->onQueue('default');
                }
            }

            $clusters .= ucwords($cluster) . ", ";
        }

        $process = request('process') == 1 ? "Truncate then Add" : "Default";

        AdminRepository::createLog(
            request('adminId'),
            '',
            'Sync Blacklist',
            "To cluster(s): " . $clusters . " | process: " . $process . " | blacklist bank count: " . count(request('blacklists'))
        );

        return response()->json(['status' => 'success', 'message' => "Blacklist Bank Account sync is currently processing... "]);
    }

    private function customRules()
    {
        return [
            'clusters' => 'required',
            'process' => 'required',
            'blacklists' => 'required'
        ];
    }
}
