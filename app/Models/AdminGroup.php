<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminGroup extends Model
{
    protected $primaryKey = 'id';
	protected $table = 'admin_role';
	protected $dateFormat = 'U';
	
	protected $fillable = ['name', 'module_roles', 'cluster_roles'];
}
