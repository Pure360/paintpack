<?php
// Imports
require_once("PaintSession.php");

/**
* Class holding short-cuts to the different operations this 
* application will make using PAINT.  This inherits from PaintSession
* which handles the login and context id plus provides handler methods.
*/
class PaintMethods extends PaintSession
{
    /**
     * Search messages
     */
    public function searchMessages($messageName = null)
    {
        $searchInput = array();
        $resultOutput = null;

        // Search to see if an email already exists with this name (assumes no SMS on the account)
        if(!empty($messageName))
        {
	        $searchInput["messageName"] = $messageName;
	    }

		$resultOutput = $this->search("bus_facade_campaign_message", $searchInput);

		return $resultOutput;
    }

    /**
     * Search deliveries
     */
    public function searchDeliveries(	$messageName = null,
										$listName = null,
										$deliveryStartFromDate = null,
										$deliveryStatuses = null)
    {
        $searchInput = array();
        $resultOutput = null;

        // Search to see if an email already exists with this name (assumes no SMS on the account)
        if(!empty($messageName))
        {
	        $searchInput["messageName"] = $messageName;
	    }

        if(!empty($listName))
        {
	        $searchInput["listName"] = $listName;
	    }

        if(!empty($deliveryStartFromDate))
        {
	        $searchInput["deliveryStartFromDate"] = $deliveryStartFromDate;
	    }

        if(!empty($deliveryStatuses))
        {
	        $searchInput["deliveryStatuses"] = $deliveryStatuses;
	    }

		$resultOutput = $this->search("bus_facade_campaign_delivery", $searchInput);

		return $resultOutput;
    }

    /**
     * Search emails
     */
    public function searchEmails($messageName = null)
    {
        $searchInput = array();
        $resultOutput = null;

        // Search to see if an email already exists with this name (assumes no SMS on the account)
        if(!empty($messageName))
        {
	        $searchInput["messageName"] = $messageName;
	    }

		$resultOutput = $this->search("bus_facade_campaign_email", $searchInput);

		return $resultOutput;
    }

   /**
     * Create a new message on the account.  This function isolates some of the basic features
     * of a message.  More complicated features must be accessed using the sendRequest
     * function and using the data dictionary to discover the required data fields
     */
    public function createEmail($messageName, $subject, $bodyPlain, $bodyHtml)
    {
        $messageFound = false;
        $searchInput = array();
        $deleteInput = array();
        $emailInput = array();
        $resultOutput = null;

        // Put the data for the email into a hashtable keyed on the field names taken from the 
        // data dictionary
        $emailInput["messageName"]	= $messageName;
        $emailInput["subject"]		= $subject;
        $emailInput["bodyPlain"]	= $bodyPlain;
        $emailInput["bodyHtml"]		= $bodyHtml;

        // Search to see if an email already exists with this name (assumes no SMS on the account)
        if(!empty($messageName))
        {
	        $searchInput["messageName"] = $messageName;
	        $resultOutput = $this->search("bus_facade_campaign_email", $searchInput);
	    }

        if (!empty($resultOutput))
        {
            // Loop through the results in case there are other messages that contain the 
            // same string within their message name
            for ($index = 0; $index < count($resultOutput) && !$messageFound; $index++)
            {
                $loadOutput = null;
                $loadOutputFields = null;
                $loadInput = $resultOutput[$index];

                // Use the id data returned from the search to load the specific email
                $loadOutput = $this->sendRequest("bus_facade_campaign_email", "load", $loadInput, null);
                $loadOutputFields = $loadOutput["bus_entity_campaign_email"];
                
                if ($loadOutputFields["messageName"] == $messageName)
                {
                    $resultOutput = $loadOutput;
                    $messageFound = true;
                }
            }
        }
        
        if(!$messageFound)
        {
            // No existing message found so we'll create a new one
            $resultOutput = $this->sendRequest("bus_facade_campaign_email", "create", null, null);
        }

        // Whether we loaded the bean or created a new one, we'll have a bean id now. 
        // Put the bean id along with the rest of the data and request to store. After
        // this the bean will have been cleared away.
        $emailInput["beanId"] = $resultOutput["bus_entity_campaign_email"]["beanId"];

        $resultOutput = $this->sendRequest("bus_facade_campaign_email", "store", $emailInput, null);

        return $resultOutput;
    }

