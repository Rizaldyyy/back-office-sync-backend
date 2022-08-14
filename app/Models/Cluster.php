<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cluster extends Model
{
    protected $primaryKey = 'id';
	protected $table = 'cluster';
	protected $dateFormat = 'U';
	
	protected $fillable = [
		'name', 'slug', 'url', 'cluster_url', 'ga_view_id', 'status'
	];
	
}
