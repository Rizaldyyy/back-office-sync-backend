<?php

namespace App\Models;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    protected $primaryKey = 'id';

	protected $table = 'admin_log';
	protected $dateFormat = 'U';

	protected $with = ['admin'];
	
	public $timestamp = true;

    protected $fillable = [
        'admin_id', 'cluster', 'action', 'detail', 'admin_ip',
    ];

	public function scopeFilter($query, array $filters)
    {
        $query->when($filters['cluster'] ?? false, fn($query, $cluster) =>
            $query->where(fn($query) => 
                $query->where('title', 'like', '%'.$cluster.'%')
            )
        );

        $query->when($filters['admin'] ?? false, fn($query, $admin) =>
            $query->where(fn($query) => 
                $query->where('admin_id', $admin)
            )
        );

		$query->when($filters['action'] ?? false, fn($query, $action) =>
            $query->where(fn($query) => 
                $query->where('action', 'like', '%'.$action.'%')
            )
        );

		$query->when($filters['detail'] ?? false, fn($query, $detail) =>
            $query->where(fn($query) => 
                $query->where('detail', 'like', '%'.$detail.'%')
            )
        );
    }

    public function getCreatedAtAttribute($date)
    {
        return date('d-m-Y H:i:s', strtotime($date));
    }

    public function getUpdatedAtAttribute($date)
    {
        return date('d-m-Y H:i:s', (int) $date);
    }


	public function admin() {
		return $this->belongsTo(Admin::class, 'admin_id');
	}
}
