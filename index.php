<?php
/*
	@author		:	Giriraj Namachivayam
	@date 		:	Mar 01, 2013
	@demourl	:	http://ngiriraj.com/socialMedia/fbidcard/
	@document	:	http://ngiriraj.com/work/
*/


# Debug
ini_set('display_errors',1); 
error_reporting(E_ALL);
 
/* CONFIGURE */
$facebookAppId      = '321176691239227'; 
$facebookAppSecret  = 'xxxxxxxxxxxxxxxxxxxxxxxxx'; 
$return_url         = 'http://ngiriraj.com/socialMedia/fbidcard/';  		#Redirect_uri
$idDir		  = 'fbcards/'; 								#Dir path to store ID card images
$fbPermissions      = 'publish_stream,user_hometown,user_birthday';  		#Required facebook permissions
$fbTemplate_img     = 'fbidcard.png'; 							#Facebook standared id card image template
$font               = 'DidactGothic.ttf'; 						#Font used


//include facebook SDK
include_once("src/facebook.php"); 

//Facebook API
$facebook = new Facebook(array(
  'appId'  => $facebookAppId,
  'secret' => $facebookAppSecret,
));


if(isset($_GET["logout"]) && $_GET["logout"]==true)
{
    //Destroy the current session and logout user
    $facebook->destroySession();
    header('Location: '.$return_url);
}

//Get facebook user
$fbuser = $facebook->getUser();

# Check Existing user or not
$idExists = $idDir.'id_'.$fbuser.'.jpg';
if (file_exists($idExists)) {
	echo '<div>Thank you again!! <br>Your FB ID Card already uploaded to your album. Please check it<br>'.' [<a href="?logout=true">Log Out</a>]</div>'; 
    echo '<img src="'.$idDir.'id_'.$fbuser.'.jpg" >';
    exit();
} 

