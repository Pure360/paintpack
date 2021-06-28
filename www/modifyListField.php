<?php
	// Imports
	require_once("pure360/PaintSystemException.php");
	require_once("pure360/PaintSecurityException.php");
	require_once("pure360/PaintValidationException.php");
	require_once("pure360/PaintMethods.php");

	// Receive data posted from the form
	$listId    = (!empty($_REQUEST["listId"])? $_REQUEST["listId"]: null);
    $oldFieldColNo     = (!empty($_REQUEST["oldFieldColNo"])? $_REQUEST["oldFieldColNo"]: null);
    $newFieldName     = (!empty($_REQUEST["newFieldName"])? $_REQUEST["newFieldName"]: null);
    $output			= "";
	$listData	= "";
	
	// Send the request to process
	if(!empty($listId))
	{		
	    $paint = new PaintMethods();
	     
        try
        {
        	$listOutput = null;
        	$displayFields	= array("listName",
                "field1Name");
        	
            // ***** Log in and create a context *****
            $paint->login();

            // ***** Load the list record *****
            $listOutput = $paint->modifyListFieldName($listId, $oldFieldColNo, $newFieldName);
            $listOutput = $paint->loadlist($listId);

            // Output to help the user see what's going on.
            $output = "list found.  See below for details:<BR/><BR/>";
            
            // Remove some of the less interesting data from the array and then output the rest
            foreach($listOutput as $fieldName=>$fieldValue)
            {
            	if(in_array($fieldName, $displayFields))
            	{
		            $listData .= $fieldName." = ".$fieldValue."\n";
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
        Load an existing list.&nbsp; You will need the reference number (list id) that was 
        returned when the list was created.<br />
        <br />
        <font color="red"><?php echo $output; ?></font>list reference (id):
        <input name="listId" value="<?php echo $listId; ?>"/>
        <br />
        Existing field column number:<input name="oldFieldColNo" />
        <br />
        New field name:<input name="newFieldName" />
        <br />
        <input type="submit" value="Modify list" />
		<pre><?php echo $listData; ?></pre>
    </div>
    </form>
</body>
</html>
