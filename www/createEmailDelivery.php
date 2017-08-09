<?php
	// Imports
	require_once("pure360/PaintSystemException.php");
	require_once("pure360/PaintSecurityException.php");
	require_once("pure360/PaintValidationException.php");
	require_once("pure360/PaintMethods.php");

	// Receive data posted from the form
	$processInd		= (!empty($_REQUEST["processInd"])? $_REQUEST["processInd"]: "N");
	$listName		= (!empty($_REQUEST["listName"])? $_REQUEST["listName"]: null);
	$messageName	= (!empty($_REQUEST["messageName"])? $_REQUEST["messageName"]: null);
	$subject		= (!empty($_REQUEST["subject"])? $_REQUEST["subject"]: null);		
	$plainBody		= (!empty($_REQUEST["plainBody"])? $_REQUEST["plainBody"]: null);		
	$htmlBody		= (!empty($_REQUEST["htmlBody"])? $_REQUEST["htmlBody"]: null);		
	$output			= "";
	
	// Send the request to process
	if($processInd=="Y")
	{
		
        try
        {
        	$deliveryOutput = null;
        	$deliveryId		= null;
        	
            // ***** Log in and create a context *****
            $paint = new PaintMethods();
            $paint->login();

            // ***** Create the message *****
            $paint->createEmail($messageName, $subject, $plainBody, $htmlBody);
            
            // ***** Create the delivery *****
			$deliveryOutput = $paint->createDelivery($listName, $messageName);
			$deliveryOutput = $deliveryOutput["bus_entity_campaign_delivery_key"];
			$deliveryId		= $deliveryOutput["deliveryId"];
			
            // Output to help the user see what's going on.
            $output = "Delivery has been scheduled (reference number is ".$deliveryId.")<BR/><BR/>";
            
        }
        catch (PaintValidationException $pve)
        {
            $output = "Validation Error<BR/><BR/>".
                                    $paint->convertResultToDebugString($pve->getErrors())."<BR/><BR/>";
        }
        catch (PaintSecurityException $psece)
        {
            $output = "Security Exception<BR/><BR/>".$psece->getMessage()."<BR/><BR/>";
        }
        catch (PaintSystemException $pse)
        {
            $output = "System Exception<BR/><BR/>".$pse->getMessage()."<BR/><BR/>";
        }
        catch (Exception $exp)
        {
            $output = "Unhandled Exception<BR/><BR/>".$exp->getMessage()."<BR/><BR/>";
        }

        // Log out of the session.  This should be placed so that
        // it will always occur even if there is an exception
        try
        {
            $paint->logout();
        }
        catch (Exception $exp)
        {
        	// Ignore
        }				
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>))) Pure: PAINT Example Implementation</title>    
    <link rel="stylesheet" type="text/css" href="paint.css" />    
</head>
<body>
    <form action="" method="post">
    <input type="hidden" name="processInd" value="Y" />
    <div>
        <a href="index.htm"><b>home</b></a><br />
        <br />
        Use this page to select a list for your delivery.&nbsp; Then enter the details of
        the message you want to send.&nbsp; If the message name already exists then your
        message content will overwrite the existing message.&nbsp; If it doesn't already
        exist then a new message will be created.&nbsp; The delivery will be scheduled to
        send in the next five minutes.<br />
        <br />
        <font color="red"><?php echo $output; ?></font>List Name (must match a list on the system):<br />
        <input name="listName" value="<?php echo $listName;?>"/><br />
        <br />
        Message Name:<br />
        <input name="messageName" value="<?php echo $messageName;?>" /><br />
        Subject:<br />
        <input name="subject" value="<?php echo $subject;?>" /><br />
        <br />
        Plain Message:<br />
        <textarea name="plainBody" rows="10" cols="50"><?php echo $plainBody;?></textarea><br />
        HTML Message:<br />
        <textarea name="htmlBody" rows="10" cols="50"><?php echo $htmlBody;?></textarea><br />
        <br />
        <input type="submit" value="Schedule" /></div>
    </form>
</body>
</html>
