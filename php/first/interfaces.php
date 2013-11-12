<?php

//TODO: interface for Rights...


/***********************************************************************************/

interface XhtmlInterface{
	public function draw_normal();
	public function draw_modify();
	//public function draw_admin();
}

interface DBInterface{
	public function store();
	public function retrieve($id);
	public function delete();
}


//Permissions are always set for a whole group, never for single users.
interface PermissionInterface{
	public function maskPermissions($int);
	public function checkPermission($type);
}


?>
