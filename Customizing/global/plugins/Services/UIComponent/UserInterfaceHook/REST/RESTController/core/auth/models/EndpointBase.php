<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\clients\Clients as Clients;


/**
 *
 * Constructor requires $app & $sqlDB.
 */
class EndpointBase extends EndpointBase {
    protected $tokenSettings;


    public static fromBase($baseObject) {
        $obj = new self($baseObject->app, $baseObject->sqlDB, $baseObject->plugin);
        $obj->tokenSeetings = $baseObject->tokenSettings;
        return $obj;
    }


    public function tokenSettings() {
        if (!$this->tokenSettings)
            $this->tokenSettings = loadTokenSettings();

        return $this->tokenSettings;
    }

    /**
     * Load all settings from database, could also load each value when
     * its required, but doing only one query should be better overall.
     * Sets $tokenSalt and $tokenTTL.
     */
    protected function loadTokenSettings() {
        // Fetch key, value pairs from database
        $query = 'SELECT setting_name, setting_value FROM ui_uihk_rest_config WHERE setting_name IN ("token_salt", "token_ttl")';
        $set = $this->sqlDB->query($query);
        while ($set != null && $row = $this->sqlDB->fetchAssoc($set)) {
            switch ($row['setting_name']) {
                case "token_salt" :
                    $salt = $row['setting_value'];
                    break;
                case "token_ttl" :
                    $ttl = $row['setting_value'];
                    break;
            }
        }

        // Set default values
        if (!$salt)
            throw new \Exception('Can\'t load token-salt from database! Check that there is a (token_salt, <VALUE>) entry in the ui_uihk_rest_config table.');
        if (!$ttl)
            $ttl = 30;

        // Create new settings object
        return new Token\Settings($salt, $ttl);
    }
}
