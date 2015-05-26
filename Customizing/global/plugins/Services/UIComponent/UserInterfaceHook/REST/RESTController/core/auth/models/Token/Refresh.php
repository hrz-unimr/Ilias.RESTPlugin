<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth\Token;


/*
 *
 */
class Refresh extends Generic {
    /**
     *
     */
    public static function fromFields($tokenSettings, $user, $api_key, $type = null, $misc = null, $lifetime = null) {
        $refreshToken = new self($tokenSettings);
        $tokenArray = $refreshToken->generateTokenArray($user, $api_key);
        $refreshToken->setToken($tokenArray);

        if ($refreshToken->getTokenArray())
            return $refreshToken;
    }


    /**
     *
     */
    public function getRemainingRefreshs() {
        //
        $user_id = $this->getUserId();
        $api_key = $this->GetApiKey();
        $refresh_token = $this->getTokenString();

        //
        $sql = sprintf('
            SELECT num_refresh_left
            FROM ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id=%d
            AND ui_uihk_rest_keys.api_key="%s"
            AND ui_uihk_rest_oauth2.refresh_token="%s"',
            $user_id,
            $api_key,
            $refresh_token
        );
        $query = $this->sqlDB->query($sql);

        //
        if ($query != null && $entry = $this->sqlDB->fetchAssoc($query))
            return $entry['num_refresh_left'];
    }


    /**
     *
     */
    public function getTokenInfo() {
        //
        $user_id = $this->getUserId();
        $api_key = $this->GetApiKey();
        $refresh_token = $this->getTokenString();

        //
        $sql = sprintf('
            SELECT num_refresh_left, num_resets, last_refresh_timestamp
            FROM ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id=%d
            AND ui_uihk_rest_keys.api_key="%s"
            AND ui_uihk_rest_oauth2.refresh_token="%s"',
            $user_id,
            $api_key,
            $refresh_token
        );
        $query = $this->sqlDB->query($sql);

        //
        if ($query != null && $entry = $this->sqlDB->fetchAssoc($query))
            return $entry;
    }


    /**
     *
     */
    protected function generateTokenArray($user, $api_key, $type = null, $misc = null, $lifetime = null) {
        // Generate random string to make re-hashing token "difficult"
        $randomStr = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 5);

        // Generate token and return it
        $tokenArray = parent::generateTokenArray($user, $api_key, "refresh", $randomStr, $this->tokenSettings->getTTL());
        return $tokenArray;
    }
}
