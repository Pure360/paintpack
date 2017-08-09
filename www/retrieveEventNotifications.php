<?php
	// Imports
	require_once("pure360/PaintSystemException.php");
	require_once("pure360/PaintSecurityException.php");
	require_once("pure360/PaintValidationException.php");
	require_once("pure360/PaintMethods.php");

	// Receive data posted from the form
	$processInd		= (!empty($_REQUEST["processInd"])? $_REQUEST["processInd"]: "N");
	
	$notificationTypes	= (!empty($_REQUEST["notificationTypes"])? $_REQUEST["notificationTypes"]: null);
	$maxNotifications	= (!empty($_REQUEST["maxNotifications"])? $_REQUEST["maxNotifications"]: null);
	$markAsReadInd		= (!empty($_REQUEST["markAsReadInd"])? $_REQUEST["markAsReadInd"]: null);		
	$customFieldNames	= (!empty($_REQUEST["customFieldNames"])? $_REQUEST["customFieldNames"]: null);		
	$output				= "";
	$eventData			= "";
	$eventCount			= null;
	$eventMetaOutput	= "";
	
	// Send the request to process
	if($processInd=="Y")
	{		
        try
        {
			$eventCount				= null;
			$eventMeta				= null;
			$eventData        		= null;
        	$eventMetaOutput		= "";
        	$eventOutput			= null;
        	
            // ***** Log in and create a context *****
            $paint = new PaintMethods();
            $paint->login();

            // ***** Retrieve the event data *****
            $eventOutput	= $paint->retrieveEventNotifications(	$notificationTypes, 
																	$maxNotifications, 
																	$markAsReadInd,
																	$customFieldNames);

			$eventCount		= $eventOutput["eventCount"];
			$eventData		= base64_decode($eventOutput["eventData"]);
			$eventMeta		= $eventOutput["eventMeta"];

			// Output the meta data as a readable string
			foreach($eventMeta as $eventMetaName=>$eventMetaData)
			{
				$eventMetaOutput .= "Type: ".$eventMetaName.", Columns [".$eventMetaData."]\n";
			}
						
            // Output to help the user see what's going on.
            $output = "Event data retrieved (see below)<BR/><BR/>";     
            
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
		
	} else
	{
		// This code is run when the page is accessed rather than posted
		if(empty($customData))
		{
    		$notificationTypes 	= "CLICK,OPEN,BOUNCE,BLOCKED,OPTOUT,OPTIN,DELIVERYSTART,DELIVERY";	
			$maxNotifications	= 100;	
			$markAsReadInd		= "N";
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
        Retrieve the event notifications from your deliveries.  Note that each different type of event notifcation 
		required must be enabled on the profile.  Once enabled, some notifications need to be specifically switched on
		within the email e.g. clicks and opens.  Event notification will be returned based on the selected notification
		types, and will be returned in CSV format.<br />
        <br />
        <font color="red"><?php echo $output; ?></font>
		Notification Types:<br />
        <em>(enter a comma separated list of types i.e. CLICK,OPEN,BOUNCE,OPTOUT,OPTIN,DELIVERY)</em>
        <input name="notificationTypes" value="<?php echo $notificationTypes; ?>" size="50"/><br />
        <br />
        Maximum Notifications:<br/>
   		<em>(the maximum number of event notifications to return per request)</em><br />
        <input name="maxNotifications" value="<?php echo $maxNotifications; ?>"/><br />
        <br />
        Mark as Read:<br/>
   		<em>(mark the retrieved notifications as read so that they are not returned in the next request)</em><br />
        <input name="markAsReadInd" value="<?php echo $markAsReadInd; ?>"/><br />
        <br />
        Custom fields:<br/>
   		<em>(list the names of custom fields to be returned if available)</em><br />
        <input name="customFieldNames" value="<?php echo $customFieldNames; ?>"/><br />
        <br />
		<input type="submit" value="Retrieve Data" />
        <br />
        <br />
        Result:<br />
        <em>(the returned data will be displayed below)</em><br />
        <br/>
        <b>Event records returned:</b>
        <br/>
        <br/>
        <?php echo $eventCount;?>
		<br/>
		<br/>
        <b>Event data description:</b>
        <br/>
        <pre><?php echo $eventMetaOutput;?></pre>
        <b>Event data (base 64 decoded):</b>
        <br/>
        <pre><?php echo $eventData;?></pre>
		<br />
        <br />
    </div>
    </form>
</body>
</html>
