<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Support\Facades\Hash;

class Admin extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $table = 'admin';

    protected $with = ['group', 'location'];

    protected $fillable = [
        'group_id', 'location_id', 'balance_id', 'username', 'change_password', 'password', 'login_date',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'password',
        'user_session',
    ];

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

    public function getLoginDateAttribute($date)
    {
        return date('d-m-Y H:i:s', $date);
    }

    public function getCreatedAtAttribute($date)
    {
        return date('d-m-Y H:i:s', strtotime($date));
    }

    public function getUpdatedAtAttribute($date)
    {
        return date('d-m-Y H:i:s', strtotime($date));
    }

    public function group() {
		return $this->belongsTo(AdminGroup::class, 'group_id');
	}
	public function location() {
		return $this->belongsTo(AdminLocation::class, 'location_id');
	}
	public function module() {
		return $this->belongsTo(Module::class, 'module_id');
	}
	public function cluster() {
		return $this->belongsTo(Cluster::class, 'game_id');
	}
}
