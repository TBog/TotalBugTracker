<?php
// # Define all xajax functions
// require_once 'inc/ajax.functions.inc.php';
# Process the client request
error_reporting(E_ALL | E_NOTICE);
require_once 'inc/ajax.inc.php';
$xajax->processRequest();
?>