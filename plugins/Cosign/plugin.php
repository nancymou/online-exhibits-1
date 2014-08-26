<?php
 add_plugin_hook('initialize', 'cosign_initialize');
 //add_filter('admin_whitelist','addToWhitelist');
 add_plugin_hook('install', 'cosign_install');
 
 add_filter('login_adapter', 'login');
 add_filter('login_form', 'loginf');
 add_plugin_hook('define_acl', 'cosign_define_acl');
 // This event will be triggered when save button in the Exhibit metadata form clicked.
 add_plugin_hook('after_save_exhibit','cosign_save_exhibit');
 // This event will be triggered when save button in the pages in exhibit is clicked.
 //add_plugin_hook('after_save_exhibit_page','cosign_save_exhibit_page');
 
 add_plugin_hook('before_delete_user','cosign_delete_user_from_group');
 add_plugin_hook('after_delete_exhibit', 'cosign_delete_exhibit');
// add_plugin_hook('before_delete_exhibit', 'cosign_delete_exhibit_page');
 

 function cosign_define_acl($args){
  $acl = $args['acl'];
	require_once dirname(__FILE__).'/GroupCosignAssertion.php';	
	$acl->allow(null, 'ExhibitBuilder_Exhibits', array('edit','delete'),
		     new GroupCosignAssertion);
	$acl->allow('contributor', 'Items', array('makePublic'));
 }
 
 

function cosign_delete_exhibit_page($args){
		$exhibit = $args['record'];	
		$newimageexhibitrelationship = new CosignImagexhibitrelationship;
		$newimageexhibitrelationship->deleteImagexhibitrelationshipRecords($exhibit->id);	
 }
 
/*function cosign_save_exhibit_page($args){
  $exhibit_page = $args['record'];
  $post = $args['post'];  		
  $exhibit_record = $exhibit_page->getExhibit($exhibit_page->id);
  $Exhibit_image = cosign_exhibit_image_item($exhibit_record, $post['File']);

  if (!empty($Exhibit_image)){
		$newimageexhibitrelationship = new CosignImagexhibitrelationship;
		$newimageexhibitrelationship->deleteImagexhibitrelationshipRecords($exhibit_record->id);
		$newimageexhibitrelationship->entity_id = $exhibit_record->id;
		$newimageexhibitrelationship->image_name = $Exhibit_image['image'];
		$newimageexhibitrelationship->image_title = $Exhibit_image['title'];
		$newimageexhibitrelationship->save();		
	}		  
}*/
   
function cosign_delete_exhibit($args){		
	  $exhibit = $args['record'];
	  $newimageexhibitrelationship = new CosignImagexhibitrelationship;
	  $newimageexhibitrelationship->deleteImagexhibitrelationshipRecords($exhibit->id);			
		// exit;
	 	$newgroupsexhibitrelationship = new CosignGroupexhibitrelationship;
		$newgroupsexhibitrelationship->deleteGroupexhibitrelationshipRecords($exhibit->id);
}


function cosign_delete_user_from_group($args){
   $user = $args['record'];
   $grouping = new CosignGrouping;
   $grouping->deleteGroupingRecords($user->id);
}

