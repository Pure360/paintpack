<?php
	// Imports
	require_once("pure360/PaintSystemException.php");
	require_once("pure360/PaintSecurityException.php");
	require_once("pure360/PaintValidationException.php");
	require_once("pure360/PaintMethods.php");

	// Receive data posted from the form
	$deliveryId 	= (!empty($_REQUEST["deliveryId"])? $_REQUEST["deliveryId"]: null);
	$output			= "";
	$deliveryData	= "";
	
	// Send the request to process
	if(!empty($deliveryId))
	{		
	    $paint = new PaintMethods();
	     
        try
        {
        	$deliveryOutput = null;
        	$displayFields	= array("deliveryDtTm",
									"deliveryStatus",
									"languageCode",
									"messageName",
									"listNames",
									"total",
									"sent",
									"enroute",
									"received",
									"replied",
									"bounced",
									"autoReplied",
									"softBounced",
									"blocked",
									"sentToFriend",
									"outOfCredit",
									"opened",
									"repeatOpened",
									"clicked",
									"repeatClicked",
									"optedOut",
									"optedOutPassive",
									"limitedReceipt",
									"stopped",
									"alreadyBounced",
									"alreadyOptedOut");
        	
            // ***** Log in and create a context *****
            $paint->login();

            // ***** Load the delivery record *****
            $deliveryOutput = $paint->loadDelivery($deliveryId);

            // Output to help the user see what's going on.
            $output = "Delivery found.  See below for details:<BR/><BR/>";
            
            // Remove some of the less interesting data from the array and then output the rest
            foreach($deliveryOutput as $fieldName=>$fieldValue)
            {
            	if(in_array($fieldName, $displayFields))
            	{
		            $deliveryData .= $fieldName." = ".$fieldValue."\n";
		        }
	        }
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
    <div>
        <a href="index.htm"><b>home</b></a><br />
        <br />
        Load an existing delivery.&nbsp; You will need the reference number (delivery id) that was 
        returned when the delivery was created.&nbsp; The delivery bean holds high level reporting
		data that can be displayed to the end user<br />
        <br />
        <font color="red"><?php echo $output; ?></font>Delivery reference (id):
        <input name="deliveryId" value="<?php echo $deliveryId; ?>"/>
        <input type="submit" value="Load Delivery" />
		<pre><?php echo $deliveryData; ?></pre>
    </div>
    </form>
</body>
</html>
