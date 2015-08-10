<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 *
 */
class RESTRequest extends \Slim\Http\Request {
    /**
     *
     */
    public function __construct(\Slim\Environment $env) {
        //
        parent::__construct($env);

        //
        foreach (getallheaders() as $key => $value)
            $this->headers->set($key, $value);
    }


    /**
     *
     */
    public function params($key = null, $default = null, $throw = false) {
        // Fetch body, this should be a decoded json
        $body = $this->getBody();

        // Return key-value from body (JSON PHP-Array)
        if (isset($body) && isset($body[$key]))
            return $body[$key];
        else {
            // Return key-value from RAW body or get
            $value = parent::params($key, $default);
            if ($value != $default)
                return $value;
            if (isset($_POST[$key]))
                return $_POST[$key];

            // Return default value or throw exception?
            if (!$throw)
                return $default;

            // Throw exception because its enabled
            throw new Exceptions\MissingParameter('Mandatory data is missing, parameter \'%paramName%\' not set.', $key);
        }

    }
}
