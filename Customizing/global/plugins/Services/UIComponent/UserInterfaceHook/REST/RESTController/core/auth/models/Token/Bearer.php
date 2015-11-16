<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Token;


/*
 *
 */
class Bearer extends Base {
    //
    protected static $fields = array(
        'access_token',
        'expires_in',
        'token_type',
        'scope',
        'ilias_client'
    );


    /**
     *
     */
    public static function fromMixed($tokenSettings, $tokenArray) {
        $bearerToken = new self($tokenSettings);
        $bearerToken->setToken($tokenArray);

        if ($bearerToken->getTokenArray())
            return $bearerToken;
    }
    public static function fromFields($tokenSettings, $user, $api_key, $scope = null, $ilias_client) {
        $bearerToken = new self($tokenSettings);
        $tokenArray = $bearerToken->generateTokenArray($user, $api_key, $scope, $ilias_client);
        $bearerToken->setToken($tokenArray);

        if ($bearerToken->getTokenArray())
            return $bearerToken;
    }


    /**
     *
     */
    protected function generateTokenArray($user, $api_key, $scope = null, $ilias_client) {
        // Generate generic token containing user and api-key
        $token_type = 'bearer';
        $accessToken = Generic::fromFields($this->tokenSettings, $user, $api_key, $token_type, '', $this->tokenSettings->getTTL(), $ilias_client);

        // Generate bearer-token containing the generic token and additional information
        return array(
            'access_token'  => $accessToken,
            'expires_in'    => $accessToken->getRemainingTime(),
            'token_type'    => $token_type,
            'scope'         => $scope,
            'ilias_client'  => $ilias_client,
        );
    }
}