    /**
     * Send a request to upload a new list.  If the list already exists then we'll
     * remove it first and create a new one.  Note that the list data should be a CSV
     * string starting with a comma separated list of field names. At least one header 
     * must be either "email" or "mobile".
     */
    public function createList($listName, $listDataSource, $notifyUri)
    {
        $listFound = false;
        $searchInput = array();
        $listInput = array();
        $resultOutput = null;

        // Search to see if a lists already exists with this name
        $searchInput["listName"] = $listName;
        $resultOutput = $this->search("bus_facade_campaign_list", $searchInput);

        // If we found the correct list then remove it first
        if (!empty($resultOutput))
        {
            // Loop through the results in case there are other lists that contain the 
            // same string within their lists name
            for ($index = 0; $index < count($resultOutput) && !$listFound; $index++)
            {
                $loadOutput = null;
                $loadOutputFields = null;
                $loadInput = $resultOutput[$index];

                // Use the id data returned from the search to load the specific email
                $loadOutput = $this->sendRequest("bus_facade_campaign_list", "load", $loadInput, null);
                $loadOutputFields = $loadOutput["bus_entity_campaign_list"];
                if ($loadOutputFields["listName"] == $listName)
                {
                    $removeInput = array();

                    // Remove the existing list
                    $removeInput["beanId"] = $loadOutputFields["beanId"];
                    $this->sendRequest("bus_facade_campaign_list", "remove", $removeInput, null);
                    $listFound = true;
                }
            }
        }

        // Put the data for the list into the hashtable.  Note that the header row needs to
        // be split out and is used to create the custom field names.  
        $listInput["listName"] = $listName;

        if ($notifyUri != null)
        {
            $listInput["uploadFileNotifyEmail"] = $notifyUri;
        }

        if ($listDataSource != null)
        {
            $endFirstRowPos = 0;
            $customFieldCount = 0;
            $firstRow = null;
            $fieldNames = null;
            
            // Extract the first row from the list data
            $endFirstRowPos = strpos($listDataSource, "\n");
            
            if ($endFirstRowPos !== false)
            {
                $firstRow = substr($listDataSource, 0, $endFirstRowPos);
                $listDataSource = substr($listDataSource, $endFirstRowPos+1);
            }

            // Split this into the different column names
            $fieldNames = explode(",", $firstRow);

            // Loop through each column name and add them to the custom field
            // names list until all have been added or we have reached the maximum 
            // allowed
            for ($index = 0; ($index < count($fieldNames) & $customFieldCount <= 10); $index++)
            {
                $fieldName = $fieldNames[$index];

                switch ($fieldName)
                {
                    case "email":
                        $listInput["emailCol"] = $index;
                        break;

                    case "mobile":
                        $listInput["mobileCol"] = $index;
                        break;

                    default:
                        $fieldColStr = "field".$index."Col";
                        $fieldNameStr = "field".$index."Name";

                        // Replace illegal spaces
                        $fieldName = str_replace(' ', '_', $fieldName);

                        // Add data to the list so PAINT knows about the fields
                        $listInput[$fieldColStr] = $index;
                        $listInput[$fieldNameStr] = $fieldName;

                        // Keep count so we don't go over ten (PAINT would ignore them)
                        $customFieldCount++;
                        break;
                }
            }
        }

        // Use the "paste" field to pass in the string of data.  File uploads are not currently
        // supported via PAINT.
        $listInput["pasteFile"] = $listDataSource;

        // Now create the new list bean for us to reference and load with data
        $resultOutput = $this->sendRequest("bus_facade_campaign_list", "create", null, null);

        // Set the data onto the list and save to the system.  Note that the bean will
        // bean cleared away from the session after this
        $listInput["beanId"] = $resultOutput["bus_entity_campaign_list"]["beanId"];
        $resultOutput = $this->sendRequest("bus_facade_campaign_list", "store", $listInput, null);

        return $resultOutput;
    }

    /**
     * Schedule a delivery to the named list and message to run immediately
     */
    public function createDelivery($listName, $messageName)
    {
        $deliveryDtTmStr = null;
        $deliveryDtTm = null;
        $deliveryInput = array();
        $listSearchInput = array();
        $msgSearchInput = array();
        $resultOutput = null;
        $listData = null;
        $messageData = null;

        // Request to create a new delivery record.  This wil return with a list of 
        // messages and lists so we can use those lists to get the ids of the 
        // list and message we want to send to
        $resultOutput = $this->sendRequest("bus_facade_campaign_delivery", "create", null, null);
        $resultOutput = $resultOutput["bus_entity_campaign_delivery"];

        // Find the list id based on the name
        $listSearchInput["listName"] = $listName;
        $listData = $this->searchExactMatch("bus_facade_campaign_list", $listSearchInput);
        $deliveryInput["listIds"] = array($listData["listId"]);

        // Loop through the messages to find the ID that matches the name we've received
        $msgSearchInput["messageName"] = $messageName;
        $messageData = $this->searchExactMatch("bus_facade_campaign_email", $msgSearchInput);
        $deliveryInput["messageId"] = $messageData["messageId"];

        // Finally, add the a time five minutes into the future as the scheduled time
		$deliveryDtTm = strtotime("5 minutes", time());        
        $deliveryDtTmStr = date("d/m/Y H:i", $deliveryDtTm);
        $deliveryInput["deliveryDtTm"] = $deliveryDtTmStr;

        // Set the data onto the list and save to the system.  Note that the bean will
        // bean cleared away from the session after this
        $deliveryInput["beanId"] = $resultOutput["beanId"];
        $resultOutput = $this->sendRequest("bus_facade_campaign_delivery", "store", $deliveryInput, null);

        return $resultOutput;
    }

