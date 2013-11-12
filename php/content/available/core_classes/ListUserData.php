<?php
/*
 *      listuserdata.php
 *      
 *      Copyright 2009 user007 <user007@D1612ak>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */


class ListUserData extends Content{
	

	//protected $tag_keys = array('chgVmodeEdit','chgVmodeNormal','username','password','login','group','logout');
	//protected $tags = array('edit','normal','username','password','login','group','logout');
	
	protected $mConfKeys = array('usergroup','fields');
	protected $mConfValues = array('6','first_name,last_name,street,zip,city,mobile,phone,phone2,email');
	
	
	public function display(){
		$xhtml = '';
		
		$query = "SELECT ".$this->mConfValues['fields']." 
				FROM `user` u 
				JOIN `user_in_group` uig ON uig.`user_id`=u.`id`
				WHERE uig.`usergroup_id`=".$this->mConfValues['usergroup'];
				
		//echo $query;
		
		$table = new AbTable('Userlist',$query);
		
		$xhtml .= $table->getHtml();
		
		return $xhtml;
	}
	
}

?>
