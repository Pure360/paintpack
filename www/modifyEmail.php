<?php
	// Imports
	require_once("pure360/PaintSystemException.php");
	require_once("pure360/PaintSecurityException.php");
	require_once("pure360/PaintValidationException.php");
	require_once("pure360/PaintMethods.php");

	// Receive data posted from the form
	$emailId    = (!empty($_REQUEST["emailId"])? $_REQUEST["emailId"]: null);
    $linkSuffix = (!empty($_REQUEST["linkSuffix"])? $_REQUEST["linkSuffix"]: null);
    $output		= "";
	$emailData	= "";
	
	// Send the request to process
	if(!empty($emailId))
	{		
	    $paint = new PaintMethods();
	     
        try
        {
        	$emailOutput = null;

            $displayFields = array("linkSuffix");
        	
            // ***** Log in and create a context *****
            $paint->login();

            // ***** Load the email record *****
            //$emailOutput = $paint->loadEmail($emailId);
            $emailOutput = $paint->modifyEmailLinkSuffix($emailId, $linkSuffix);
            $emailOutput = $paint->loadEmail($emailId);

            // Output to help the user see what's going on.
            $output = "Email found.  See below for details:<BR/><BR/>";
            
            // Remove some of the less interesting data from the array and then output the rest
            foreach($emailOutput as $fieldName=>$fieldValue)
            {
            	if(in_array($fieldName, $displayFields))
            	{
		            $emailData .= $fieldName." = ".$fieldValue."\n";
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
        Example of modifying an existing email, in this example case the link suffix which is appended to all links in the email.&nbsp; You will need the email ID that was returned when the email was created.<br />
        <br />
        <font color="red"><?php echo $output; ?></font>email reference (id):
        <input name="emailId" value="<?php echo $emailId; ?>"/>
        <br />New link suffix:<input name="linkSuffix" />
        <br /><input type="submit" value="Modify Email" />
		<pre><?php echo $emailData; ?></pre>
    </div>
    </form>
</body>
</html>
