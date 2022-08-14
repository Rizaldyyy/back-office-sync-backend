<?php

namespace App\Repositories;

use App\Models\Module;

/**
* Bank resource repository
*/
class ModulesRepository
{
	function __construct(){
	}
	
    public static function getMenu($roles)
    {
        $menu = Module::where('module_parent', 1)
            ->where("is_menu",1)
            ->orderBy('module_order')
            ->get();

        foreach($menu as $mn){
            $mn->children = array_filter($mn->menuChild->toArray(), function($child) use ($roles){
                if(strpos(strtolower($roles),strtolower($child['role']))!==false){
					return $child;
				}
            });
        }

        return $menu;
    }

}