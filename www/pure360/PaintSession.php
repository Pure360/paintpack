<?php
// Imports
require_once("PaintSystemException.php");
require_once("PaintSecurityException.php");
require_once("PaintValidationException.php");

date_default_timezone_set('Europe/London');

/**
*
* Utility class for creating and re-using a session within PAINT.  Utility
* methods are included to implement the standard actions e.g. creating and
* email or uploading a list.
*/
class PaintSession
{
	/** WSDL URL **/
//	protected $wsdlUrl = "http://paint.pure360.com/paint.pure360.com/ctrlPaint.wsdl";
	protected $wsdlUrl = "http://emailapi.co.uk/emailapi.co.uk/ctrlPaint.wsdl";

    /** Id to link requests together between logging in and out **/
    protected $contextId;

    protected $loginName;
    protected $password;

    /** Hashtable containing the data for this context **/
    protected $contextData;

    // Construct the class with the relevant credentials
    public function __construct()
	{
        // ** ENTER YOUR CREDENTIALS HERE **

		$this->loginName = "";
		$this->password = "";
    }

    /**
     * Log into the Pure system and obtain a context id.
     * This is automatically called from the class constructor 
     * and so is probably only required if manually logging
     * out and then back in again. 
     */    
    public function login()
    {
        $entityInput = null;
        $resultOutput = null;

        // Sanity check that the user name and password have been set correctly
        if ($this->loginName == "yourUsername" && $this->password == "yourPassword")
        {
            throw new PaintSystemException("You have not set the user name and password ".
                                            "for your account.  Please see the PaintSession ".
                                            "class in the com.pure360.paint namespace and ".
                                            "update the values set in the constructor.");
        }

        // Create argument data into a hashtable
        $entityInput = array();
        $entityInput["userName"] = $this->loginName;
        $entityInput["password"] = $this->password;

        // Login 
        $resultOutput = $this->sendRequest("bus_facade_context", "login", $entityInput, null);

        // Store the context id on the class
        $this->contextData = $resultOutput;
        $this->contextId = $resultOutput["bus_entity_context"]["beanId"];        
    }

    /**
     * Log out of the current context.  This will remove the context id and you won't be
     * able to issue any other requests after this other than login.
     */
    public function logout()
    {
        // No data needs to be sent to this request
        $this->sendRequest("bus_facade_context", "logout", null, null);

        $this->contextId = null;
    }

    /**
     * Search for an entity by a set of search parameters and return the key fields for
     * the entity or entities found.
     */
    public function search($facadeBean, $searchParameters)
    {
        $resultOutput = null;
        $searchBean = str_replace("bus_facade", "bus_search", $facadeBean);

        // First search to see if an email already exists with this name (assumes no SMS on the account)
        $resultOutput = $this->sendRequest($facadeBean, "search", $searchParameters, null);

        // Access the data using the search bean name NOT the facade bean name
        $resultOutput = $resultOutput[$searchBean];
        $resultOutput = $resultOutput["idData"];

        return $resultOutput;
    }
    
    /**
    * Search for an entity using search parameters but ensure that only an exact match
    * for all parameters is returned.
    */
    public function searchExactMatch($facadeBean, $searchParameters)
    {
    	$searchResults = null;
    	$exactMatchData = null;
        $entityBean = str_replace("bus_facade", "bus_entity", $facadeBean);
        
    	// Perform the general search to obtain a list of ids
    	$searchResults = $this->search($facadeBean, $searchParameters);
    	
    	// Loop through the ids and call the load method until we find an exact match
    	foreach($searchResults as $loadInput)
    	{
    		$beanInst = $this->sendRequest($facadeBean, "load", $loadInput, null);
    		
    		if(!empty($beanInst))
    		{
    			$exactMatch = true;
    			
    			foreach($searchParameters as $paramName=>$paramValue)
    			{
    				$beanData = $beanInst[$entityBean];
    				
    				if(!isset($beanData[$paramName]) || $beanData[$paramName] !== $paramValue)
    				{
    					$exactMatch = false;
   					}
   				}
   				
   				if($exactMatch)
   				{
	   				$exactMatchData = $beanData;
   				}
    		}
   		}
   		
   		if(empty($exactMatchData))
   		{
   			throw new PaintValidationException(array("searchExactMatch"=>"No exact match found for $facadeBean"));
   		}
   		
    	return $exactMatchData;
   	}

    /**
     * Send a request to PAINT passing the required parameters
     * and returning a hashtable of hashtables as the result
     * if successful, or throw an exception if unsuccessful.
     */
    public function sendRequest($className, $processName, $entityInput, $processInput)
    {
    	$client = null;
        $resultOutput = null;

        // Check that the context is valid
        if ($processName != "login" && $this->contextId == null)
        {
            throw new PaintSystemException("No context available for this request");
        }        
		
		$client = new SoapClient($this->wsdlUrl, array("trace" => "0"));
		$resultOutput = $client->handleRequest(	$this->contextId,
												$className, 
												$processName, 
												$entityInput, 
												$processInput);   
        switch ($resultOutput["result"])
        {
            case "success":
                if (!empty($resultOutput["resultData"]))
                {
                    $resultOutput = $resultOutput["resultData"];
                }
                else
                {
                    // Update requests return no data back
                    $resultOutput = array();
                }
                break;

            case "bean_exception_validation":
                throw new PaintValidationException($resultOutput["resultData"]);

            case "bean_exception_security":
                throw new PaintSecurityException($resultOutput["resultData"]);

            case "bean_exception_system":
                throw new PaintSystemException($resultOutput["resultData"]);

            default:
                throw new Exception("Unhandled exception thrown from PAINT");
        }

        return $resultOutput;
    }

    /**
     * Return the data from the context.  This data is loaded whe
     * logging in and contains details about the login, profile and group
     */
    public function getContextData()
    {
        return $this->contextData;
    }

    /**
     * Convert a result hashtable into a string for debug purposes
     */
    public function convertResultToDebugString($result)
    {
        $resultStr = "";

        foreach($result as $tmpKey=>$tmpValue)
        {
            if ($tmpValue != null && is_array($tmpValue))
            {
                $resultStr = $resultStr."<BR/>---><BR/>[Nested Hashtable Key] [".$tmpKey."]".$this->convertResultToDebugString($tmpValue);
            }
            else
            {
                $resultStr = $resultStr."<BR/>Key [".$tmpKey."] Value [".$tmpValue."]";
            }
        }

        $resultStr = $resultStr."<BR/><---<BR/>";

        return $resultStr;
    }
}
