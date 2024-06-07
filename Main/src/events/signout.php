<?php
session_start();

// Unset all of the session variables
$_SESSION = [];

// Destroy the session.
session_destroy();

// Redirect to the sign-in page or any other desired page
header("Location: ../../login.php");
exit;
?>
