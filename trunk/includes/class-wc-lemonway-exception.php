<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_LemonWay_Exception extends Exception
{
    /** @var string Localized error message */
    private $localized_message;

    /**
     * Constructor
     *
     * @param string $error_message Error message
     * @param string $error_code Error code
     * @param string $localized_message Localized error message
     */
    public function __construct($error_message = '', $localized_message = '', $error_code = 0)
    {
        if (!empty($localized_message)) {
            $this->localized_message = $localized_message;
        } else {
            $this->localized_message = $error_message;
        }
        
        parent::__construct($error_message, $error_code);
    }

    /**
     * Return the localized message.
     *
     * @return string
     */
    public function getLocalizedMessage()
    {
        return $this->localized_message;
    }
}
