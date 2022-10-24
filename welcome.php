<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$username = $_SESSION["username"];

require_once "config.php";

$root = "";

$perm = 0;

function mysqli_result($res, $row, $field=0) {
    $res->data_seek($row);
    $datarow = $res->fetch_array();
    return $datarow[$field];
}

function checkPerm() {
	global $username;
	global $link;
	global $perm;
	global $root;
	
	$sql = "SELECT perm FROM users WHERE username='$username'";
	$result = $link->query($sql);
	$perm = mysqli_result($result, 0);
	
	if ($perm == 1) $root = "/d/arfCloudStorage/" . $username;
	if ($perm == 2) $root = "/d/arfCloudStorage/" . $username;
	if ($perm == 15) $root = "/d/arfCloudStorage/";
}

function printPerm() {
	global $perm;
	if ($perm == 0) echo "Account not validated, ask arf20 to validate your account.";
	else if ($perm == 1) echo "Read-only user.";
	else if ($perm == 2) echo "Standard user.";
	else if ($perm == 15) echo "Admin";
}

checkPerm();

function check($str) {
	if (strpos($str, "..") || strpbrk($str, "\"\'<>\\")) {
		die("No .. or special charecters allowed, fuck you.");
	}
}

if (!isset($_GET["path"]))
	$navdir = "/";
else
	$navdir = $_GET["path"];


// Forms

$width = 400;

function printForms() {
	global $perm;
	global $navdir;
	/*echo "<div class=\"form\"><form action=\"";
		echo htmlspecialchars($_SERVER["PHP_SELF"]);
		echo "?path=$navdir\" method=\"post\" enctype=\"multipart/form-data\">\n";*/
	if ($perm > 1) {
		echo "<div class=\"form\"><form onsubmit=\"return onUpload()\" enctype=\"multipart/form-data\">\n";
		echo "	<label>Upload file</label><br>\n";
		echo "	<input type=\"file\" name=\"fileToUpload\" id=\"fileToUpload\"><br>";
		echo "	<input type=\"submit\" value=\"Upload\" name=\"upload\" onclick=\"onUpload()\">";
		echo "	<span id=\"progress\"></span><span id=\"speed\"></span>";
		echo "</form></div>";
		
		echo "<div class=\"form\"><form action=\"";
		echo htmlspecialchars($_SERVER["PHP_SELF"]);
		echo "?mkdir=$navdir\" method=\"post\">\n";
		echo "	<label>Create directory</label><br>\n";
		echo "	<input type=\"text\" name=\"dirname\" id=\"fileToUpload\"><br>";
		echo "	<input type=\"submit\" value=\"Create\" name=\"mkdir\">";
		echo "</form></div>";
	}
}

// Upload file

if (isset($_POST["upload"])) {
	global $perm;
	$target_dir = "";
	if ($perm == 2) $target_dir = $root . "/" . $_GET["path"];
	if ($perm == 15) $target_dir = $root . $_GET["path"];
	$target_file = $target_dir . "/" . basename($_FILES["fileToUpload"]["name"]);
	check($target_file);
	move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);
	echo "$perm Uploading to " . $target_file;
	//die('<meta http-equiv="refresh" content="0; URL=../main">');
}

// Make dir

if (isset($_POST["mkdir"])) {
	global $perm;
	$target_dir = "";
	
	if ($perm == 2) $target_dir = $root . $_GET["mkdir"] . "/" . $_POST["dirname"];
	if ($perm == 15) $target_dir = $root . $_GET["mkdir"] . "/" . $_POST["dirname"];

	check($target_dir);

	//echo "Creating dir " . $target_dir;
	mkdir($target_dir);
	
	if ($perm == 2) $navdir = $_GET["mkdir"];
	if ($perm == 15) $navdir = $_GET["mkdir"];
	
	die('<meta http-equiv="refresh" content="0; URL=?path=' . $navdir . '">');
}

// ==========================
// Download

if (isset($_GET["downld"])) {
	$file = $root.$_GET["downld"];
	
	check($file);
	
	if (file_exists($file)) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($_GET["downld"]).'"');
		header('Content-Length: ' . filesize($file));
		header('Pragma: public');
		
		flush();
		
		readfile($file, true);
		
		echo "Downloading " . $_GET["downld"];
		
		die();
	} else echo "Error: file doesn't exists";
	
	//die("<meta http-equiv=\"refresh\" content=\"0; URL=?path=$path\">");
}

