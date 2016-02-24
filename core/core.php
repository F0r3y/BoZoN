<?php 
	/**
	* BoZoN core part:
	* Sets Constants, language, directories, htaccess and bozon's behaviour (files to download and files to echo)
	* @author: Bronco (bronco@warriordudimanche.net)
	**/
	
	# INIT SESSIONS VARS AND ENVIRONMENT
	define('VERSION','2.3 beta');
	include('config.php');
	start_session();
	$message='';

	# secure get / post data (normally done in auto_restrict)
	if (!empty($_GET)){$_GET=array_map('deep_strip_tags',$_GET);}
	if (!empty($_POST)){$_POST=array_map('deep_strip_tags',$_POST);}

	# locale
	if (empty($_SESSION['language'])){$_SESSION['language']=$default_language;}
	if (is_file('locale/'.$_SESSION['language'].'.php')){include('locale/'.$_SESSION['language'].'.php');}else{$lang=array();}
	# file list layout
	if (empty($_SESSION['aspect'])){$_SESSION['aspect']=$default_aspect;}
	# Current session changing theme
	if (!empty($_GET['theme'])){$_SESSION['theme']=$_GET['theme'];header('location:index.php?p='.$page.'&token='.returnToken());}
	if (empty($_SESSION['theme'])){$_SESSION['theme']=$default_theme;}
	if (empty($_SESSION['mode'])){$_SESSION['mode']=$default_mode;}

	# SESSION VARS
	# System vars
	if (empty($_SESSION['api_rss_key'])&&!empty($_SESSION['login'])){$_SESSION['api_rss_key']=hash_user($_SESSION['login']);}
	if (empty($_SESSION['stats_max_entries'])){$_SESSION['stats_max_entries']=$default_limit_stat_file_entries;}
	if (empty($_SESSION['stats_max_lines'])){$_SESSION['stats_max_lines']=$default_max_lines_per_page_on_stats_page;}
	if (empty($_SESSION['zip'])){$_SESSION['zip']=class_exists('ZipArchive');}
	if (empty($_SESSION['home'])){$_SESSION['home'] =getUrl();}	
	if (empty($_SESSION['id_file'])){$_SESSION['id_file']=$default_id_file;}
	if (empty($_SESSION['folder_share_file'])){$_SESSION['folder_share_file']=$default_folder_share_file;}
	if (empty($_SESSION['stats_file'])){$_SESSION['stats_file']=$default_stat_file;}
	if (empty($_SESSION['theme'])){$_SESSION['theme']=$default_theme;}
	if (!isset($_SESSION['current_path'])){$_SESSION['current_path']="";}
	if (!isset($_SESSION['users_rights_file'])){$_SESSION['users_rights_file']=$default_users_rights_file;}
	if (empty($_SESSION['upload_root_path'])){$_SESSION['upload_root_path']=addslash_if_needed($default_path);}
	if (empty($_SESSION['upload_user_path'])&&!empty($_SESSION['login'])){$_SESSION['upload_user_path']=$_SESSION['login'].'/';}
	
	# System Paths & files (check & create if necessary)
	if (!is_dir($_SESSION['upload_root_path'])){ mkdir($_SESSION['upload_root_path'],0744, true); }
	if (!empty($_SESSION['upload_user_path'])&&!is_dir($_SESSION['upload_root_path'].$_SESSION['upload_user_path'])){ mkdir($_SESSION['upload_root_path'].$_SESSION['upload_user_path'],0744, true); }
	if (!is_file($_SESSION['upload_root_path'].'index.html')){ file_put_contents($_SESSION['upload_root_path'].'index.html',' '); }
	if (!is_dir('thumbs/')){mkdir('thumbs/');}
	if (!is_file('thumbs/.htaccess')){file_put_contents('thumbs/.htaccess', 'deny from all');}
	if (!is_file('thumbs/index.html')){file_put_contents('thumbs/index.html',' ');}
	if (!empty($_SESSION['upload_user_path'])&&!is_dir('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'])){ mkdir('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'],0744, true); }
	if (!is_dir('thumbs/'.$_SESSION['upload_root_path'])){mkdir('thumbs/'.$_SESSION['upload_root_path']);}
	if (!is_file('thumbs/'.$_SESSION['upload_root_path'].'.htaccess')){file_put_contents('thumbs/'.$_SESSION['upload_root_path'].'.htaccess', 'deny from all');}
	if (!is_file('thumbs/'.$_SESSION['upload_root_path'].'index.html')){file_put_contents('thumbs/'.$_SESSION['upload_root_path'].'index.html',' ');}
	if (!empty($_SESSION['upload_user_path'])&&!is_file('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'].'.htaccess')){file_put_contents('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'].'.htaccess', 'deny from all');}
	if (!empty($_SESSION['upload_user_path'])&&!is_file('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'].'index.html')){file_put_contents('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'].'index.html',' ');}
	if (!is_dir('private')){mkdir('private',0744);}
	if (!is_file('private/.htaccess')){file_put_contents('private/.htaccess', 'deny from all');}
	if (!is_file($_SESSION['folder_share_file'])){save_folder_share(array());}
	if (!is_file('private/salt.php')){ file_put_contents('private/salt.php','<?php define("BOZON_SALT",'.var_export(generate_bozon_salt(),true).'); ?>'); }
	else{include('private/salt.php');}
	if (!file_exists($_SESSION['id_file'])){$ids=array();store($ids);}
	if (!is_file($_SESSION['stats_file'])){save($_SESSION['stats_file'], array());}
	if (!is_file($_SESSION['upload_root_path'].'.htaccess')){file_put_contents($_SESSION['upload_root_path'].'.htaccess', 'deny from all');}
	if (!is_file($_SESSION['users_rights_file'])){save_users_rights(array());}
	else{$users_rights=load_users_rights();}
	if (!isset($_SESSION['profile_folder_max_size'])&&!is_admin()){
		if (isset($_SESSION['login'])&&isset($users_rights[$_SESSION['login']])){
			$_SESSION['profile_folder_max_size']=$users_rights[$_SESSION['login']];
		}elseif (isset($_SESSION['login'])){
			complete_users_rights();
			$_SESSION['profile_folder_max_size']=$default_profile_folder_max_size;
		}
	}else{$_SESSION['profile_folder_max_size']=$default_profile_folder_max_size;}
	
	# Check R/W rights
	if (!is_writable('private')){echo '<p class="error">auto_restrict error: token folder is not writeable</p>';}
	if (!is_readable($_SESSION['id_file'])){$message.='<div class="error">'.e('Problem accessing ID file: not readable',false).'</div>';}
	if (!is_readable($_SESSION['stats_file'])){$message.='<div class="error">'.e('Problem accessing stats file: not readable',false).'</div>';}
	if (!is_writable($_SESSION['id_file'])){$message.='<div class="error">'.e('Problem accessing ID file: not writable',false).'</div>';}
	if (!is_writable($_SESSION['stats_file'])){$message.='<div class="error">'.e('Problem accessing stats file: not writable',false).'</div>';}
	if (!empty($_SESSION['upload_user_path'])&&!is_readable($_SESSION['upload_root_path'].$_SESSION['upload_user_path'].$_SESSION['current_path'])){$message.='<div class="error">'.e('Problem accessing '.$_SESSION['current_path'].': folder not readable',false).'</div>';}
	if (!empty($_SESSION['upload_user_path'])&&!is_writable($_SESSION['upload_root_path'].$_SESSION['upload_user_path'].$_SESSION['current_path'])){$message.='<div class="error">'.e('Problem accessing '.$_SESSION['current_path'].': folder not writable',false).'</div>';}
	

	# Libs configuration
	# Files to echo in browser (secured) 
	$behaviour['FILES_TO_ECHO']=array('nfo','m3u','txt','js','html','php','SECURED_PHP','htm','shtml','shtm','css');
	# Files to send to browser directly 
	$behaviour['FILES_TO_RETURN']=array('md','jpg','jpeg','gif','png','pdf','mp3','mp4','svg');
 	$auto_dropzone['destination_filepath']=$_SESSION['current_path'].'/';
	$auto_thumb['default_width']='64';
	$auto_thumb['default_height']='64';
	$auto_thumb['dont_try_to_resize_thumbs_files']=true;

	# CONSTANTS
	define('THEME_PATH','templates/'.$_SESSION['theme'].'/');


	include('core/templates.php');
	$ids=purgeIDs();







	#################################################
	# Functions 
	#################################################
	# Data save/load & files
	#################################################
	function load($file){return (file_exists($file) ? unserialize(gzinflate(base64_decode(substr(file_get_contents($file),9,-strlen(6))))) : array() );}
	function save($file,$data){return file_put_contents($file, '<?php /* '.base64_encode(gzdeflate(serialize($data))).' */ ?>');}
	function store($ids=null){return save($_SESSION['id_file'],$ids);}
	function unstore(){return load($_SESSION['id_file']);}
	function save_folder_share($array=null){return save($_SESSION['folder_share_file'],$array);}
	function load_folder_share(){return load($_SESSION['folder_share_file']);}
	function save_users_rights($array=null){return save($_SESSION['users_rights_file'],$array);}
	function load_users_rights(){return load($_SESSION['users_rights_file']);}
	# Delete a file or a folder and apply changes in ids file
	function delete_file_or_folder($id=null,$ids=null){
		if (empty($ids)){$ids=unstore();}
		if (empty($id)){return false;}
		$f=id2file($id);
		if(is_file($f)){
			# delete file & thumb
			unlink($f);
			$thumbfilename=get_thumbs_name($f);
			if (is_file($thumbfilename)){unlink($thumbfilename);}
			unset($ids[$id]);
			store($ids);
		}else if (is_dir($f)){
			# delete dir
			rrmdir($f);
			rrmdir('thumbs/'.$f);
			# remove all vanished sub files & folders from id file
			purgeIDs();
		}
	}
	# store all client access to a file
	function store_access_stat($file=null,$id=null){
		if (!$file||!$id){return false;}
		$host=$ref='&#8709;';
		if (isset($_SERVER['REMOTE_HOST'])){$host=$_SERVER['REMOTE_HOST'];}
		if (isset($_SERVER['HTTP_REFERER'])){$ref=$_SERVER['HTTP_REFERER'];}
		$data=array(
			'ip'=>$_SERVER['REMOTE_ADDR'],
			'host'=>$host,
			'referrer'=>$ref,
			'date'=>date('D d M, H:i:s'),
			'file'=>$file,
			'id'=>$id,
		);
		//FIXME not very good when multi-call
		$stats=load($_SESSION['stats_file']);
		if (!is_array($stats)){$stats=array();}
		if (count($stats)>$_SESSION['stats_max_entries']){
			$stats=array_values($stats);
			unset($stats[0]);
		}
		$stats[]=$data;
		save($_SESSION['stats_file'], $stats);
	}
	function addslash_if_needed($chaine){
		if (substr($chaine,strlen($chaine)-1,1)!='/'){return $chaine.'/';}else{return $chaine;}
	}
	function rename_item($file=null,$folder=''){
		if (!$file){return false;}
		if (strpos($file, '/')!==false){$file=_basename($file);}
		$folder=addslash_if_needed($folder);
		$destination=$folder.$file;
		$nb=1;
		$extension=pathinfo($file,PATHINFO_EXTENSION);
		$file2=$file;
		while (is_file($destination) || is_dir($destination)){
			$nb++;
			$add='('.$nb.')';
			if (is_file($destination)) {$file2=str_replace('.'.$extension,$add.'.'.$extension,$file);}
			else{$file2=$file.$add;}
			$destination=$folder.$file2;
		}
		return $file2;
	}
	function no_special_char($string){return preg_replace('/[\"*\/\:<>\?|]/','',$string);}
	function file_curl_contents($url,$pretend=true){
		# distant version of file_get_contents
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Charset: UTF-8'));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		if (!ini_get("safe_mode") && !ini_get('open_basedir') ) {curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);}
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		if ($pretend){curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:40.0) Gecko/20100101 Firefox/40.0');}    
		curl_setopt($ch, CURLOPT_REFERER, 'http://noreferer.com');// notez le referer "custom"
		$data = curl_exec($ch);
		$response_headers = curl_getinfo($ch);
		curl_close($ch);
		return $data;
	}
	function getUrl() {
     $url = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] : 'https://'.$_SERVER["SERVER_NAME"];
     $url .= $_SERVER["SCRIPT_NAME"];
     return $url;
    }
	function rrmdir($dir) { 
		# delete a folder and its content
	   if (is_dir($dir)) { 
	     $objects = scandir($dir); 
	     foreach ($objects as $object) { 
	       if ($object != "." && $object != "..") { 
	         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
	       } 
	     } 
	     reset($objects); 
	     rmdir($dir); 
	   } 
	}
	function _glob($path,$pattern='') {
		# glob function fallback by Cyril MAGUIRE (thx bro' ;-)
		if($path=='/'){
			$path='';
		}
	    $liste =  array();
	    $pattern=str_replace('*','',$pattern);
	    if ($handle = opendir($path)) {
	        while (false !== ($file = readdir($handle))) {
	        	if(stripos($file, $pattern)!==false || $pattern=='' && $file!='.' && $file!='..' && $file!='.htaccess') {
	                $liste[] = $path.$file;
	            }
	        }
	        closedir($handle);
	    }
		natcasesort($liste);
	    return $liste;
	   
	}
	function _basename($file){$array=explode('/',$file);if (is_array($array)){return end($array);}else{return $file;}} 
	function tree($dir='.',$files=true){
         # scann a folder and subfolders and return the tree
        if (!isset($dossiers[0]) || $dossiers[0]!=$dir){$dossiers[0]=$dir;}
        if (!is_dir($dir)&&$files){ return array($dir); }
        elseif (!is_dir($dir)&&!$files){return array();}
        $list=_glob(addslash_if_needed($dir)); 
        
        foreach ($list as $dossier) {
            $dossiers=array_merge($dossiers,tree($dossier,$files));
        }
        return $dossiers;
    }
    function only_image($tree){
    	if (is_string($tree)){$tree=tree($tree);}
    	unset($tree[0]);
    	$ext='.png .jpg .jpeg .gif .mp4';
    	foreach($tree as $file){
    		$extension=pathinfo($file,PATHINFO_EXTENSION);
    		if (!stripos($ext, $extension)){return false;}
    	}
    	return true;
    }
    function only_sound($tree){
    	if (is_string($tree)){$tree=tree($tree);}
    	unset($tree[0]);
    	$ext='.mp3 .ogg';
    	foreach($tree as $file){
    		$extension=pathinfo($file,PATHINFO_EXTENSION);
    		if (!stripos($ext, $extension)){return false;}
    	}
    	return true;
    }
	function unzip($file, $destination){ 
	    if (!class_exists('ZipArchive')){return false;}
	    $zip = new ZipArchive() ;
	    if ($zip->open($file) !== TRUE) { return false;} 
	   	$zip->extractTo($destination); 
	    $zip->close(); 
	    return true; 
	}

	function zip($source, $destination)
	{
		if (!extension_loaded('zip') || !file_exists($source)) {return false;}
		$zip = new ZipArchive();
		if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {return false;}
		$source = str_replace('', '/', realpath($source));
		if (is_dir($source) === true){
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($files as $file){
				$file = str_replace('', '/', $file);
				// Ignore "." and ".." folders
				if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) ){continue;}
				$file = realpath($file);
				if (is_dir($file) === true){
				$zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
				}else if (is_file($file) === true){
				$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
				}
			}
		}
		else if (is_file($source) === true){
			$zip->addFromString(basename($source), file_get_contents($source));
		}
	}
	
	function check_path($path){
		return (strpos($path, '//')===false && strpos($path, '..')===false && ( empty($path[0]) || (!empty($path[0]) && $path[0]!='/') ) );
	}

	function get_thumbs_name($file){
		global $auto_thumb;
		if($file[0]=='/'){
			$file=substr($file,1);
		}
		return 'thumbs/'.preg_replace('#\.(jpe?g|png|gif)#i','_THUMB_'.$auto_thumb['default_width'].'x'.$auto_thumb['default_height'].'.$1',$file);
	}

	function get_thumbs_name_gallery($file){
		global $gallery_thumbs_width;
		if($file[0]=='/'){
			$file=substr($file,1);
		}
		return 'thumbs/'.preg_replace('#\.(jpe?g|png|gif)#i','_THUMBGALLERY_'.$gallery_thumbs_width.'x'.$gallery_thumbs_width.'.$1',$file);
	}

	# IDS functions 
	############################################
	# Delete the id if it's a burn one
	function burned($id){
		if ($id[0]=='*'&&!isset($_GET['thumbs'])){
			if (!is_user_connected() || !is_owner($id)){removeID($id);}
		}
	}
	# add an item to ID file
	function addID($string,$ids=null){
		if (!$ids){$ids=unstore();}
		$id=uniqid(true);
		$ids[$id]=$_SESSION['upload_user_path'].$string;
		store($ids);
	}
	# remove an id from id file
	function removeID($id){
		$ids=unstore();
		if (!empty($ids[$id])){unset ($ids[$id]);}
		store($ids);
	}
	# remove all ids that are not actually linked to a file/folder
	function purgeIDs($ids=null){
		if (!$ids){$ids=unstore();}	
		foreach($ids as $key=>$val){
			if (empty($val)){unset($ids[$key]);}
			else{
				if (!is_file($val) && !is_dir($val)){unset($ids[$key]);}
			}
		}
		store($ids);
		return $ids;
	}
	# complete all missing ids 
	function completeID($array_of_files){
		$ids=unstore();
		$sdi=id_file_reverse($ids);// paths are keys		
		$save=false;
		$upload_path_size=strlen($_SESSION['upload_root_path'].$_SESSION['upload_user_path']);
		foreach($array_of_files as $file){
			$file=substr($file,$upload_path_size);
			if (!isset($sdi[$file])){
				$save=true;
				$id=uniqid(true);
				$ids[$id]=$_SESSION['upload_user_path'].$file;
			}
		}
		if ($save){
			store($ids);
			echo '<script>location.reload();</script>';
		}
	}
	function is_in($ext,$type){
		global $behaviour;
		if (!empty($behaviour[$type])){return array_search($ext,$behaviour[$type]);}else{return false;}

	}
	function id2file($id){
		global $ids;
		if (isset($ids[$id])){
			return $ids[$id];
		}else{
			return false;
		}
	}
	function file2id($file){
		global $ids;
		$sdi=array_flip($ids);
		if (isset($sdi[$file])){return $sdi[$file];}else{return false;}
	}

	function deep_strip_tags($var){if (is_string($var)){return strip_tags($var);}if (is_array($var)){return array_map('deep_strip_tags',$var);}return $var; }
	function visualizeIcon($extension){
		global $behaviour;
		$array=array_merge(array_flip($behaviour['FILES_TO_RETURN']),array_flip($behaviour['FILES_TO_ECHO']));
		return isset($array[$extension]);
	}
	function generate_bozon_salt($length=512){
		$salt='';
		for($i=1;$i<=$length;$i++){
			$salt.=chr(mt_rand(35,126));
		}
		return $salt;
	}
	function blur_password($pw){
		if (!empty($pw)){return hash('sha512', BOZON_SALT.$pw);}
		return false;
	}
	# to solve some problems on mime detection, fallback
	if (function_exists('mime_content_type')){
		function _mime_content_type($filename) {return mime_content_type($filename);}
	}elseif (function_exists('finfo_file')){
		function _mime_content_type($filename) {return finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $filename );}
	}else{
		function _mime_content_type($filename){
			#inspired by http://stackoverflow.com/questions/8225644/php-mime-type-checking-alternative-way-of-doing-it
		    $mime_types = array(
		        'txt' => 'text/plain',
		        'md' => 'text/plain',
 		        'nfo' => 'text/plain',
		        'htm' => 'text/html',
		        'html' => 'text/html',
		        'php' => 'text/html',
		        'css' => 'text/css',
		        'js' => 'application/javascript',
		        'json' => 'application/json',
		        'xml' => 'application/xml',
		        'swf' => 'application/x-shockwave-flash',
		        'flv' => 'video/x-flv',

		        // images
		        'png' => 'image/png',
		        'jpe' => 'image/jpeg',
		        'jpeg' => 'image/jpeg',
		        'jpg' => 'image/jpeg',
		        'gif' => 'image/gif',
		        'bmp' => 'image/bmp',
		        'ico' => 'image/vnd.microsoft.icon',
		        'tiff' => 'image/tiff',
		        'tif' => 'image/tiff',
		        'svg' => 'image/svg+xml',
		        'svgz' => 'image/svg+xml',

		        // archives
		        'zip' => 'application/zip',
		        'rar' => 'application/x-rar-compressed',
		        'exe' => 'application/x-msdownload',
		        'msi' => 'application/x-msdownload',
		        'cab' => 'application/vnd.ms-cab-compressed',

		        // audio/video
		        'mp3' => 'audio/mpeg',
		        'qt' => 'video/quicktime',
		        'mov' => 'video/quicktime',
		        'm3u' => 'audio/x-mpegurl',

		        // adobe
		        'pdf' => 'application/pdf',
		        'psd' => 'image/vnd.adobe.photoshop',
		        'ai' => 'application/postscript',
		        'eps' => 'application/postscript',
		        'ps' => 'application/postscript',

		        // ms office
		        'doc' => 'application/msword',
		        'rtf' => 'application/rtf',
		        'xls' => 'application/vnd.ms-excel',
		        'ppt' => 'application/vnd.ms-powerpoint',
		        'docx' => 'application/msword',
		        'xlsx' => 'application/vnd.ms-excel',
		        'pptx' => 'application/vnd.ms-powerpoint',


		        // open office
		        'odt' => 'application/vnd.oasis.opendocument.text',
		        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		    );

		    $ext=strtolower(pathinfo($filename,PATHINFO_EXTENSION));
			if (array_key_exists($ext, $mime_types)) {return $mime_types[$ext];} 
			else {return 'application/octet-stream';}
		}
	}


	function draw_tree($tree){
		$image_only=only_image($tree);
		$sound_only=only_sound($tree);
		if (!$image_only&&!$sound_only){
			# file list tree
			echo '<section><ul class="tree">';
			$root=explode('/',$tree[0]);$fork='&#9500;';
			$root=array_search(basename($tree[0]), $root)+1;
			$level=0;$tab=str_repeat('&nbsp;',2);
			for ($i=0;$i<count($tree);$i++){
				$branch=$tree[$i];
				if (isset($tree[$i+1])){$next=$tree[$i+1];}else{$next=false;}			
				if ($link=file2id($branch)){ 
					$ext='';
					$level=count(explode('/',$branch))-$root;
					if ($next){$next_level=count(explode('/',$next))-$root;}else{$next_level=0;}						
					if ($level<0){$level=0;}
					if ($next_level<0){$next_level=0;}

					$ext=strtolower(pathinfo($branch,PATHINFO_EXTENSION));
					$folder='';$basename=basename($branch);

					if(is_dir($branch)){
						$folder=' folder';
					}
					if ($level>$next_level || !$next){
						$fork='&#9492;';
					}else{$fork='&#9500;';}
					if ($level<$next_level){
						echo '<li>'.str_repeat('<span class="vl">'.$tab.'&#9474;'.$tab.'</span>', $level+1).'</li>';
					}
		
					echo '<li><span class="vl">'.str_repeat($tab.'&#9474;'.$tab, $level).$tab.$fork.$tab.'</span><span class="'.$ext.$folder.'"><a href="index.php?f='.$link.'">'.$basename.'</a></span></li>';
					if ($level>$next_level){echo '<li>'.str_repeat('<span class="vl">'.$tab.'&#9474;'.$tab.'</span>', $level).'</li>';}
				}
			}
			echo '</ul></section>';
		}elseif($image_only){
			# image gallery
			if (!function_exists('auto_thumb')){include('core/auto_thumb.php');}
			global $gallery_thumbs_width;
			$title=explode('/',$tree[0]);$title=$title[count($title)-1];unset($tree[0]);
			echo '<link rel="stylesheet" type="text/css" href="'.THEME_PATH.'/css/gallery.css">';
			
			echo '<section><ul class="gallery"><h1>'.$title.'</h1>';
			
			foreach($tree as $image){
				$link='index.php?f='.file2id($image);
				$file=basename($image);
				$filesize = sizeconvert(filesize($image));
				$ext=strtolower(pathinfo($image,PATHINFO_EXTENSION));
				if ($ext!='mp4'){					
					$size = getimagesize($image);
					$size=$size[0].'x'.$size[1];
					auto_thumb($image,$width=$gallery_thumbs_width,$height=$gallery_thumbs_width,$add_to_thumb_filename='_THUMBGALLERY_',$crop_image=true);
					echo '<a class="image" data-type="img" data-group="gallery" href="'.$link.'" ><img class="b-lazy" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="'.$link.'&gthumbs" alt="'.$file.'"/><span class="info"><em>'.$file.'</em> '.$size.' '.$filesize.'</span></a>';

				}else{
					$size = sizeconvert(filesize($image));
					echo '<a class="image video" data-type="" data-group="gallery" href="'.$link.'" ><img class="blank b-lazy" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="  data-src="'.THEME_PATH.'img/video.png"/><span class="info"><em>'.$file.'</em>'.$size.'</span></a>';
				}
							}
			echo '</ul></section>';
			echo '
			<script src="core/js/blazy.js"></script>
		    <script>
		        ;(function() {
		            // Initialize
		            var bLazy = new Blazy();
		        })();
		    </script>';

		}elseif($sound_only){
			# music player
			$title=explode('/',$tree[0]);$title=$title[count($title)-1];unset($tree[0]);
			echo '<link rel="stylesheet" type="text/css" href="'.THEME_PATH.'/css/music_player.css">';
			echo '<section class="music_player"><h1>'.$title.'</h1>';
			echo '<audio preload autoplay></audio>';
			foreach($tree as $sound){
				$link='index.php?f='.file2id($sound);
				$file=basename($sound);
				$ext=strtolower(pathinfo($sound,PATHINFO_EXTENSION));							
				$size = sizeconvert(filesize($sound));
				echo '<a class="sound" onclick="play(this);" href="#" data-src="'.$link.'" ><em>'.$file.'</em> '.$size.'</a>';

			}	
			echo '</section>';
			echo '
			<script src="core/js/audio.js"></script>
			<script src="core/js/playlist.js"></script>';
		}
	}
	
	function template($key,$array){
		global $templates;
		if (isset($templates[$key])){
			return str_replace(array_keys($array),array_values($array),$templates[$key]);
		}else{return false;}
	}

	function navigatorLanguage(){
		if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
			$language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			return $language{0}.$language{1};
		}else{return 'fr';}
	}



	# locales functions 
	function e($txt,$echo=true){
		global $lang;
		if (isset($lang[$txt])){$t= $lang[$txt];}else{$t= $txt;}
		if ($echo){echo $t;}else{return $t;}
	}
	function available_languages(){
		$l=_glob('locale/','php');
		foreach($l as $key=>$lang){
			$l[$key]=str_replace('.php','',basename($lang));
		}

		return $l;
	}
	# Links functions
	# create language links
	function make_lang_link($pattern='<a #CLASS href="index.php?p=#PAGE&lang=#LANG&token=#TOKEN">#LANG</a>'){
		$langs=available_languages();
		if (!empty($_GET['p'])){$page=$_GET['p'];}else{$page='';}
		if(function_exists('returntoken')){$token=returnToken();}else{$token='';}
		foreach($langs as $lang){
			if ($_SESSION['language']==$lang){$class=' class="active'.$lang.'" ';}else{$class='class="'.$lang.'" ';}
			echo str_replace(array('#CLASS','#LANG','#TOKEN','#PAGE'),array($class,$lang,$token,$page),$pattern);
		}
		
	}

	# create the connection/admin button
	function make_connect_link($label_admin='&nbsp;',$label_logout='&nbsp;',$label_login='&nbsp;'){
		if (is_user_connected()){
			if (!empty($_SESSION['login'])&&$label_admin=='&nbsp;'){$label_admin= $_SESSION['login'];}
			if(function_exists('returntoken')){$token=returnToken();}else{$token='';}
			echo '<a id="admin_button" class="btn" href="index.php?p=admin&token='.$token.'" title="'.e('Admin',false).'">'.$label_admin.'</a>';
			echo '<a id="logout_button" class="btn" href="index.php?deconnexion" title="'.e('Logout',false).'">'.$label_logout.'</a>';

		}
		else{echo '<a id="login_button" class="btn" href="index.php?p=login" title="'.e('Connection',false).'">'.$label_login.'</a>';}
	}

	# create the menu link (to change view)
	function make_menu_link($pattern='<a id="#MENU" class="#CLASS" href="index.php?p=#PAGE&aspect=#MENU&token=#TOKEN">&nbsp;</a>'){
		if(function_exists('returntoken')){$token=returnToken();}else{$token='';}
		if (!empty($_GET['p'])){$page=$_GET['p'];}else{$page='';}
		if ($_SESSION['aspect']=='icon'){$class=' active';}else{$class='';}
		echo str_replace(array('#MENU','#THEME','#TOKEN','#PAGE','#CLASS'),array('icon',THEME_PATH,$token,$page,$class),$pattern);
		if ($_SESSION['aspect']=='list'){$class=' active';}else{$class='';}
		echo str_replace(array('#MENU','#THEME','#TOKEN','#PAGE','#CLASS'),array('list',THEME_PATH,$token,$page,$class),$pattern);
	}

	# create the mode links (to change access mode)
	function make_mode_link($pattern='<a id="mode_#MODE" class="#CLASS" title="#TITLE" href="index.php?p=admin&mode=#MODE&token=#TOKEN">&nbsp;</a>'){
		if(function_exists('returntoken')){$token=returnToken();}else{$token='';}
		if ($_SESSION['mode']=='view'){$class='active';}else{$class='';}
		echo str_replace(array('#MODE','#TITLE','#TOKEN','#CLASS'),array('view',e('Manage files',false),$token,$class),$pattern);
		if ($_SESSION['mode']=='links'){$class='active';}else{$class='';}
		echo str_replace(array('#MODE','#TITLE','#TOKEN','#CLASS'),array('links',e('Manage links',false),$token,$class),$pattern);
		if ($_SESSION['mode']=='move'){$class='active';}else{$class='';}
		echo str_replace(array('#MODE','#TITLE','#TOKEN','#CLASS'),array('move',e('Move files',false),$token,$class),$pattern);
		
	}

	# Checks auto_restrict's session vars to know if a user is connected
	function is_user_connected(){
		if (empty($_SESSION['id_user'])||empty($_SESSION['login'])||empty($_SESSION['expire'])){
			return false;
		}
		return true;
	}

	# echo some classes depending on filemode, pages etc
	function body_classes(){
		if (isset($_GET['users_list'])){echo 'users_list ';}
		if (!empty($_GET['p'])){echo $_GET['p'].' ';}else{echo 'home ';}
		if (!empty($_SESSION['language'])){echo 'body_'.$_SESSION['language'].' ';}
		if (!empty($_SESSION['mode'])){echo $_SESSION['mode'].' ';}
		if (!empty($_SESSION['aspect'])&&empty($_GET['f'])){echo $_SESSION['aspect'].' ';}
	}

	# return the user's name hashed or the user's name corresponding to a hash 
	function hash_user($user_or_hash){
		if (!is_file('private/auto_restrict_users.php')){return false;}
		include ('private/auto_restrict_users.php');
		if (strlen($user_or_hash)>100){
			# hash > user
			foreach ($auto_restrict['users'] as $user=>$data){
				$hash=hash('sha512',$data['salt'].$user);
				if ($hash==$user_or_hash){return $user;}
			}
			
			return false;
		}else{
			# user > hash
			if (!empty($auto_restrict['users'][$user_or_hash])){
				return hash('sha512',$auto_restrict['users'][$user_or_hash]['salt'].$user_or_hash);
			}
			return false;
		}
	}

	# Check if current user is the id's owner 
	function is_owner($id=null){
		if (!$id || empty($_SESSION['login'])){return false;}
		$file=explode('/',id2file($id));$owner=$file[1];
		return $_SESSION['login']==$owner;
	}
	# Return id's owner 
	function return_owner($id=null){
		if (!$id){return false;}
		$file=explode('/',id2file($id));
		if (!empty($file[1])){$owner=$file[1];}
		else{$owner=e('Deleted',false);}
		
		return $owner;
	}
	# folder_size % functions
	function sizeconvert( $bytes ){
	    $label = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB' );
	    for( $i = 0; $bytes >= 1024 && $i < ( count( $label ) -1 ); $bytes /= 1024, $i++ );
	    return( round( $bytes, 2 ) . " " . $label[$i] );
	}
	function folder_size($folder,$convert=true){
		$tree=tree($folder);$size=0;
		foreach($tree as $branch){
			if (is_file(($branch))){$size+=filesize($branch);}
		}
		if ($convert){return sizeconvert($size);}
		return $size;
	}
	function folder_free($folder,$mode=false){
		if (empty($_SESSION['profile_folder_max_size'])){return false;}
		$max=($_SESSION['profile_folder_max_size']*1048576);
		$size=round($max-strval(folder_size($folder,false)),2);
		if ($size<=0){return false;}
		if (!$mode){ # converted size
			return sizeconvert($size);
		}elseif ($mode=1){ # oct
			return $size;
		}else{ # %
			return round((100*$size)/$max,1);
		}
	}
	function folder_fit($file=null,$size=null,$profile=null){
		if (!$file&&!$size||!$profile){return false;}
		$is_admin=is_admin();
		if (empty($_SESSION['profile_folder_max_size'])&&!$is_admin){return false;}
		$folder=$_SESSION['upload_root_path'].$profile;
		$max=$_SESSION['profile_folder_max_size']*1048576;
		if (!empty($file)){
			if (!is_file($file)){return false;}
			$size=filesize($file);
		}
		if (folder_size($folder,false)+$size>$max&&!$is_admin){return false;}
		return true;
	}

	function folder_usage_draw($profile,$mode=1){
		$folder=$_SESSION['upload_root_path'].$profile;
		if (!is_dir($folder)){return false;}
		if (empty($_SESSION['profile_folder_max_size'])){return false;}
		if (is_admin()){return false;}
		$free=folder_free($folder,1);
		$user_size=$_SESSION['profile_folder_max_size']*1048576;
		$used=round($user_size-$free,1);
		$usedpc=round($used*100/$user_size,1);
		$freepc=round($free*100/$user_size,1);
		if (empty($free)){$free=0;}
		
		if ($mode==1){echo '<div class="free_space_bar" ><span class="used" style="width:'.$usedpc.'%" title="'.$used.' M">'.$usedpc.'%</span><span class="free" style="width:'.$freepc.'%;" title="'.$free.' M">'.$freepc.'%</div>';}
		if ($mode==2){echo '<div class="free_space_icon btn" title="'.$freepc.'% '.e('free',false).' ('.$free.' MB)"><span class="free" style="height:'.(($freepc*32)/100).'px"></span><span class="used" style="height:'.(($usedpc*32)/100).'px"></span></div>';}
		if ($mode==3){echo '<div class="free_space_text">'.$freepc.'% '.e('free',false).' ('.sizeconvert($free).')</div>';}
	}

	function is_admin(){
		global $auto_restrict;
		if (empty($_SESSION['login'])){return false;}
		if (empty($_SESSION['admin'])){return false;}
		/*if (empty($auto_restrict)){
			$users=load_users_list();
			if (empty($users)){return false;}		
			$admin=array_keys($users);
			$admin=$admin[0];
		}else{$admin=$auto_restrict['admin']['login'];}*/
		return $_SESSION['admin']===$_SESSION['login'];	
	}

	function load_users_list(){
		global $auto_restrict;
		if (empty($auto_restrict["users"])){
			$auto_restrict_users=dirname($_SESSION['id_file']).'/auto_restrict_users.php';
			if (!is_file($auto_restrict_users)){return false;}
			include($auto_restrict_users);
		}
		return $auto_restrict["users"];
	}

	# Complete users rights
	function complete_users_rights($users_rights=null){
		global $auto_restrict,$default_profile_folder_max_size;
		$save=false;
		if (!is_admin()){return false;}	
		$users=$auto_restrict["users"];
		if (empty($users)){return false;}		
		if (!$users_rights){$users_rights=load_users_rights();}
		foreach ($users as $key=>$user){ # add missing
			if (!isset($users_rights[$user['login']])){
				$users_rights[$user['login']]=$default_profile_folder_max_size;
				$save=true;
			}
		}
		foreach ($users_rights as $user=>$size){ # remove deleted profiles
			if (!isset($users[$user])){
				unset($users_rights[$user]);
				$save=true;
			}
		}
		if ($save){save_users_rights($users_rights);}
		return $users_rights;
	}
	# creates a form with the users list
	function generate_users_folder_space_formlist($text='Users list',$text2='Check users to delete account and files'){
		global $auto_restrict,$default_profile_folder_max_size;
		if (!is_admin()){return false;}		
		$users_rights=complete_users_rights();
		echo '<h1>'.$text.'</h1><h2>'.$text2.'</h2><form action="" method="POST" class="folder_size_users_list"><table>';
		
		foreach ($users_rights as $user=>$size){
			if ($user!=$_SESSION['admin']){
					echo '<tr>';
					echo '<td><label>';
					echo '<span>'.$user.' <em>('.folder_size($_SESSION['upload_root_path'].$user).' '.e('used',false).')</em></span></td>';
					echo '<input type="hidden" name="user_name[]" value="'.$user.'"/>';
					echo '<td><input type="number" name="user_right[]" class="npt" value="'.$size.'" title="MB" max="'.$_SESSION['max_size'].'" min="0"/></td>';
					newToken();
				echo '</tr>';
			}
		}
		
		echo '</table><input type="submit" value="Ok" class="btn"/></form>';
	}

	# Lightbox generation functions
	function draw_lb(){
		echo '
		<div id="lb_overlay" >
			<div id="lb_nav">
				<a id="lb_prev" href="prev" onclick="lb_show(this,type);"></a>
				<a id="lb_next" href="next" onclick="lb_show(this,type);"></a>
				<span id="lb_close" onclick="lb_hide();"></span>
				<div id="lb_content-info"></div>	
				
			</div>
			<div id="lb_content" >
			</div>
		</div>
		';
	}
	function draw_lb_link($file,$alt=null,$text_link='&nbsp;',$group='',$type='iframe'){
		if(@is_array(getimagesize($file))){$type='img';}
		if (!empty($group)){$group='data-group="'.$group.'"';}
		echo '<a href="'.$file.'" '.$group.' onclick="lb_show(this);" data-type="'.$type.'" alt="'.$alt.'">'.$text_link.'</a>';
	}


	function start_session(){if (!session_id()){session_start();}}
	function aff($var,$stop=true){$dat=debug_backtrace();echo 'Arret ligne <em>'.$dat[0]['line'].'</em> dans le fichier <em>'.$dat[0]['file'].'</em> <pre style="background-color:rgba(0,150,200,0.5);color:#024;padding:10px">';var_dump($var);echo '</pre><pre style="background-color:rgba(150,150,0,0.5);color:#322;padding:10px">'.var_dump($dat).'</pre>';if ($stop){exit();}}
?>