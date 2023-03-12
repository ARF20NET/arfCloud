<?php
// Include config file
require_once "config.php";
 
// Define variables and initialize with empty values
$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = "";
 
// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate username
    if (empty(trim($_POST["username"])))
        $username_err = "Please enter a valid username, or fuck you.";
    else if (strpbrk(trim($_POST["username"]), "\"\'<>/\\") != false)
		$username_err = "Username must not contain special caracters (fuck you if you are trying injection).";
	else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
				} else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "SQL failed. Idk ask arf20.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"])))
        $password_err = "Please enter a password.";     
    else if(strlen(trim($_POST["password"])) < 6)
        $password_err = "Password must have atleast 6 characters.";
	else if(strpbrk(trim($_POST["password"]), "\"\'<>\\") != false)
		$password_err = "Password must not contain special caracters (fuck you if you are trying injection).";
    else
        $password = trim($_POST["password"]);
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"])))
        $confirm_password_err = "Please confirm password.";     
    else if(strpbrk(trim($_POST["confirm_password"]), "\"\'<>\\") != false)
		$confirm_password_err = "Password must not contain special caracters (fuck you if you are trying injection).";
    else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
         
        if ($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_password);
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)){
                // Redirect to login page
                header("location: login.php");
            } else {
                echo "SQL failed. Idk ask arf20.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
			
			// Create user directory
			mkdir("/d/arfCloudStorage/" . $username);
        }
    }
    
    // Close connection
    mysqli_close($link);
}
?>
 
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>Sign Up</title>
		<link rel="stylesheet" type="text/css" href="arfCloud.css">
		<link rel="stylesheet" type="text/css" href="/style.css">
	</head>
	<body>
		<div class="wrapper">
			<h2>arfCloud Sign Up</h2>
			<p>For those who don't want their data sold by Google</p>
			<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
				<div class="form-group row <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
					<div class="column"><label>Username</label></div>
					<div class="column"><input type="text" name="username" class="form-control" pattern="[a-zA-Z0-9_]+" value="<?php echo $username; ?>"></div>
					<span class="help-block"><?php echo $username_err; ?></span>
				</div>    
				<div class="form-group row <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
					<div class="column"><label>Password</label></div>
					<div class="column"><input type="password" name="password" class="form-control" pattern="[a-zA-Z0-9_!@^*$%&)(=+çñÇ[]{}-.,_:;]+" value="<?php echo $password; ?>"></div>
					<span class="help-block"><?php echo $password_err; ?></span>
				</div>
				<div class="form-group row <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
					<div class="column"><label>Confirm Password</label></div>
					<div class="column"><input type="password" name="confirm_password" class="form-control" pattern="[a-zA-Z0-9_!@^*$%&)(=+çñÇ[]{}-.,_:;]+" value="<?php echo $confirm_password; ?>"></div>
					<span class="help-block"><?php echo $confirm_password_err; ?></span>
				</div>
				<div class="form-group">
					<input type="submit" class="btn btn-primary" value="Submit">
					<input type="reset" class="btn btn-default" value="Reset">
				</div>
				<p><a href="login.php">Login</a>.</p>
			</form>
		</div>
	</body>
</html>
