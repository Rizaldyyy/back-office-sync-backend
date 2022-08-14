<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use DB;

use App\Models\Admin;
use App\Models\AdminLog;
use App\Models\AdminLocation;

use App\Repositories\AdminRepository;
use App\Repositories\ModulesRepository;

use App\ThirdParty\CsTool;

class AdminController extends Controller
{
    // ADMIN
    public function show()
    {
        $admin = Admin::all();

        return response()->json(['status' => 'success','data' => $admin]);
    }

    public function store()
    {
        $attributes = $this->validate(request(), [
            'username' => ['required', 'alpha_num', 'min:4', 'max:255', Rule::unique('admin', 'username')],
            'password' => ['required','min:8','regex:/^(?=\S*[a-z])(?=\S*[A-Z])/', Rule::notIn($this->bannedPasswords()), 'confirmed'],
            'group_id' => 'required',
            'location_id' => 'required',
            'change_password' => 'required'
        ]);

        $adminId = request()->adminId;
        $attributes['login_date'] = time();

        $admin = Admin::create($attributes);
        if($admin)
        {
            $data = Admin::find($admin->id);
            // logs
            AdminRepository::createLog($adminId, '', 'Added New Admin', 'Admin Name: '.strtoupper($data->username));

            return response()->json(['status' => 'success', 'data' => $data, 'message' => 'Admin Created!']);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to create new admin. Please try again later!'], 400);
    }

    public function update()
    {
        $adminId = request()->adminId;
        $id = request()->id;

        $admin = Admin::find($id);
        if($admin)
        {
            $attributes = $this->validate(request(), [
                'username' => ['required', 'alpha_num', 'min:4', 'max:255', Rule::unique('admin', 'username')->ignore($admin)],
                'password' => ['required','min:8','regex:/^(?=\S*[a-z])(?=\S*[A-Z])/', Rule::notIn($this->bannedPasswords()), 'confirmed'],
                'group_id' => 'required',
                'location_id' => 'required',
                'change_password' => 'required'
            ]);
            
            $admin->username = $attributes['username'];
            
            if(!empty($attributes['password'])){
				$admin->password = $attributes['password'];
			}
            elseif(request()->location_id == 2){
				$new_pass = $this->generateRandomPassword();

				$cstool = $this->cstool->sendCredentials(strtolower($admin->username), $new_pass);
				if ($cstool->success === false) {
                    return response()->json(['status' => 'fail', 'message' => 'Failed! Error in CSTool side: '.$cstool->error_description.' for Admin : '.strtolower($admin->username)], 400);
                }

                $admin->password = $new_pass;
			}

            $admin->group_id = $attributes['group_id'];
			$admin->location_id = $attributes['location_id'];
			$admin->change_password = $attributes['change_password'];
            $admin->save();

            $admin = Admin::find($id); //reload admin data
            // logs
            AdminRepository::createLog($adminId, '', 'Update Admin Data', 'Admin Name: '.strtoupper($admin->username));
            return response()->json(['status' => 'success', 'data' => $admin, 'message' => 'Admin Update!']);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to update admin. Please try again later!'], 400);
    }

    public function destroy()
    {
        $adminId = request()->adminId;
        $id = request()->id;

        $admin = Admin::find($id);
        $adminName = strtoupper($admin->username);
        
        if($admin->delete()){
            // logs
            AdminRepository::createLog($adminId, '', 'Admin Delete', 'Admin: '.strtoupper($admin->username).' has been deleted.');
            return response()->json(['status' => 'success', 'message' => 'Admin: '.strtoupper($admin->username).' has been deleted. Bye Admin!']);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to delete the admin account. Please try again later.'], 400);
    }

    public function updateStatus()
    {
        $adminId = request()->adminId;
        $id = request()->id;
        $status = request()->status;
        $term = $status==1 ? 'ACTIVE' : 'INACTIVE';

        $admin = Admin::find($id);
        $admin->status = $status;
        
        if($admin->save()){
            // logs
            AdminRepository::createLog($adminId, '', 'Set Admin Status', 'Admin: '.strtoupper($admin->username).' set status to: '.$term);

            return response()->json(['status' => 'success', 'message' => 'Admin: '.$admin->username.', status updated to: '.$term]);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to update admin status. Please try again later.'], 400);
    }

    public function updatePassword()
    {
        $attributes = $this->validate(request(), [
            'password' => 'required',
			'new_password' => ['required','min:8','regex:/^(?=\S*[a-z])(?=\S*[A-Z])/', Rule::notIn($this->bannedPasswords()),'confirmed'],
			'new_password_confirmation' => 'required',
        ]);

        $id = request()->id;
        $current_pass = $attributes['password'];
        $new_pass = $attributes['new_password'];

        $admin = Admin::find($id); //get admin data
        $check = Hash::check($current_pass, $admin->password);
        if(!$check){
            return response()->json(['message' => 'Wrong Password!'], 422);
        }

        // check password if the same
        if(strcmp($current_pass, $new_pass) == 0){
            return response()->json(['message' => 'New password cannot be same as your current password!'], 422);
        }

        //return error if repeated char
        $pattern = '/(.)\1\1/';
        $repeatedChar = preg_match($pattern, strtolower($new_pass));
        if($repeatedChar){
            return response()->json(['message' => 'New password cannot be same as your current password!'], 422);
        }

        $admin->password = $new_pass;
        $admin->login_ip = request()->ip();
        $admin->change_password = 0;

        if($admin->save())
        {
            // logs
            AdminRepository::createLog($admin->id, '', 'Added Change Password', 'Admin Name: '.strtoupper($admin->username));

            return response()->json(['status' => 'success', 'message' => 'Change password success!']);
        }

        return response()->json(['status' => 'fail', 'message' => 'Unable to change password. Please try again later!'], 400);
    }

    private function generateRandomPassword($length = 8) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

    private function bannedPasswords(){
		return [
			'password', 'aa168168', 'Aa168618', 'aA168168', 'aA131313', 'Aa131313', 'aa123456', 'Aa123456',
		];
	}

    // -------------------------------------------------------------------------------------------------------------------------------
    // LOGS
    public function showLogs()
    {
        $filter = request(['cluster', 'admin', 'action', 'detail']);
        $admin = AdminLog::latest()->select('*', DB::raw('IF(cluster, cluster, "Master BO") as cluster'))->filter($filter)->get();

        return response()->json(['status' => 'success','data' => $admin]);
    }

    
    // -------------------------------------------------------------------------------------------------------------------------------
    // LOCATION
    public function showLocation()
    {
        $admin = AdminLocation::all();

        return response()->json(['status' => 'success','data' => $admin]);
    }
}
