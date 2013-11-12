<?php
class CampOverviewContent extends Content
{

	protected $error_message = '';
	
	public function display()
	{
		$xhtml = '<hr></hr><h1>ANMELDUNGEN VORBEREITUNGSWOCHE</h1>';
		//just to display the ppl already enroled...
		
		$sql = SqlQuery::getInstance();
		
		$tbl = new AbTable('lager_tbl',"SELECT u.first_name, u.last_name, u.zip, c.* FROM IBO_camp c JOIN user u ON u.id = c.user_id WHERE YEAR(timestamp)=".date('Y'),array());
		
		$xhtml .= array2html($sql->simpleQuery("SELECT COUNT(user_id) as students, IF(course_language_id =2,'fr','de') sprache FROM IBO_camp WHERE YEAR(timestamp)=".date('Y')." GROUP BY course_language_id"));
		
		$xhtml .= array2html($sql->simpleQuery("SELECT COUNT(user_id) as vegetarier FROM IBO_camp where vegetarian = 1 AND YEAR(timestamp)=".date('Y')));
		
		$xhtml .= array2html($sql->simpleQuery("SELECT COUNT(user_id) as students, IF(sex =1,'f','m') as sex, tshirt_size as tshirt FROM IBO_camp WHERE YEAR(timestamp)=".date('Y')." GROUP BY sex, tshirt_size ORDER BY sex"));
		
		$xhtml .= $tbl->getHtml();
		
		return $xhtml;
	}

}
?>
