<?php
// Initialize the session
session_start();
 
// Unset all of the session variables
$_SESSION = array();
 
// Destroy the session.
session_destroy();
 
// Redirect to login page
header("location: login.php");

// you don't need a closing bracket if you don't have a body, and you don't need a exit either since it's the end of the code anyway