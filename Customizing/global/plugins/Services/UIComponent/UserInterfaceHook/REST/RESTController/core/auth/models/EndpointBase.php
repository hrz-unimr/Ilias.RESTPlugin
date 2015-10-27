<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 *
 */
class EndpointBase extends Libs\RESTModel {
    /*
     *
     */
    protected static $accessSettings;
    protected static $refreshSettings;


    /**
     *
     */
    public static function tokenSettings($type) {
        if ($type == 'access' || $type == 'bearer') {
          if (!self::$accessSettings)
              self::$accessSettings = self::loadTokenSettings();

          return self::$accessSettings;
        }
        elseif ($type == 'refresh') {
          if (!self::$refreshSettings) {
              if (!self::$accessSettings)
                  self::$accessSettings = self::loadTokenSettings();

              self::$refreshSettings = new Token\Settings(self::$accessSettings->getSalt(), 5256000);
          }

          return self::$refreshSettings;
        }
    }


    /**
     * Load all settings from database, could also load each value when
     * its required, but doing only one query should be better overall.
     * Sets $tokenSalt and $tokenTTL.
     */
    protected static function loadTokenSettings() {
        // Fetch key, value pairs from database
        $sql = 'SELECT setting_name, setting_value FROM ui_uihk_rest_config WHERE setting_name IN ("token_salt", "token_ttl")';
        $query = self::getDB()->query($sql);
        while ($query != null && $row = self::getDB()->fetchAssoc($query)) {
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
