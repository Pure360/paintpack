<?php
/**
* Exception thrown when PAINT encounters validation errors during an update or store.
* Errors are stored in a hashtable keyed by a unique reference for the field
* or particular validation error.
*/
class PaintValidationException extends Exception
{
    /** Array of errors keyed on the error field/name **/
    protected $errors;

    /**
     * Construct the exception with a hashtable of errors
     */
    public function __construct($errors)
	{
		$this->errors = $errors;
		
		parent::__construct("Validation error");
	}

    /**
     * Return the hash table or errors 
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
