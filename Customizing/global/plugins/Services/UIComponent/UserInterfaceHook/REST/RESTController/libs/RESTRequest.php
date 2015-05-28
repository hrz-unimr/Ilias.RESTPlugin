<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
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
        if ($body && $body[$key])
            return $body[$key];
        else {
            // Return key-value from RAW body or get
            $value = parent::params($key, $default);
            if ($value != $default)
                return $value;

            // Return default value or throw exception?
            if (!$throw)
                return $default;

            // Throw exception because its enabled
            throw new Exceptions\MissingParameter('Mandatory data is missing, parameter %paramName% not set.', $param);
        }

    }
}
