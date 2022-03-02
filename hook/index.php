<?php 	require_once("config.php");	
		 
		$hookResponse = file_get_contents('php://input');
		$json = json_decode($hookResponse);

		if($json):
			 
			$eid = $json->id;
			$pid = $json->data->paymentId;
			$mid = $json->merchantId;
			$event = $json->event;
			$response=preg_replace('/\s/','',(json_encode($json->data)));
			$timestamp = $json->timestamp;
			$now = date('Y-m-d H:i:s');
			// prepping a controlled sorting order, since sorting a webhook by timestamp in general tend to be unreliable.
			if ($event == 'payment.created') {
                $order = 0;
            }
            if ($event == 'payment.checkout.completed') {
                $order = 1;
            }
            if ($event == 'payment.reservation.created') {
                $order = 2;
            }
            if ($event == 'payment.reservation.created.v2') {
                $order = 3;
            }
            if ($event == 'payment.cancel.created') {
                $order = 4;
            }
            if ($event == 'payment.charge.created') {
                $order = 5;
            }
            if ($event == 'payment.charge.created.v2') {
                $order = 6;
            }
            if ($event == 'payment.refund.completed') {
                $order = 7;
            }
            if ($event == 'payment.charge.failed') {
                $order = 8;
            }
 
			$checkHooks = $dbobj->query("select * from tbl_webhooks WHERE fldPID='".$pid."' LIMIT 1");
			if($checkHooks->num_rows > 0){			 
				
				$ID = $checkHooks->row['fldID'];
				$addEvent = "insert into tbl_webhook_events (fldHookID,fldEID,fldEvent,fldData,fldSort,fldStamp) 
				values ('$ID','$eid','$event','$response','$order','$timestamp')";
				$dbobj->query($addEvent);

			}else{ 
				$newHook = "insert into tbl_webhooks (fldMID,fldPID,fldDate) values ('$mid','$pid','$now')";
				$dbobj->query($newHook);

				$result = $dbobj->query("select fldID from tbl_webhooks order by fldID desc LIMIT 1");				
				$newID = $result->row['fldID'];

				$newEvent = "insert into tbl_webhook_events (fldHookID,fldEID,fldEvent,fldData,fldSort,fldStamp) 
				values ('$newID','$eid','$event','$response','$order','$timestamp')";
				$dbobj->query($newEvent);
			}

		else:
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
	<title>Nets Easy - integration</title>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

	<!-- Demo CSS -->
	<link rel="stylesheet" href="demo.css">

	<!-- Latest compiled and minified JavaScript -->
	<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
</head>
<body>
<div class="container-fluid">
	<div class="row">
		<div class="col-12 text-center">
			<h1>WEBHOOKS</h1>
		</div>
	</div>


    <?php $msg = $_GET['msg'] ?? ''; switch($msg):	case "hook_updated":?>
		<div class="alert alert-info alert-dismissable">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
			<h4>Hook was updated!</h4>
		</div>
	<?php break; case "hook_deleted":?>
		<div class="alert alert-danger alert-dismissable">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
			<h4>Hook was deleted!</h4>
		</div>
	<?php break; endswitch;?>

	<div class="row head">
		<div class="col-1">
			MERCHANT ID
		</div>
		<div class="col-1">
			HOOK ID
		</div>
		<div class="col-3">
			PAYMENT ID
		</div>
		<div class="col-3">
			EVENT
		</div>
		<div class="col-1">
			DATE
		</div>
		<div class="col-2 p-0 text-right">
			STATUS / DELETE
		</div>
	</div>

	<?php 
		$getHooks = $dbobj->query("select * from tbl_webhooks order by fldDate"); 
		if($getHooks->num_rows > 0): 
		foreach ($getHooks->rows as $hook) :		
		$ID = $hook['fldID'];

		$getEvents = $dbobj->query("select * from tbl_webhook_events where fldHookID='".$ID."' order by fldSort DESC LIMIT 1"); 
		$getHistory = $dbobj->query("select * from tbl_webhook_events where fldHookID='".$ID."' order by fldSort DESC"); 
		
		foreach ($getEvents->rows as $event):
	?>

	<?php 
		if($event['fldEvent']=='payment.checkout.completed'){ $status="created"; };
		if($event['fldEvent']=='payment.reservation.created'){ $status="new"; };
		if($event['fldEvent']=='payment.reservation.created.v2'){ $status="new-2"; };
		if($event['fldEvent']=='payment.cancel.created'){ $status="cancelled"; };
		if($event['fldEvent']=='payment.charge.created'){ $status="completed"; };
		if($event['fldEvent']=='payment.charge.created.v2'){ $status="completed-2"; };
		if($event['fldEvent']=='payment.refund.completed'){ $status="refund"; };
		if($event['fldEvent']=='payment.charge.failed'){ $status="failed"; };
	?>

	<div class="row hook">
		<div class="col-1">
			<?php echo $hook['fldMID'];?>
		</div>

		<div class="col-1">
			<a class="btn btn-data" data-toggle="modal" data-target="#Modal-<?php echo $ID;?>">
				INFO
			</a>
		</div>

		<div class="col-3">
			<?php echo $hook['fldPID'];?>
		</div>
		<div class="col-3">
			<?php echo $event['fldEvent'];?>
		</div>
		<div class="col-1">
			<?php echo date("d.m.Y H:i:s", strtotime($event['fldStamp']));?>
		</div>
		<div class="col-2 p-0 text-right">
			<span class="badge badge-<?php echo $status;?>"><?php echo $status;?></span>
			<a href="hook_delete.php?ID=<?php echo $ID;?>" class="btn btn-close">&#215;</a>
		</div>
	</div>

	<div class="modal" id="Modal-<?php echo $ID;?>" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="ModalLabel">ID #<?php echo $ID;?>  - Hook ID : <sub style="bottom: .05em;"><?php echo $event['fldEID'];?></sub></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>Hook ID : <?php echo $event['fldEID'];?></p>
				</div>
				<div class="modal-footer">
					<?php print("<pre>".print_r($event,true)."</pre>");?>
				</div>
			</div>
		</div>
	</div>

	<?php endforeach; endforeach; else:?>

		<div class="row hook empty">
			<div class="col-12 text-center">. . . NO HOOKS FOUND . . .</div>
		</div>

	<?php endif;?>
</div>
</body>
</html>
<?php endif;?>