// Delete button

function rrmdir($dir) { 
	if (is_dir($dir)) { 
		$objects = scandir($dir);
		foreach ($objects as $object) { 
			if ($object != "." && $object != "..") { 
				if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
					rrmdir($dir. DIRECTORY_SEPARATOR .$object);
				else
					unlink($dir. DIRECTORY_SEPARATOR .$object); 
			} 
		}
		rmdir($dir); 
	} 
}

if (isset($_GET["del"])) {
	//echo $_GET["del"] . "<br>";
	if ($perm == 2) $navdir = "/" . substr($_GET["del"], 0, strrpos($_GET["del"], "/", -2));
	if ($perm == 15) $navdir = substr($_GET["del"], 0, strrpos($_GET["del"], "/", -2));
	
	if ($perm == 2) $target = $root . $_GET["del"];
	if ($perm == 15) $target = $root . $_GET["del"];
	
	//echo "path: " . substr($_GET["del"], 0, strrpos($_GET["del"], "/", -1)) . "<br>";
	//echo "path: $navdir";
	//echo "del: " . $target;	
	
	if (is_dir($target))
		rrmdir($target);
	else
		unlink($target);
		
		
	
	die("<meta http-equiv=\"refresh\" content=\"0; URL=?path=$navdir\">");
}


// ===================== LISTING =====================
function formatBytes($size, $precision = 2){
	if ($size == 0) return "0";
	
	$base = log($size, 1024);
	$suffixes = array('', 'K', 'M', 'G', 'T');

	return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}

function getDirList($dir) {
	global $root;
	$dir = $root . $dir;
	
	check($dir);
	
	//echo $root;
	
    // array to hold return value
    $retval = [];

    // add trailing slash if missing
    if(substr($dir, -1) != "/") {
      $dir .= "/";
    }

    // open pointer to directory and read list of files
    $d = @dir($dir) or die("getFileList: Failed opening directory {$dir} for reading");
    while(FALSE !== ($entry = $d->read())) {
      // skip hidden files
      if($entry[0] == ".") continue;
      if(is_dir("{$dir}{$entry}")) {
        $retval[] = [
          'name' => "{$dir}{$entry}/",
          'type' => filetype("{$dir}{$entry}"),
          'size' => 0,
          'lastmod' => filemtime("{$dir}{$entry}")
        ];
      } elseif(is_readable("{$dir}{$entry}")) {
        $retval[] = [
          'name' => "{$dir}{$entry}",
          'type' => mime_content_type("{$dir}{$entry}"),
          'size' => filesize("{$dir}{$entry}"),
          'lastmod' => filemtime("{$dir}{$entry}")
        ];
      }
    }
    $d->close();

    return $retval;
}

function printDirList($dirlist) {
	global $navdir;
	global $width;
	global $perm;
	global $username;
	
	echo "<h3>Index of $navdir</h3>";
	echo "<table border=\"1\">\n";
	echo "<thead>\n";
	echo "<tr><th style=\"width: {$width}px;\">Name</th><th>Type</th><th>Size</th><th>Last Modified</th><th>Actions</th></tr>\n";
	echo "</thead>\n";
	echo "<tbody>\n";
	
	echo "<tr>\n";
	echo "<td style=\"width: {$width}px;\"><a href=\"?path=/";
	echo substr($navdir, 1, strrpos($navdir, "/", -2));
	echo "\">";
	echo "..";
	echo "</a></td>\n";
	echo "<td>dir</td>\n";
	echo "<td></td>\n";
	echo "<td></td>\n";
	echo "<td></td>\n";
	echo "</tr>\n";
	
	foreach($dirlist as $file) {
		if ($file['type'] == "dir") {
			echo "<tr>\n";
			echo "<td style=\"width: {$width}px;\"><a href=\"?path=/";
			if ($perm == 1 || $perm == 2) echo substr($file['name'], 1 + strpos($file['name'], $username) + strlen($username)) . "\">";
			if ($perm == 15) echo substr($file['name'], 20) . "\">";
			echo substr($file['name'], 1 + strrpos($file['name'], "/", -2)) . "</a></td>\n";
			echo "<td>{$file['type']}</td>\n";
			echo "<td></td>\n";
			echo "<td>",date('r', $file['lastmod']),"</td>\n";
			if ($perm == 1) echo "<td>";
			if ($perm == 2) echo "<td><a href=\"?del=/" . substr($file['name'], 1 + strpos($file['name'], $username) + strlen($username), -1) . "\">Delete</a>";
			if ($perm == 15) echo "<td><a href=\"?del=/" . substr($file['name'], 20, -1) . "\">Delete</a>";
			echo "</td></tr>\n";
		} else {
			echo "<tr>\n";
			echo "<td style=\"width: {$width}px;\"><a href=\"?downld=/";
			if ($perm == 1 || $perm == 2) echo substr($file['name'], 1 + strpos($file['name'], $username) + strlen($username)) . "\">";
			if ($perm == 15) echo substr($file['name'], 20) . "\">";
			echo substr($file['name'], 1 + strrpos($file['name'], "/", -1)) . "</a></td>\n";
			echo "<td>{$file['type']}</td>\n";
			echo "<td>" . formatBytes($file['size']) . "</td>\n";
			echo "<td>",date('r', $file['lastmod']),"</td>\n";
			if ($perm == 1) echo "<td>";
			if ($perm == 2) echo "<td><a href=\"?del=/" . substr($file['name'], 1 + strpos($file['name'], $username) + strlen($username)) . "\">Delete</a>";
			if ($perm == 15) echo "<td><a href=\"?del=/" . substr($file['name'], 20) . "\">Delete</a>";
			echo "</td></tr>\n";
		}
	}
	echo "</tbody>\n";
	echo "</table>\n\n";
}

