<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

use App\Repositories\ModulesRepository;
use App\Repositories\AdminRepository;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|min:3|max:255',
            'password' => 'required|min:3|max:255'
        ]);

        $admin = Admin::where('username', $request->username)->first();

        if($admin && Hash::check($request->password, $admin->password))
        {
            if($admin->status === 0){
                return response()->json(['status' => 'fail', 'message' => 'Your account is disabled. Unable to Login.'], 400);
            }

            $ip = request()->ip();
            $token = base64_encode(Str::random(40));

            Admin::where('username', $request->username)->update(['user_session' => $token, 'login_date' => time(), 'login_ip' => $ip]);
            $menu = ModulesRepository::getMenu($admin->group->module_roles);

            // logs
            AdminRepository::createLog($admin->id, '', 'Logged In', 'Date: '.date('d-m-Y H:i:s', time()).' - IP: '.$ip);
        
            return response()->json(['status' => 'success','data' => $admin, 'menu' => $menu]);
        }
        else{
            return response()->json(['status' => 'fail'], 401);
        }
    }

    public function logout()
    {
        return response()->json(['status' => 'success']);
    }
}
