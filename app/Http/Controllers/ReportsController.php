<?php

namespace App\Http\Controllers;

use App\Traits\GuzzleApi;
use App\Repositories\AdminRepository;
use Illuminate\Support\Arr;

class ReportsController extends Controller
{
    use GuzzleApi;

    /**
     * Shows the turnover winloss data per player or game
     */
    public function showTurnoverWinloss()
    {

        $validate = $this->validate(request(), $this->customRules('show'));

        $data = [];
        foreach (request('clusters') as $key => $cluster) {

            $url = $this->getApiUrl($cluster);
            $endPoint = 'api/data-game-player-towl-list';
            $params = request()->all();

            if ($url) {
                $url = $url . $endPoint;

                $results = $this->sendRequest('put', $url, $params);

                if ($results) {

                    $data[$key] = collect($results)->map(
                        fn ($result) => [
                            'cluster_name' => $cluster,
                            'towl_date' => $result->towl_date,
                            'data_name' => $result->data_name ?? null,
                            'agent_name' => $result->agent_name ?? null,
                            'provider_name' => $result->game_name ?? null,
                            'turnover' => $result->turnover,
                            'winloss' => $result->winloss,
                            'generated_at' => $result->generated_at
                        ]
                    );
                } else {
                    return response()->json(['status' => 'fail', 'message' => 'Unable to fetch the turnover-winlose data of cluster ' . ucwords($cluster)], 400);
                }
            }
        }

        return response()->json(['status' => 'success', 'data' => Arr::flatten($data, 1)]);
    }

    /**
     * Updates the turnover winloss data per cluster and their whitelabel brands
     */
    public function refetchTurnoverWinloss()
    {
        $validate = $this->validate(request(), $this->customRules('update'));

        $data = [];
        if (request('clusters')) {

            $clusters = request('clusters');
            $url = $this->getApiUrl($clusters);
            $endPoint = 'api/data-game-player-towl-refetch';
            $params = request()->all();
            // return response()->json(['status' => 'fail', 'message' => $params], 400);
            if ($url) {
                $url = $url . $endPoint;

                $results = $this->sendRequest('put', $url, $params);
                
                if ($results) {

                    return response()->json(['status' => 'success', 'message' => 'Refetching data is now in queue.', 'data' => $results]);

                    // $data[$key] = collect($results)->map(
                    //     fn ($result) => [
                    //         'brand_name' => $brand,
                    //         'towl_date' => $result->towl_date,
                    //         'data_name' => $result->data_name ?? null,
                    //         'agent_name' => $result->agent_name ?? null,
                    //         'provider_name' => $result->game_name ?? null,
                    //         'turnover' => $result->turnover,
                    //         'winloss' => $result->winloss,
                    //         'generated_at' => $result->generated_at
                    //     ]
                    // );
                } else {
                    return response()->json(['status' => 'fail', 'message' => 'Unable to re-fetch the turnover-winlose data of ' . ucwords($clusters)], 400);
                }
            }
        }
        
        return response()->json(['status' => 'success', 'data' => Arr::flatten($data, 1)]);
    }

    /**
     * Receive and record the log details on every game/provider that was refetched
     */
    public function createLogs()
    {
        AdminRepository::createLog(request('adminId'), request('cluster'), request('action'), request('detail'));
    }

    private function customRules($type)
    {
        switch ($type) {
            case 'show':

                return [
                    'clusters' => 'required',
                    'dateFrom' => 'required',
                    'dateTo' => 'required',
                    'reportType' => 'required'
                ];
                break;
            case 'update':

                return [
                    'adminId' => 'required',
                    'clusters' => 'required',
                    'dateFrom' => 'required',
                    'dateTo' => 'required',
                    'whitelabel' => 'required'
                ];
                break;
            default:
                return false;
                break;
        }
    }
}
