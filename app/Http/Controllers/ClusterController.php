<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\Cluster;
use App\Repositories\AdminRepository;


class ClusterController extends Controller
{
    // Cluster LIST
    public function show()
    {
        return response()->json(['status' => 'success', 'data' => Cluster::all()]);
    }

    public function store()
    {
        //Validate the request attributes/parameters
        $attributes = $this->validate(request(), $this->customRules());
        $attributes['slug'] = Str::slug($attributes['name'], '-');

        $cluster = Cluster::create($attributes);

        if ($cluster) {
            // logs
            AdminRepository::createLog(request()->adminId, '', 'Added a new cluster', 'Cluster: ' . strtoupper($cluster->name));

            return response()->json(['status' => 'success', 'data' => Cluster::find($cluster->id), 'message' => 'Cluster Created!']);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to create new cluster. Please try again later!'], 400);
    }

    public function update()
    {
        //Find the cluster based on request id
        $cluster = Cluster::find(request()->id);

        if ($cluster) {

            //Validate the request attributes/parameters
            $attributes = $this->validate(request(), $this->customRules());

            //Proceed to update the specific cluster
            $cluster->name = $attributes['name'];
            $cluster->slug = Str::slug($attributes['name'], '-');
            $cluster->url = $attributes['url'];
            $cluster->cluster_url = $attributes['cluster_url'];

            if ($cluster->save()) {
                // logs
                AdminRepository::createLog(request()->adminId, '', "Updated the cluster", 'Cluster: ' . strtoupper($attributes['name']));

                return response()->json(['status' => 'success', 'data' => Cluster::find($cluster->id), 'message' => "Cluster {$attributes['name']} has been updated!"]);
            }
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to update the cluster. Please try again later!'], 400);
    }

    public function updateStatus()
    {
        $status = request()->status;
        $term = $status == 1 ? 'ACTIVE' : 'INACTIVE';

        //Find the cluster based on request id and update the status
        $cluster = Cluster::find(request()->id);
        $cluster->status = $status;

        if ($cluster->save()) {
            // logs
            AdminRepository::createLog(request()->adminId, '', 'Set Cluster Status', 'Cluster: ' . strtoupper($cluster->name) . ' set status to: ' . $term);

            return response()->json(['status' => 'success', 'message' => 'Cluster: ' . strtoupper($cluster->name) . ', status updated to: ' . $term]);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to update cluster status. Please try again later.'], 400);
    }

    public function destroy()
    {
        //Find the cluster based on request id
        $cluster = Cluster::find(request()->id);

        if ($cluster->delete()) {
            // logs
            AdminRepository::createLog(request()->adminId, '', 'Cluster Delete', 'Cluster: ' . strtoupper($cluster->name) . ' has been deleted');
            return response()->json(['status' => 'success', 'message' => 'Cluster: ' . strtoupper($cluster->name) . ' has been deleted.']);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to delete the cluster. Please try again later.'], 400);
    }

    private function customRules()
    {
        return [
            'name' => ['required', 'regex:/^[A-z0-9 \-]+/', 'min:4', 'max:20'],
            'url' => ['required', 'url'],
            'cluster_url' => ['required', 'url'],
        ];
    }
}
