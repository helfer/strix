<?php
	//global $sg; // alias for $GLOBALS['config']


	/* Database:
 	 **********************************************************************/
 	$sg['db_host'] = 'localhost';
 	$sg['db_user'] = '';
  	$sg['db_pass'] = '';
 	$sg['db_name'] = '';
	
	
	define('ADMIN_EMAIL','admin@pspace.ch');
	
	
	/**@todo: move to Database
 	 * Languages:
 	 **********************************************************************/
 	$GLOBALS['config']['languages_long'] = array(
 		1=>'Deutsch',
 		2=>'Français',
 		3=>'Italiano',
 		4=>'English');
	$GLOBALS['config']['languages_short'] = array(
 		1=>'de',
 		2=>'fr',
 		3=>'IT',
 		4=>'en');
 	$GLOBALS['config']['languages_available'] = array(
 		1=>'de',
 		2=>'fr',
 		3=>'it',
 		4=>'en');
 	$GLOBALS['config']['languages_enabled'] = array(1=>'de',
 		2=>'fr',4=>'en');
	
	
	//--------------------------------------------------------------------
	//ALL THE STUFF I DON'T KNOW WHERE TO PUT:
	
	/** @todo: clean up! */
 	
 	
 	//ids of special pages in database
 	//NOTE: this can depend on domains.
	//TODO: this is gonna dissappear completely!
 	//IDEA: keep id's 1 to 10 free for special pages in every domain.
 	define('GLOBAL_PAGE_ID',2); //content gets displayed on every page
 	define('ERROR_PAGE_ID',1);
 	
 	
 	define('NEWSLETTER_PATH','/newsletters/');
	
	 	/* Misc:
 	 **********************************************************************/
 
 	define('MAX_PAGETREE_DEPTH',3);
 
 	define('TAG_SEPARATOR','#%S#');
 	
 	define('EVERYBODY_GROUP',1);
 	define('ADMIN_GROUP',2);
 	define('EVERYBODY_PAGEGROUP',1);
 	define('ADMIN_PAGEGROUP',1);
 	define('UNDER_CONSTRUCTION_PG',3);
	
	
	define('RESET_PWD_PAGE','/utils/reset_pwd/');
	
 	/* TAGS: Maybe to be moved to DB
 	 **********************************************************************/
 
	$GLOBALS['tag_array']['form_has_errors'] = array(
	 	'1'=>'Bitte hervorgehobene Felder überprüfen.',
 		'2'=>'Veuiller verifier les entrées marquées.',
 		'4'=>'Please verify highlighted fields.');

 	// ------------------- Restrictions ----------------- //
 	$GLOBALS['tag_array']['NotEmptyRestriction']['error'] = array(
 		'1'=>'zwingend auszufüllen',
 		'2'=>'Doit être rempli',
 		'3'=>'Ni zhu zai nar?',
 		'4'=>'Must not be empty',
 		'5'=>'Cài gì dây?');

 	$GLOBALS['tag_array']['NotFalseRestriction']['error'] = array(
 		'1'=>'Bitte akzeptieren',
 		'2'=>'veuiller accepter s.v.p.',
 		'4'=>'Please accept');
 		
 	$GLOBALS['tag_array']['IsNumericRestriction']['error'] = array(
 		'1'=>'Wert ist keine Zahl',
 		'2'=>'pas un nombre',
 		'4'=>'Value is not a Number');
 	
 	$GLOBALS['tag_array']['InRangeRestriction']['error'] = array(
		'1'=>'ungültiger Wert',
		'2'=>'numéro invalide',
 		'4'=>'value is out of range');
 	
	$GLOBALS['tag_array']['InListRestriction']['error'] = array(
		'4'=>'value is not in list');
	
	$GLOBALS['tag_array']['FunctionalRestriction']['error'] = array(
		'4'=>'value is out of bounds');
	
	$GLOBALS['tag_array']['IsDecentPassword']['error'] = array(
		'1'=>'Passwort erfüllt die Anforderungen nicht',
		'2'=>'mot de passe ne répond pas aux exigences',
		'4'=>'password does not meet requirements');
	
	$GLOBALS['tag_array']['IsUserPassword']['error'] = array(
		'4'=>'password wrong!');
	
	$GLOBALS['tag_array']['IsValidEmail']['error'] = array(
		'1'=>'E-Mail Adresse ungültig',
		'2'=>'émail invalide',
		'4'=>'not a valid e-mail address');

	$GLOBALS['tag_array']['SameAsRestriction']['error'] = array(
		'1'=>'Werte stimmen nicht überein!',
		'2'=>'valuers sont différantes!',
		'4'=>'values are not the same!');

	$GLOBALS['tag_array']['SameEmailAsRestriction']['error'] = array(
		'1'=>'Email Adressen sind nicht identisch!',
		'2'=>'émails sont différants!',
		'4'=>'email addresses differ!');

	$GLOBALS['tag_array']['StrlenRestriction']['too_long'] = array(
		'1'=>'Text ist zu lang',
		'2'=>'texte trop long',
		'4'=>'text is too long');
		
	$GLOBALS['tag_array']['StrlenRestriction']['too_short'] = array(
		'1'=>'Text ist zu kurz',
		'2'=>'texte trop court',
		'4'=>'text is too short');

	$GLOBALS['tag_array']['IsEmptyOrStrlenRestriction']['too_long'] = array(
		'1'=>'Text ist zu lang',
		'2'=>'le texte trop long',
		'4'=>'text is too long');
		
	$GLOBALS['tag_array']['IsEmptyOrStrlenRestriction']['too_short'] = array(
		'1'=>'Text ist zu kurz',
		'2'=>'le texte est trop court',
		'4'=>'text is too short');

	$GLOBALS['tag_array']['StrlenRestrictionIfEmailNotEmpty']['too_long'] = array(
		'1'=>'Text ist zu lang',
		'2'=>'le texte trop long',
		'4'=>'text is too long');
		
	$GLOBALS['tag_array']['StrlenRestrictionIfEmailNotEmpty']['too_short'] = array(
		'1'=>'Text ist zu kurz',
		'2'=>'le texte est trop court',
		'4'=>'text is too short');		

	$GLOBALS['tag_array']['StrlenRestrictionIfEmailNotEmpty']['nameNoEmail'] = array(
		'1'=>'Name ohne email',
		'2'=>'Nom sans email',
		'4'=>'Name without e-Mail');
		
	$GLOBALS['tag_array']['StrlenRestrictionIfAdressNotEmpty']['too_long'] = array(
		'1'=>'Text ist zu lang',
		'2'=>'le texte trop long',
		'4'=>'text is too long');
		
	$GLOBALS['tag_array']['StrlenRestrictionIfAdressNotEmpty']['too_short'] = array(
		'1'=>'Text ist zu kurz',
		'2'=>'le texte est trop court',
		'4'=>'text is too short');		
		
	$GLOBALS['tag_array']['StrlenRestrictionIfAdressNotEmpty']['nameNoAddress'] = array(
		'1'=>'Name ohne Adresse',
		'2'=>'Nom sans adresse',
		'4'=>'Name without address');

	$GLOBALS['tag_array']['IsDate']['error'] = array(
		'1'=>'kein gültiges Datum',
		'2'=>'date non valable',
		'4'=>'not a valid date');
		
	$GLOBALS['tag_array']['IsDate']['too_early'] = array(
		'1'=>'Datum ist zu früh',
		'2'=>'date est trop tôt',
		'4'=>'date is too early');
		
	$GLOBALS['tag_array']['IsDate']['too_late'] = array(
		'1'=>'Datum ist zu spät',
		'2'=>'date est trop tard',
		'4'=>'date is too late!');
		
	$GLOBALS['tag_array']['ArrayIsDate']['error'] = array(
		'1'=>'kein gültiges Datum',
		'2'=>'date non valable',
		'4'=>'not a valid date');
		
	$GLOBALS['tag_array']['ArrayIsDate']['too_early'] = array(
		'1'=>'Datum ist zu früh',
		'2'=>'date est trop tôt',
		'4'=>'date is too early');
		
	$GLOBALS['tag_array']['ArrayIsDate']['too_late'] = array(
		'1'=>'Datum ist zu spät',
		'2'=>'date est trop tard',
		'4'=>'date is too late!');		
		
	$GLOBALS['tag_array']['IsTime']['wrong_format'] = array(
		'1'=>'Format ungültig (nur hh:mm)',
		'2'=>'Pas dans le format hh:mm',
		'4'=>'Unknwon format. Please use hh:mm format.');	
		
	$GLOBALS['tag_array']['IsTime']['unknown_time'] = array(
		'1'=>'Ungültige Zeit',
		'2'=>'heure pas valable',
		'4'=>'invalid time');

	$GLOBALS['tag_array']['IsEmptyOrTime']['wrong_format'] = array(
		'1'=>'Format ungültig (nur hh:mm)',
		'2'=>'Pas dans le format hh:mm',
		'4'=>'Unknwon format. Please use hh:mm format.');	
		
	$GLOBALS['tag_array']['IsEmptyOrTime']['unknown_time'] = array(
		'1'=>'Ungültige Zeit',
		'2'=>'heure pas valable',
		'4'=>'invalid time');			
		
	$GLOBALS['tag_array']['IsEmailRestriction']['error'] = array(
		'1'=>'E-Mail Adresse ungültig',
		'2'=>'émail invalide',
		'4'=>'not a valid e-mail address!');

	$GLOBALS['tag_array']['IsEmptyOrEmailRestriction']['error'] = array(
		'1'=>'E-Mail Adresse ungültig',
		'2'=>'émail invalide',
		'4'=>'not a valid e-mail address!');
		
	$GLOBALS['tag_array']['isEmptyOrImageRestriction']['notimage'] = array(
		'1'=>'keine gültige Bilddateim (nur jpg und png erlaubt)',
		'2'=>'image invalide (jpg et png uniquement)',
		'4'=>'no valid image (jpg and png only)');
		
	$GLOBALS['tag_array']['isEmptyOrImageRestriction']['toobig'] = array(
		'1'=>'Bilddatei ist zu gross',
		'2'=>'image est trop large',
		'4'=>'image is too big');		
		
	$GLOBALS['tag_array']['isEmptyOrImageRestriction']['uploaderror'] = array(
		'1'=>'Upload fehlgeschlagen. Bitte ernuet versuchen.',
		'2'=>'télécharger interompu. Essayez de nouveau.',
		'4'=>'Upload failed. Please try again.');		
		
	$GLOBALS['tag_array']['isEmptyOrImageRestriction']['toosmall'] = array(
		'1'=>'Bild ist zu klein.',
		'2'=>'image trop petite',
		'4'=>'image is too small');	
		
	$GLOBALS['tag_array']['isImageRestriction']['notimage'] = array(
		'1'=>'keine gültige Bilddate (nur jpg und png erlaubt)',
		'2'=>'image invalide (jpg et png uniquement)',
		'4'=>'no valid image (jpg and png only)');
		
	$GLOBALS['tag_array']['isImageRestriction']['toobig'] = array(
		'1'=>'Bilddatei ist zu gross',
		'2'=>'image est trop large',
		'4'=>'image is too big');		
		
	$GLOBALS['tag_array']['isImageRestriction']['toosmall'] = array(
		'1'=>'Bilddatei ist zu klein',
		'2'=>'image est trop petite',
		'4'=>'image is too small');	
		
	$GLOBALS['tag_array']['isImageRestriction']['uploaderror'] = array(
		'1'=>'Upload fehlgeschlagen. Bitte ernuet versuchen.',
		'2'=>'télécharger interompu. Essayez de nouveau.',
		'4'=>'Upload failed. Please try again.');		
		
	$GLOBALS['tag_array']['isEmptyOrPDFOrImageRestriction']['notimageorpdf'] = array(
		'1'=>'keine gültige Datei (nur jpg und pdf erlaubt)',
		'2'=>'fichier invalide (jpg et pdf uniquement)',
		'4'=>'no valid extension (jpg and pdf only)');
		
	$GLOBALS['tag_array']['isEmptyOrPDFOrImageRestriction']['toobig'] = array(
		'1'=>'Datei ist zu gross',
		'2'=>'fichier est trop large',
		'4'=>'file is too big');		
		
	$GLOBALS['tag_array']['isEmptyOrPDFOrImageRestriction']['toosmall'] = array(
		'1'=>'Bilddatei ist zu klein',
		'2'=>'image est trop petite',
		'4'=>'image is too small');	
		
	$GLOBALS['tag_array']['isEmptyOrPDFOrImageRestriction']['uploaderror'] = array(
		'1'=>'Upload fehlgeschlagen. Bitte ernuet versuchen.',
		'2'=>'télécharger interompu. Essayez de nouveau.',
		'4'=>'Upload failed. Please try again.');		
		
		
		
	// ------------------- Boxes ----------------- //
	$GLOBALS['tag_array']['ContentBox']['insert_label'] = array(
		'1'=>'neues Objekt: ',
		'2'=>'nouveau objet: ',
		'4'=>'new object: ');
	$GLOBALS['tag_array']['ContentBox']['insert'] = array(
		'1'=>'einfügen',
		'2'=>'insérer',
		'4'=>'insert');
	$GLOBALS['tag_array']['ContentBox']['move_up'] = array(
		'1'=>'rauf',
		'4'=>'up');
	$GLOBALS['tag_array']['ContentBox']['move_down'] = array(
		'1'=>'runter',
		'4'=>'down');
	
	
	// ------------------- Content ----------------- //
	
	$GLOBALS['tag_array']['Content']['delete'] = array(
		'1'=>'löschen',
		'2'=>'supprimer',
		'4'=>'delete');
	$GLOBALS['tag_array']['Content']['edit'] = array(
		'1'=>'ändern',
		'2'=>'modifier',
		'4'=>'edit');
	$GLOBALS['tag_array']['Content']['translate'] = array(
		'1'=>'übersetzen',
		'2'=>'traduire',
		'4'=>'translate');
	$GLOBALS['tag_array']['Content']['admin'] = array(
		'1'=>'admin',
		'2'=>'admin',
		'4'=>'admin');
 	


?>