	/**
	* Load an existing delivery using a reference number.  High level report data will be returned
	*/
	public function loadDelivery($deliveryId)
	{
		$entityInput	= null;
		$resultOutput	= null;
		
        $entityInput = array("deliveryId" => $deliveryId);

        // Use the unique id to retrieve the delivery and return the bean data
        $resultOutput = $this->sendRequest("bus_facade_campaign_delivery", "load", $entityInput, null);
        $resultOutput = $resultOutput["bus_entity_campaign_delivery"];		
        
        return $resultOutput;
	}
	
    /**
     * Create a new one-to-one delivery to a specified email address and passing
     * any custom data that should merge into the message.  Note that the message must 
     * already exist in the account.
     */
    public function createOne2One($emailTo, $messageName, $customData)
    {
        $entityInput	= array();
        $processInput	= array();
        $resultOutput	= null;

        // Put the data for the email into a hashtable keyed on the field names taken from the 
        // data dictionary
        $processInput["message_messageName"]	= $messageName;
        $entityInput["toAddress"]				= $emailTo;


        // Load the string of custom data as separate arguments into the 
        // customData parameter
        if($customData != null)
        {
            $customDataAll = array();
            $customDataRows = null;

            // Split into rows and load into the input hashtable
            $customDataRows = explode("\r\n", $customData);

            for($index=0; $index<count($customDataRows); $index++)
            {
				$fieldName = null;
                $customDataField = null;

                // Split name value and load
                $customDataField = explode("=", $customDataRows[$index]);
                $fieldName = $customDataField[0];

                // Add the value to pass in as custom data for the message
                if (count($customDataField) == 2 && $fieldName!="")
                {
                    $fieldValue = $customDataField[1];
                    $customDataAll[$fieldName] = $fieldValue;
                }
            }

            if (!empty($customDataAll))
            {
                $entityInput["customData"] = $customDataAll;
            }
        }

        // Create a blank one2one
        $resultOutput	= $this->sendRequest("bus_facade_campaign_one2one", "create", $entityInput, $processInput);
        $resultOutput	= $resultOutput["bus_entity_campaign_one2one"];
        $entityInput	= array("beanId" => $resultOutput["beanId"]);

        // Update with data and save
        $resultOutput = $this->sendRequest("bus_facade_campaign_one2one", "store", $entityInput, null);

        return $resultOutput;
    }
    
	/**
	* Retrieve a batch of event notifications from a PureResponse profile.  Note that the profile must be set-up 
	* to capture these events, and for click and open event notifications, the campaign email must be set-up
	* to capture these events too.
	*
	* Data is returned in a base 64 encoded cvs string so it requires decoding before it can be used.
	*
	*/
	public function retrieveEventNotifications($notificationTypes, $maxNotifications, $markAsReadInd, $customFieldNames)
	{
		$processInput		= null;
		$resultOutput		= null;
		$customFieldNames	= (!empty($customFieldNames)? $customFieldNames: array());
		
        $processInput = array(	"notificationTypes" => $notificationTypes,
								"maxNotifications"	=> $maxNotifications,
								"markAsReadInd"		=> $markAsReadInd,
								"customFieldNames"	=> $customFieldNames,
								"clientType"		=> "CLIENT_PULL");

        $resultOutput = $this->sendRequest("bus_facade_eventNotification", "getBatch", null, $processInput);
        $resultOutput = $resultOutput["bus_entity_eventNotificationBatch"];

        return $resultOutput;
	}    
	
	
	/**
	* Switch between different profiles.  You'll need to use the key values from the roleList in the context
	* bean returned from logging in.  The key values are static and don't change over time.
	*/
	public function switchProfile($profileKey)
	{
		$entityInput	= null;
		$resultOutput	= null;
		
        $entityInput = array("currentRoleKey" => $profileKey);

        // Send the profile key to switch the current context
        $resultOutput = $this->sendRequest("bus_facade_context", "switchRole", $entityInput, null);
        
        return $resultOutput;
	}
}
