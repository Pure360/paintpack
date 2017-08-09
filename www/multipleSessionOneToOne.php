<?php
	// Imports
	require_once("pure360/PaintSystemException.php");
	require_once("pure360/PaintSecurityException.php");
	require_once("pure360/PaintValidationException.php");
	require_once("pure360/PaintMethods.php");

	// Receive data posted from the form
	$processInd		= (!empty($_REQUEST["processInd"])? $_REQUEST["processInd"]: "N");
	$emailTo		= (!empty($_REQUEST["emailTo"])? $_REQUEST["emailTo"]: null);
	$messageName	= (!empty($_REQUEST["messageName"])? $_REQUEST["messageName"]: null);
	$customData		= (!empty($_REQUEST["customData"])? $_REQUEST["customData"]: null);		
	$output			= "";
	
	// Send the request to process
	if($processInd=="Y")
	{		
        try
        {
            // ***** Log in and create a context *****
            $paint1 = new PaintMethods();
            $paint2 = new PaintMethods();
            $paint1->login();
            $paint2->login();

            // ***** Create your one2one *****
            $paint1->createOne2One($emailTo, $messageName, $customData);
            $paint2->createOne2One($emailTo, $messageName, $customData);

            // Output to help the user see what's going on.
            $output = "One-to-One request scheduled<BR/><BR/>";
            
            
        }
        catch (PaintValidationException $pve)
        {
            $output = "Validation Error<BR/><BR/>".
                                    $paint1->convertResultToDebugString($pve->getErrors())."<BR/><BR/>";
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
            $paint1->logout();
            $paint2->logout();
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
    		$customData = "firstName=John\nlastName=Smith";		
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
        Schedule a PureResponse One-to-One message.&nbsp; This is a tracked one-to-one message
        with custom data merged in.&nbsp; The message you select must already exist in the
        system and you must have the One-to-One module enabled on your account for the login
        that you are using.<br />
        <br />
        <font color="red"><?php echo $output; ?></font>Email To:
        <br />
        <input name="emailTo" value="<?php echo $emailTo; ?>"/><br />
        <br />
        Message Name:<br/>
   		<em>(this message name must exist on the system)</em><br />
        <input name="messageName" value="<?php echo $messageName; ?>"/><br />
        <br />
        Custom Data:<br />
        <em>(place each custom data item on a new line in the format "fieldName=fieldValue")</em><br />
            <textarea name="customData"><?php echo $customData; ?></textarea><br />
            <br />
            <input type="submit" value="Send One-to-One" /></div>
        <br />
        <em>
            <br />
        </em>
    </form>
</body>
</html>
