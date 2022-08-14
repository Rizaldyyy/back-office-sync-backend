<?php

namespace App\Http\Controllers;

use App\Traits\GuzzleApi;
use App\Repositories\AdminRepository;
use App\Jobs\SyncJob;

class GameController extends Controller
{
    use GuzzleApi;

    /**
     * Used to get the list of games of a certain vendor
     */
    public function show()
    {
        if (request('cluster_slug') && request('vendor_slug')) {

            $url = $this->getApiUrl(request('cluster_slug'));
            $endPoint = 'api/data-slot-game';

            if ($url) {
                $url = $url . $endPoint;

                $params['where'] = "game_provider.game_provider_slug = '" . request('vendor_slug') . "'";

                $results = $this->sendRequest('put', $url, $params);

                if ($results) {
                    $data = collect($results)->map(
                        fn ($result) =>  [
                            'cluster_name' => $result->cluster,
                            'slots_lobby_id' => $result->slots_lobby_id,
                            'slots_lobby_name' => $result->slots_lobby_name,
                            'is_show' => true,
                            'rawData' => json_encode($result)
                        ]
                    );

                    return response()->json(['status' => 'success', 'data' => $data]);
                }
            }
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to fetch game list. Please try again later.'], 400);
    }

    /**
     * Used to synchronize the game(s) across the selected clusters
     */
    public function syncGames()
    {
        //Validate the request attributes/parameters
        $validator = $this->validate(request(), $this->customRules());
        
        $clusters = '';
        $params = [];
        $params['vendor'] = request('vendor');
        $params['process'] = request('process');

        $method = 'put';
        $endPoint = 'api/data-sync-game';

        foreach (request('clusters') as $cluster) {
            $url = $this->getApiUrl($cluster);

            if ($url) {
                $apiUrl = $url . $endPoint;

                foreach (request('gamelists') as $gameList) {
                    $params['game'] = is_string($gameList) ? $gameList : json_encode($gameList);
                    dispatch(new SyncJob($method, $apiUrl, $params))->onQueue('default');
                }
            }

            $clusters .= ucwords($cluster) . ", ";
        }

        $process = request('process') == 1 ? "Truncate then Add" : "Default";

        AdminRepository::createLog(
            request()->adminId,
            '',
            'Sync Game(s)',
            "To cluster(s): " . $clusters . " | vendor: " . request('vendor') . " | process: " . $process . " | game count: " . count(request('gamelists'))
        );

        return response()->json(['status' => 'success', 'message' => 'Game(s) was successfully synchronized.']);
    }

    private function customRules()
    {
        return [
            'clusters' => 'required',
            'vendor' => 'required',
            'process' => 'required',
            'gamelists' => 'required'
        ];
    }
}
