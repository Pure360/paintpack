<?php
	// Imports
	require_once("pure360/PaintSystemException.php");
	require_once("pure360/PaintSecurityException.php");
	require_once("pure360/PaintValidationException.php");
	require_once("pure360/PaintMethods.php");

	// Receive data posted from the form
	$processInd	     = (!empty($_REQUEST["processInd"])? $_REQUEST["processInd"]: "N");
    $listName        = (!empty($_REQUEST["listName"])? $_REQUEST["listName"]: null);
    $transactionType = (!empty($_REQUEST["transactionType"])? $_REQUEST["transactionType"]: "CREATE");
	$listData	     = (!empty($_REQUEST["listData"])? $_REQUEST["listData"]: null);
	$notifyUri	     = (!empty($_REQUEST["notifyUri"])? $_REQUEST["notifyUri"]: null);		
	$output		     = "";

	// Send the request to process
	if($processInd=="Y")
	{		
        try
        {
            // ***** Log in and create a context *****
            $paint = new PaintMethods();
            $paint->login();

            // ***** Upload your list data *****
            $paint->createList($listName, $listData, $notifyUri, $transactionType);

            // Output to help the user see what's going on.
            $output = "List upload request sent.  Please wait for the system to process the request.<BR/><BR/>";
            
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
		if(empty($listData))
		{
    		$listData = "email,first name,last name,customer number\n".
                        "abc1@anydomain.com,John,Smith,A12345\n".
                        "abc2@anydomain.com,James,Salmon,B12345\n".
                        "abc3@anydomain.com,Jack,Sutton,C12345\n".
                        "abc4@anydomain.com,Julie,Salford,D12345\n".
                        "abc5@anydomain.com,June,Sanders,E12345\n".
                        "abc6@anydomain.com,Johan,Smythe,F12345\n".
                        "abc7@anydomain.com,Jenny,Sorano,G12345\n".
                        "abc8@anydomain.com,Julian,Shankley,H12345\n".
                        "abc9@anydomain.com,Jose,Sandro,I12345\n".
                        "abc10@anydomain.com,Jamie,Savier,J12345\n";		
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
        This page will allow you to upload a list of data. The list name you use will be matched to any that
        exist on the system already.
        
        Please note that uploading lists via PAINT is only suitable for smaller lists (i.e. < 5000 records). 
        If you wish to upload larger lists then you should use the alternative list interface options that 
        allow you to send unmodified list data over HTTP (see the Pure Interfaces documentation).
        <br />
        <br />
        <font color="red"><?php echo $output; ?></font>List Name:<br />
        <input name="listName" value="<?php echo $listName; ?>"/><br />
        <br />
        <font color="red"><?php echo $output; ?></font>Transaction type<br />
        <input name="transactionType" value="<?php echo $transactionType; ?>"/><br />
        <br />
        List Data:<br />
        <textarea name="listData" rows="10" cols="50"><?php echo $listData; ?></textarea><br />
        (Note that the list data must have a header row describing each column, and you
        must include an "email" column).<br />
        <br />
        Notify Email Address:<br />
        <input name="notifyUri" value="<?php echo $notifyUri; ?>"/><br />
        (This is the email address that receives notification when the list has been uploaded)<br />
        <br />
        <br />
        <input type="submit" value="Create List" /></div>
    </form>
</body>
</html>
