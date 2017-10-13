<?php
//Elakiya K.C
//1001354817
//Assignment 5
// display all errors on the browser
error_reporting(E_ALL);
enable_implicit_flush();
ini_set('display_errors','On');


require_once("DropboxClient.php");


//DropBox CLient object creation:
$dropbox = new DropboxClient(array(
	'app_key' => "####",   
	'app_secret' => "####",
	'app_full_access' => false,
),'en');

$selected_image=null;

//load the access_token
$access_token = load_token("access");
if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
	echo "loaded access token:";
	print_r($access_token);
}

elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{
	// then load our previosly created request token
	$request_token = load_token($_GET['oauth_token']);
	if(empty($request_token)) die('Request token not found!');
	
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	store_token($access_token, "access");
	delete_token($_GET['oauth_token']);
}

// checks if access token is required
if(!$dropbox->IsAuthorized())
{
	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}

function store_token($token, $name)
{
	if(!file_put_contents("tokens/$name.token", serialize($token)))
		die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
}

function load_token($name)
{
	if(!file_exists("tokens/$name.token")) 
		return null;
	return @unserialize(@file_get_contents("tokens/$name.token"));
}

function delete_token($name)
{
	@unlink("tokens/$name.token");
}

function enable_implicit_flush()
{
	@apache_setenv('no-gzip', 1);
	@ini_set('zlib.output_compression', 0);
	@ini_set('implicit_flush', 1);
	for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
	ob_implicit_flush(1);
	echo "<!-- ".str_repeat(' ', 2000)." -->";
}


?>

<html>
<head>
<Title>DropBox</Title>
<style>
	div {
    height:100%;
}

#right{
	top:0px;
    width:500px; 
    margin:25px;
	float:right;
}

#left {
    width:500px;
    margin:25px;
	float:left;
}


</style>
</head>
<body bgcolor="#E6E6FA">
<h3 align='center'>Welcome to Dropbox</h3>
<br>
<div id="left">
<form action="album.php" method="POST" enctype="multipart/form-data">
<p> Select the image to upload in DropBox</p> 
<input type="file" name="pic" value="pic"/>
<input type="submit" name="Upload" value="Upload"/>
</form>
</div>
<?php
//List the files in Dropbox
$files = $dropbox->GetFiles("",false);

if(empty($files)) {
   $dropbox->UploadFile("leonidas.jpg");
   $files = $dropbox->GetFiles("",false);
 }
print "<div id='right'>";
print "List of files available in Dropbox:";
$file_lists=array_keys($files);
print '<table border=1>';
foreach ($file_lists as $image)
{   
	$download_link='album.php?download='.$image;
	print '<tr>';
	print '<td>'.$image.'</td>';
	print "<td><a href='".$download_link."'>Download</td>";
	print "<td><form action='album.php' method='POST' enctype='multipart/form-data'>
			<input type='hidden' name='Delete' value='".$image."'>
			<input type='submit' value='Delete'/>
			</form></td>";
	print '</tr>';
}
print '</table><br>';


if(isset($_GET['download'])) 
{
	$download_file=$_GET['download'];
	$download_file1 = "download_".basename($download_file);
	$dropbox->DownloadFile($download_file, $download_file1);
	print $download_file."\r downloaded successfully";
	print "<br>";
	$selected_image=$download_file;
}

if(isset($_POST["Upload"]))
{   
	//print "Inside if of upload";
	print "<br>";
	$file_name = $_FILES['pic']['name'];
	$file_ext=$_FILES['pic']['type'];
	print "Upload file:".$file_name;
    $accepted_extn= array("image/jpeg","image/jpg");
      
    if(in_array($file_ext,$accepted_extn)=== false){
		print "<p>Please upload image files only</p>";
    }
	else
	{
	//Upload file into dropbox
	//$file_content=file_get_contents($file_name);//Read the content of the file
	$file_upload=$dropbox->UploadFile($_FILES["pic"]["tmp_name"], $file_name);
	print '<p>File uploaded successfully</p>';
	}
	
}

if(isset($_POST['Delete']))
{
	$dropbox->Delete($_POST['Delete']);
	print "Deleted the image successfully";
}

if(is_null($selected_image))
	print "<p>No image selected</p>";
else
{
	$img_src=$dropbox->GetLink($selected_image,false);
	print "<img src='".$img_src."' height='150' width='150'/><br>";
}
		
print '</div>';
?>

</body>
</html>