//echo $perm;
  
?>
 
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>arfCloud</title>
		<style type="text/css">
			table {
				table-layout: fixed;
				//width: 90%;
			}
			
			.form {
				border-style: double;
				padding: 10px;
				margin-top: 10px;
				<?php echo "width: ".(string)($width  - 20)."px"; ?>
			}
		</style>
		<link rel="stylesheet" type="text/css" href="/style.css">
		<script>
			function formatBytes(size, precision){
				if (size == 0) return "0";
				
				var base = Math.log(size) / Math.log(1024);
				var suffixes = ['', 'K', 'M', 'G', 'T'];

				return (Math.round(Math.pow(1024, base - Math.floor(base)), precision)).toString() + " " + suffixes[Math.floor(base)].toString();
			}
		
			function onUpload() {
				  // (A) FILE UPLOAD
				  var data = new FormData(),
				  upfile = document.getElementById("fileToUpload").files;
				  data.append("fileToUpload", upfile[0]);
				  data.append("upload", "xd");
				  // data.append("KEY", "VALUE");
				 
				  // (B) AJAX
				  var xhr = new XMLHttpRequest();
				  xhr.open("POST", <?php echo "\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?path=$navdir\""; ?>);
				 
				  // (C) UPLOAD PROGRESS
				  var percent = 0;
				  var uploaded = 0;
				  var newuploaded = 0;
				  progress = document.getElementById("progress");
				  speed = document.getElementById("speed");
				 
				  xhr.upload.onloadstart = function(evt){
					progress.innerHTML = "Starting";
				  };
				  
				  function updateSpeed() {
					  var diffuploaded = newuploaded - uploaded;
					  uploaded = newuploaded;
					  speed.innerHTML = formatBytes(diffuploaded, 3) + "B/s";
					  console.log(formatBytes(diffuploaded) + "B/s");
					  setTimeout(updateSpeed, 1000);
				  }
				 
				  xhr.upload.onprogress = function(evt){
					var newpercent = Math.ceil((evt.loaded / evt.total) * 100);
					newuploaded = evt.loaded;
					if (newpercent > percent) {
						//console.log(String(percent) + " " + String(newpercent));
						percent = newpercent;
						progress.innerHTML = percent + "% ";
						
					}
				  };
				 
				  xhr.upload.onloadend = function(evt){
					progress.innerHTML = "Done";
				  };

				  // (D) ON UPLOAD COMPLETE
				  xhr.onload = function(){
					console.log(this);
					console.log(this.response);
					console.log(this.status);
					location.reload();
				  };
				 
				  // (E) GO!
				  xhr.send(data);
				  updateSpeed();
				  
				  return false;
			}
		</script>
	</head>
	<body>
		<div>
			<h1>arfCloud</h1>
			<a href="/arfCloud/disclaimer.txt">Disclaimer</a>
		</div>
		<p><a href="logout.php">Log Out</a></p>
		<P><?php printPerm(); ?></p>
			<?php printForms(); ?>
		
		<?php
			if ($perm > 0) printDirList(getDirList($navdir));  
		?>
	</body>
</html>
