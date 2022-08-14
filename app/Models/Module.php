<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $primaryKey = 'id';

	protected $table = 'modules';
	protected $dateFormat = 'U';

	protected $fillable = ['is_menu', 'menu_icon', 'menu_name', 'module_order', 'module_parent', 'role', 'route'];

	public function scopeGetParentModules($query, $parent = 1){
		$query->where('module_parent', $parent);
	}

	public function parent(){
		return $this->hasOne(Module::class, 'id', 'module_parent');
	}
	public function menuChild() {
		return $this->hasMany(Module::class, 'module_parent', 'id')->where("is_menu",1)->orderBy('module_order');
	}
	public function child() {
		return $this->hasMany(Module::class, 'module_parent', 'id')->orderBy('module_order');
	}

	//Custom query
	public static function exceptOne() {
		return Module::with('child')->where('module_parent', 1)->get();
	}
	
	//Binds to the delete event of this model where it may delete a 
	//1.) module_parent (parent menu/module), 2.) a child, 3.) or, lastly, deletes the parent with their corresponding child().
    public static function boot() {
        parent::boot();
        self::deleting(function($module) {
             $module->child()->delete();
        });
    }
}