function cosign_install(){
    $db = get_db();
    $db->query("CREATE TABLE IF NOT EXISTS `{$db->prefix}group_exhibit_relationship` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `exhibit_id` int(10) unsigned NOT NULL,
        `group_id` int(10) unsigned NOT NULL,
         PRIMARY KEY (`id`)  
       ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");     
  }
 
function cosign_save_exhibit($args){ 

  $exhibit = $args['record'];
  $Exhibit_image = cosign_exhibit_image_file($exhibit);

  $newimageexhibitrelationship = new CosignImagexhibitrelationship;
  $newimageexhibitrelationship->deleteImagexhibitrelationshipRecords($exhibit->id);	
  if (!empty($Exhibit_image)){
		$newimageexhibitrelationship->entity_id = $exhibit->id;
		$newimageexhibitrelationship->image_name = $Exhibit_image['image'];
		$newimageexhibitrelationship->image_title = $Exhibit_image['title'];
		$newimageexhibitrelationship->save();
	}
	
 //group selection then save to exhibit
 	if(!empty($_POST['group-selection'][0])){
    	print_r($_POST['group-selection'][0]);
      $exhibit = $args['record'];
		  $newgroupsexhibitrelationship = new CosignGroupexhibitrelationship;
		  $newgroupsexhibitrelationship->deleteGroupexhibitrelationshipRecords($exhibit->id);
  		$newgroupsexhibitrelationship->exhibit_id = $exhibit->id;
	  	$newgroupsexhibitrelationship->group_id = $_POST['group-selection'][0];
		  $newgroupsexhibitrelationship->save();
		  print_r($newgroupsexhibitrelationship);
		 // exit;
	}
}
 
  function cosign_exhibit_image_file($exhibit) {
	$Exhibit_image = "";
	$topPages = $exhibit->getTopPages();
	
	if (count($topPages) > 0) {
		$exhibitPage = $topPages[0];
	}
	else
		return '';


//	print_r($exhibitPage->getPageEntries());
	//exit;
//print_r($topPages[0]->getPageEntries());
//exit;
	while ($Exhibit_image == "") {
			foreach ($exhibitPage->getPageEntries() as $pageEntry){
		// retrieve the image file that is stored and check if there is a thumbnail available, if not, check the next pageEntry.
	
				if ($pageEntry->file_id){				
			  	$file = get_db()->getTable('File')->find($pageEntry->file_id);
				  $item = get_db()->getTable('Item')->find($pageEntry->item_id);
			  }
				elseif ($item = $pageEntry->Item){
						if (isset($item->Files[0])){
								$file = $item->Files[0];
		        }
				}
							
				if ((!empty($file)) and ($file->hasThumbnail())){
					$imgurl = $file->getStoragePath('fullsize');										
					$Exhibit_image = array('image'=>'/'.$imgurl,'title'=>metadata($item, array('Dublin Core', 'Title')));						
					break;
				}
				if ($Exhibit_image != "") break;
			}//for each
		
			// if page object exists, grab link to the first child page if exists. If it doesn't, grab
			// a link to the next page
  		$targetPage = null;

	  	if ($nextPage = $exhibitPage->firstChildOrNext()) {		  
				  $targetPage = $nextPage;
		  } 
		  elseif ($exhibitPage->parent_id) {	
			  $parentPage = $exhibitPage->getParent();
			  $nextParentPage = $parentPage->next();
			  if ($nextParentPage) {
				  $targetPage = $nextPage;
			  }
		  } // elseif 
		  if ($targetPage){		  	
			  $exhibitPage = $targetPage;			  
		  }
		  else{		  
			  break;
		  }
	}//while
	
return $Exhibit_image;
}
 
/* function cosign_exhibit_image_item($exhibit,$post) { 
	$Exhibit_image = "";
	$topPages = $exhibit->getTopPages();
		
	if (count($topPages) > 0) {
		$exhibitPage = $topPages[0];
	}
	else {
		return '';
	}

	while ($Exhibit_image == "") {	
		foreach ($exhibitPage->getPageEntries() as $pageEntry){

			 if ($pageEntry->item_id){
			  	$item = get_db()->getTable('Item')->find($pageEntry->item_id);
			 }
		   else {
			  	$item = null;
			 }			
			 if (count(@$item->Files) > 0){			   			   
			  	foreach ($item->Files as $file){			  
					  if ($file->hasThumbnail() and ($file['id']==$post[1])){
								$imgurl = $file->getStoragePath('fullsize');							
					     $Exhibit_image = array('image'=>'/'.$imgurl,'title'=>metadata($item, array('Dublin Core', 'Title')));
					     break;
					  }
				  }
			 }
			 if ($Exhibit_image != "") break;
		}//for each

		// if page object exists, grab link to the first child page if exists. If it doesn't, grab
		// a link to the next page
  		$targetPage = null;

	  	if ($nextPage = $exhibitPage->firstChildOrNext()) {		  
			  $targetPage = $nextPage;
		  } 
		  elseif ($exhibitPage->parent_id) {	
			  $parentPage = $exhibitPage->getParent();
			  $nextParentPage = $parentPage->next();
			  if ($nextParentPage) {
				  $targetPage = $nextPage;
			  }
		  } // elseif 
		  if ($targetPage){		  	
			  $exhibitPage = $targetPage;
		  }
		  else{		  
			  break;
		  }
	}//while	
	return $Exhibit_image;
}*/
 
  function cosign_initialize(){
	   Zend_Controller_Front::getInstance()->registerPlugin(new CosignControllerPlugin);    
  }
     
  /*function addToWhitelist($adminWhiteList){   	
	   array_push($adminWhiteList,array('controller' => 'cosign'));
	   return $adminWhiteList;	   
  }*/
 
 function loginf($loginform){
 
   if((isset($_SERVER['REMOTE_USER']))) {
      if($_SERVER['REMOTE_USER'] == 'musolffm') {
        if(strpos($_SERVER['HTTP_USER_AGENT'],"Internet Explorer") !== false) {
           $_SERVER['REMOTE_USER'] = 'user1';
           $_SERVER['ORIGINAL_USER'] = 'musolffm';
         }
          if(strpos($_SERVER['HTTP_USER_AGENT'],"Chrome") !== false) {
           $_SERVER['REMOTE_USER'] = 'user2';
           $_SERVER['ORIGINAL_USER'] = 'musolffm';
         }
       } 
      if($_SERVER['REMOTE_USER'] == 'nancymou') {
        if(strpos($_SERVER['HTTP_USER_AGENT'],"Safari") !== false) {
           $_SERVER['REMOTE_USER'] = 'jlausch';
           $_SERVER['ORIGINAL_USER'] = 'nancymou';
         }          
       } 
    
	     $_POST['username']= $_SERVER['REMOTE_USER'];
	     $_POST['password']='dd';
	     $_SERVER['REQUEST_METHOD']='POST';
	     return $loginform; 
	 }
	 else {
	   $redirected_url = 'https://'.$_SERVER['SERVER_NAME'].'/online-exhibits-o2/admin/';
      header('location: '.$redirected_url); 
	  } 		 
}

function login($authAdapter,$loginForm) {    
    if(isset($_SERVER['REMOTE_USER'])) {
        $username = $_SERVER['REMOTE_USER'];
        $pwd = '';
        $authAdapter = new Omeka_Auth_Adapter_Cosign($username,$pwd);
        return $authAdapter; 
    }
    else {
       $redirected_url = 'https://'.$_SERVER['SERVER_NAME'].'/online-exhibits-o2/admin/';
       header('location: '.$redirected_url);  
    }
 }
 
 
 class Omeka_Auth_Adapter_Cosign implements Zend_Auth_Adapter_Interface {	
	private $omeka_userid;
		
	public function __construct($username,$password) {
		$this->omeka_userid = $username;
	}
		
	public function authenticate() {
        // Omeka needs the user ID (not username)
        $omeka_user = get_db()->getTable('User')->findBySql("username = ?", array($this->omeka_userid), true);
        if ($omeka_user) {
        	$id = $omeka_user->id;
        	$correctResult = new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $id,array("good job"));
        	return $correctResult;	
        }
        else {
        	$messages = array();
        	$messages[] = 'Login information incorrect. Please try again.';
        	$authResult = new Zend_Auth_Result(Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND, $this->omeka_userid , $messages);
        	return $authResult;
        }
	}		
}
	
 
   
