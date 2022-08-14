<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLocation extends Model
{
    protected $primaryKey = 'id';

	protected $table = 'admin_location';
	protected $dateFormat = 'U';
	
	public $timestamp = false;
}
