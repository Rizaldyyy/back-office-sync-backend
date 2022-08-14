<?php

namespace App\Http\Controllers;

use App\Models\AdminGroup;
use App\Repositories\AdminRepository;

class RoleController extends Controller
{
    public function show()
    {
        return response()->json(['status' => 'success', 'data' => AdminGroup::all()]);
    }

    public function store()
    {
        //Validate the request attributes/parameters
        $attributes = $this->validate(request(), $this->customRules());

        $attributes['module_roles'] = json_encode($attributes['module_roles']);
        $attributes['cluster_roles'] = json_encode($attributes['cluster_roles']);

        $role = AdminGroup::create($attributes);

        if ($role) {
            // logs
            AdminRepository::createLog(request()->adminId, '', 'Added a new role', 'Role: ' . strtoupper($role->name));

            return response()->json(['status' => 'success', 'data' => AdminGroup::find($role->id), 'message' => 'Role Created!']);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to create a new role. Please try again later!'], 400);
    }

    public function update()
    {
        //Find the role based on request id
        $role = AdminGroup::find(request()->id);

        if ($role) {

            //Validate the request attributes/parameters
            $attributes = $this->validate(request(), $this->customRules());

            //Proceed to update the specific role
            $role->name = $attributes['name'];
            $role->module_roles = json_encode($attributes['module_roles']);
            $role->cluster_roles = json_encode($attributes['cluster_roles']);

            if ($role->save()) {
                // logs
                AdminRepository::createLog(request()->adminId, '', "Updated the role", 'Role: ' . strtoupper($attributes['name']));

                return response()->json(['status' => 'success', 'data' => AdminGroup::find($role->id), 'message' => "Role {$attributes['name']} has been updated!"]);
            }
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to update the cluster. Please try again later!'], 400);
    }

    public function updateStatus()
    {
        $status = request()->status;
        $term = $status == 1 ? 'ACTIVE' : 'INACTIVE';

        //Find the role based on request id and update the status
        $role = AdminGroup::find(request()->id);
        $role->active = $status;

        if ($role->save()) {
            // logs
            AdminRepository::createLog(request()->adminId, '', 'Set Role Status', 'Role: ' . strtoupper($role->name) . ' set status to: ' . $term);

            return response()->json(['status' => 'success', 'message' => 'Role: ' . strtoupper($role->name) . ', status updated to: ' . $term]);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to update role status. Please try again later.'], 400);
    }

    public function destroy()
    {
        //Find the role based on request id
        $role = AdminGroup::find(request()->id);

        if ($role->delete()) {
            // logs
            AdminRepository::createLog(request()->adminId, '', 'Role Delete', 'Role: ' . strtoupper($role->name) . ' has been deleted');
            return response()->json(['status' => 'success', 'message' => 'Role: ' . strtoupper($role->name) . ' has been deleted.']);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to delete the role. Please try again later.'], 400);
    }

    private function customRules()
    {
        return [
            'name' => ['required', 'regex:/^[A-z]+/', 'min:4', 'max:20'],
            'module_roles' => 'required',
            'cluster_roles' => 'required',
        ];
    }
}
