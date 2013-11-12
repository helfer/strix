<?php
/**
 * @version 0.1
 * @brief User Object holding all methods associated with a user.
 * 
 * User Object. Does not handle Authentication!!!
 * 
 * @author: Jonas Helfer <jonas.helfer@ibosuisse.ch>
 * @date: 2009-05-23
 */
final class ScannedExam extends DatabaseObject{
	
	protected $db_vars = array('user_id','new_pw','student_exam_id','exam_file','info_fields','answer_fields','verified','match_dist','verified_by','changed');
	
	protected $id = 1; //!!!! DO NOT CHANGE THIS VALUE!!! //TODO: find a better solution for this
	protected $user_id = NULL;
	protected $new_pw = '';
	protected $student_exam_id = NULL;
	protected $exam_file = '';
	protected $info_fields = '';
	protected $answer_fields = '';
	protected $verified = 0;
	protected $match_dist = NULL;
	protected $verified_by = NULL;
	protected $changed = '0000-00-00 00:00:00';
	
	
	//--- cross variables
	//protected $primary_user_group_name = 'everybody';
	//protected $secondary_user_groups;
	
	//--- other variables
	
	public function getTableName()
	{
		return 'IBO_scanned_exam';	
	}
	
}

?>
