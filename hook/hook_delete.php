<?php 
require_once("config.php");

//$ID = strip_tags(stripslashes(mysqli_real_escape_string($conn, $_GET['ID'])));
if($_GET['ID']) {

	$DeleteHook = "DELETE FROM `tbl_webhooks` WHERE `fldID` = '".$_GET['ID']."' LIMIT 1";
	$dbobj->query($DeleteHook);

	$DeleteEvent = "DELETE FROM `tbl_webhook_events` WHERE `fldHookID` = '".$_GET['ID']."' LIMIT 1";
	$dbobj->query($DeleteEvent);

	header("Location:../hook?msg=hook_deleted");
}
?>