class CosignControllerPlugin extends Zend_Controller_Plugin_Abstract {	
          public function routeStartup(Zend_Controller_Request_Abstract $request) {
    	      $router = Zend_Controller_Front::getInstance()->getRouter();        	   
        		$route = new Zend_Controller_Router_Route(
   				  'users/logout',
    				 array(
    					'module'     => 'cosign', 
        				'controller' => 'cosign',
       					'action'     => 'logout'
   		      )); 
      		  $router->addRoute('logoutCosignUser', $route);
      		  
      	   $route = new Zend_Controller_Router_Route(
   				 'users/add/',
    				array(
    					'module'       => 'cosign', 
        				'controller' => 'cosign',
       					'action'     => 'add'
   		      ));
  	      	$router->addRoute('addCosignUser', $route); 	  		
  	      	
  	      	 $route = new Zend_Controller_Router_Route(
   				 'users/edit/:id',
    				array(
    					'module'       => 'cosign', 
        				'controller' => 'cosign',
       					'action'     => 'edit'
   		      ));
  	      	$router->addRoute('editCosignUser', $route);   
  	      	
  	      	
  	    /* 	 $route = new Zend_Controller_Router_Route(
   				 'exhibits/edit-page-content/:id',
    				array(
    					'module'       => 'cosign', 
        				'controller' => 'exhibit',
       					'action'     => 'edit'
   		      ));
  	      	$router->addRoute('editCosignExhibit', $route);   	*/
  	      	
  	      	    		
    }
 }