//check user session
if(!$fbuser) 
{
	// Login button
    $loginUrl = $facebook->getLoginUrl(array('scope' => $fbPermissions,'redirect_uri'=>$return_url));
    echo '<div style="text-align:center;">';
	echo '<a href="'.$loginUrl.'"><img src="facebook-login.png" /></a>';
	echo '</div>';
}
else
{    
     //get user profile
     try {
        $fb_profile = $facebook->api('/me');
        
        //list of user permissions
        $user_permissions = $facebook->api("/me/permissions"); 
      } catch (FacebookApiException $e) {
        echo $e;
        $fbuser = null;
      }
     
	  //Login url
	  $loginUrl = $facebook->getLoginUrl(array('scope' => $fbPermissions,'redirect_uri'=>$return_url)); 
	  
	  //Permission required
	  $permissions_needed = explode(',',$fbPermissions); 
	  
	  //Permission checker
	  foreach($permissions_needed as $per) 
	  {
		if (!array_key_exists($per, $user_permissions['data'][0])) { 
			die('<div>We need additional '.$per.' permission to continue, <a href="'.$loginUrl.'">click here</a>!</div>');
		}
	  }
	  
	  /*
	  Simply you can use "copy" cmd with following URL
	  https://graph.facebook.com/1207059/picture?width=121&height=100
	  
	  
	  If you are restricted for "allow_url_fopen" by PHP.INI files, 
	  Use following fql query and get the FB profile image path. Download with the help of cURL.

	  [REF] : http://stackoverflow.com/questions/11743768/how-to-get-facebook-profile-large-square-picture 
	  */
	  $fql    =   "SELECT url, real_width, real_height FROM profile_pic WHERE id=me()  AND width=100  AND height=150";
				$param  =   array(
						'method'    => 'fql.query',
						'query'     => $fql,
						'callback'  => ''
					);
	  $fqlResult   =   $facebook->api($param);
     

	//Alternative Image Saving Using cURL seeing as allow_url_fopen is disabled - bummer
	function save_image($img,$fullpath='profile'){
		$ch = curl_init ($img);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		$rawdata=curl_exec($ch);
		curl_close ($ch);
		if(file_exists($fullpath)){
			unlink($fullpath);
		}
		$fp = fopen($fullpath,'x');
		fwrite($fp, $rawdata);
		fclose($fp);

	}

	//Download user profile image
	//copy($fbProfileImagefile,$fbTemplatefile);
	save_image($fqlResult[0]['url'],$idDir.$fbuser.'.jpg');    
   
    	//display logout url
    	echo '<div>'.$fb_profile["name"].' [<a href="?logout=true">Log Out</a>]</div>'; 


	// Get Friends count and wall count
	$fql_count    =   "SELECT uid,friend_count,wall_count FROM user where uid=me()";
			$param  =   array(
					'method'    => 'fql.query',
					'query'     => $fql_count,
					'callback'  => ''
				);
	$fqlResult1   =   $facebook->api($param);
	    
    
    	$fbTemplate = imagecreatefrompng($fbTemplate_img); // FB Template - PNG file format
    	$fbProfileImage = imagecreatefromjpeg($idDir.$fbuser.'.jpg'); // Profile image stored in ID Card folder - jpg file format
    
    	imagealphablending($fbTemplate, false); 
    	imagesavealpha($fbTemplate, true);
    	imagecopymerge($fbTemplate, $fbProfileImage, 15, 80, 0, 0, 100, 150, 100);  # profile image merge with FB Template
    	
	#Text color Theme
    	$facebook_blue = imagecolorallocate($fbTemplate, 81, 103, 147); // Create blue color
    	$facebook_grey = imagecolorallocate($fbTemplate, 74, 74, 74); // Create grey color
    
	imagealphablending($fbTemplate, true);    
	
	#FB User Information
    	$fb_user_id        = $fbuser;
    	$fb_user_name      = isset($fb_profile['name'])?$fb_profile['name']:'No Name';
    	$fb_user_gender    = isset($fb_profile['gender'])?$fb_profile['gender']:'No gender';
    	$fb_user_hometown  = isset($fb_profile['hometown'])?$fb_profile['hometown']['name']:'Unknown';
    	$fb_user_birth     = isset($fb_profile['birthday'])?$fb_profile['birthday']:'00/00/0000';
    	$fb_disclaimer         = '* Un-official facebook ID card from http://ngiriraj.com';
    
    	# Embed informations to FB ID Card	
    	imagettftext($fbTemplate, 12, 0, 130, 107, $facebook_grey, $font, $fb_user_name); // Name
    	imagettftext($fbTemplate, 12, 0, 130, 145, $facebook_grey , $font, $fb_user_id); // ID    
    	imagettftext($fbTemplate, 12, 0, 130, 187, $facebook_grey, $font, $fb_user_gender); //Gender
    	imagettftext($fbTemplate, 12, 0, 273, 187, $facebook_grey, $font, $fb_user_birth); //DOB
    	imagettftext($fbTemplate, 12, 0, 130, 225, $facebook_grey, $font, $fb_user_hometown); //Location
    	imagettftext($fbTemplate, 12, 0, 273, 225, $facebook_grey, $font, $fqlResult1[0]['friend_count']); //Friend count
    	imagettftext($fbTemplate, 12, 0, 365, 225, $facebook_grey, $font, $fqlResult1[0]['wall_count']); //Wall count
    	imagettftext($fbTemplate, 6, 0, 130, 238, $facebook_blue, $font, $fb_disclaimer); //message
        
	# FB ID Card save into idDir folder	
    	imagepng($fbTemplate, $idDir.'id_'.$fbuser.'.jpg');
	
	# Display FB ID CARD
    	echo '<img src="'.$idDir.'id_'.$fbuser.'.jpg" >';
    
	# DELETE Profile image (due to space issue)
    	unlink($idDir.$fbuser.'.jpg');    
    
	# Error : Uncaught OAuthException: (#200) The user hasn't authorized the application to perform this action thrown in ...
    	# [REF] http://stackoverflow.com/questions/9996859/oauthexception-when-posting-to-facebook-wall-using-php/10651503#10651503
	$facebook->setFileUploadSupport(true);
	
	
	# FB ALBUM CREATE
	$post_data = array(
		'name'=>"ngiriraj",
		'description'=>"ID Card Maker"
    	);
	$data['album'] = $facebook->api("/me/albums", 'post', $post_data); // You will get Album ID
	$album_id = $data['album']['id'];
	
	# GET Full path
	$file = $_SERVER['DOCUMENT_ROOT'] . "/socialMedia/fbidcard/".$idDir."id_".$fbuser.".jpg";  // Change your folder path
	
	# UPLOAD TO FB ALBUM and POST in FB user wall.
	$post_data = array(
		"message" => "Hurry up! Get your FaceBook ID Card from http://ngiriraj.com/socialMedia/fbidcard/",
		"source" => '@' . realpath($file)
	);
	$data['photo'] = $facebook->api("/$album_id/photos", 'post', $post_data);  
	
    	imagedestroy($fbTemplate);
    	imagedestroy($fbProfileImage);
}
?>