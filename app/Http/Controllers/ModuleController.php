<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Repositories\AdminRepository;

use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    public function show()
    {
        return response()->json(['status' => 'success', 'data' => Module::exceptOne()]);
    }

    public function store()
    {
        //Validate the request attributes/parameters
        $attributes = $this->validate(request(), $this->customRules());

        //Proceed to create the specific module
        $module = Module::create($attributes);

        if ($module) {
            // logs
            AdminRepository::createLog(request()->adminId, '', 'Added a new module', 'Parent: '. request('module_parent') .' | Module: '. strtoupper(request('menu_name')));

            return response()->json(['status' => 'success', 'data' => Module::find($module->id), 'message' => 'Module Created!']);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to create a new module. Please try again later!'], 400);
    }

    public function update()
    {
        //Find the module based on request id
        $module = Module::find(request()->id);

        if ($module) {

            //Validate the request attributes/parameters
            $attributes = $this->validate(request(), $this->customRules());

            //Proceed to update the specific module
            $module->is_menu = $attributes['is_menu'];
            $module->menu_icon = isset(request()->menu_icon) ? request()->menu_icon : null;
            $module->menu_name = $attributes['menu_name'];
            $module->module_order = $attributes['module_order'];
            $module->module_parent = $attributes['module_parent'];
            $module->role = $attributes['role'];
            $module->route = $attributes['route'];

            if ($module->save()) {
                // logs
                AdminRepository::createLog(request()->adminId, '', "Updated the module", 'Parent: '. strtoupper($module->parent->menu_name) .' | Module: '. strtoupper($attributes['menu_name']));

                return response()->json(['status' => 'success', 'data' => $module, 'message' => "Module ". strtoupper($attributes['menu_name']) ." has been updated!"]);
            }
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to update the module. Please try again later!'], 400);
    }

    public function updateStatus()
    {
        $status = request()->status;
        $term = $status == 1 ? 'ACTIVE' : 'INACTIVE';

        //Find the module based on request id and update the status
        $module = Module::find(request()->id);
        $module->active = $status;

        if ($module->save()) {
            // logs
            AdminRepository::createLog(request()->adminId, '', 'Set Module Status', 'Parent: '. strtoupper($module->parent->menu_name) .' | Module: '. strtoupper($module->menu_name) .' | set status to: ' .$term);

            return response()->json(['status' => 'success', 'message' => 'Module: ' . strtoupper($module->menu_name) . ', status updated to: ' . $term]);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to update module status. Please try again later.'], 400);
    }

    public function destroy()
    {
        //Find the module based on request id
        $module = Module::with('child')->find(request()->id);

        if ($module->delete()) {
            // logs
            AdminRepository::createLog(request()->adminId, '', 'Module Delete', 'Parent: '. strtoupper($module->parent->menu_name) .' | Module: ' . strtoupper($module->menu_name) . ' has been deleted');
            return response()->json(['status' => 'success', 'message' => 'Module: ' . strtoupper($module->menu_name) . ' has been deleted.']);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to delete the module. Please try again later.'], 400);
    }

    private function customRules()
    {
        return [
            'is_menu' => ['required', 'boolean'],
            'menu_name' => ['required', 'min:4'],
            'module_order' => ['required', 'numeric'],
            'module_parent' => ['required', 'numeric'],
            'role' => ['required', 'regex:/^[A-z ]+$/', 'min:4'],
            'route' => ['required', 'regex:/^[A-z \/-]+$/']
        ];
    }
}
