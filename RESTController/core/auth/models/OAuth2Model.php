<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;

use \RESTController\core\clients\ClientsModel;


/**
 * Class OAuth2Model
 * This model provides methods to accomplish the OAuth2 mechanism for ILIAS.
 * Note: In contrast to the original specification, we renamed the term OAuth2 "client_id" to "api_key".
 */
class OAuth2Model {
    // ----------------------------------------------------------------------------------------------
    // Authorization endpoint routines
    
    
    /**
     * The authorization endpoint part of the authorization credendials flow.
     * @param $app
     */
    public function handleAuthorizationEndpoint_authorizationCode($app) {
        $request = $app->request();
        $response = new Oauth2Response($app);
        $api_key = $request->params('api_key');
        $redirect_uri = $request->params('redirect_uri');
        $username = $request->params('username');
        $password = $request->params('password');
        $response_type = $request->params('response_type');
        $authenticity_token = $request->params('authenticity_token');

        if ($redirect_uri && $api_key && is_null($authenticity_token) && is_null($username) && is_null($password)) {
            OAuth2Model::render($app, 'REST OAuth - Login für Tokengenerierung', 'oauth2loginform.php', array(
                'api_key' => $api_key, 
                'redirect_uri' => $redirect_uri, 
                'response_type' => $response_type
            ));
        } elseif ($username && $password) {
            $isAuth = AuthLib::authenticateViaIlias($username, $password);

            $clientValid = AuthLib::checkOAuth2Client($api_key);
            if ($isAuth == true && $clientValid == true){
                $clients_model = new ClientsModel();
                if ($clients_model->clientExists($api_key)) {
                    if ($clients_model->is_oauth2_gt_authcode_enabled($api_key)) {
                        if($clients_model->is_oauth2_consent_message_enabled($api_key) == true) {
                            // Standard behaviour of the "authorization code grant": having an additional page with a consent message
                            $temp_authenticity_token = TokenLib::generateSerializedToken($username, $api_key, "", "", 10);
                            $oauth2_consent_message = $clients_model->getOAuth2ConsentMessage($api_key);
                            
                            OAuth2Model::render($app, 'REST OAuth - Client autorisieren', 'oauth2grantpermissionform.php', array(
                                'api_key' => $api_key, 
                                'redirect_uri' => $redirect_uri, 
                                'response_type' => $response_type, 
                                'authenticity_token' => $temp_authenticity_token, 
                                'oauth2_consent_message' => $oauth2_consent_message
                            ));
                        } else {
                            $authorization_code = TokenLib::generateSerializedToken($username, $api_key, "code", $redirect_uri,10);
                            $url = $redirect_uri . "?code=".$authorization_code;
                            $app->redirect($url);
                        }
                    } else {
                        $response->setHttpStatus(401);
                        $response->send();
                    }
                } else {
                    $response->setHttpStatus(401);
                    $response->send();
                }

            } else {
                OAuth2Model::render($app, 'REST OAuth - Login für Tokengenerierung', 'oauth2loginform.php', array(
                    'error_msg' => "Username or password incorrect!",
                    'api_key' => $api_key, 
                    'redirect_uri' => $redirect_uri, 
                    'response_type' => $response_type
                ));
            }
        } elseif ($authenticity_token && $redirect_uri) {
            $authenticity_token = TokenLib::deserializeToken($authenticity_token);
            $user = $authenticity_token['user'];

            if (!TokenLib::tokenExpired($authenticity_token)) {
                $authorization_code = TokenLib::generateSerializedToken($user, $api_key, "code", $redirect_uri,10);
                $url = $redirect_uri . "?code=".$authorization_code;
                $app->redirect($url);
            }
        }
    }
    
    
    /**
     * The authorization endpoint part of the implicit grant flow.
     * @param $app
     */
    public function handleAuthorizationEndpoint_implicitGrant($app) {
        $response = new Oauth2Response($app);
        $request = $app->request();
        $api_key = $request->params('api_key');
        $redirect_uri = $request->params('redirect_uri');
        $username = $request->params('username');
        $password = $request->params('password');
        $response_type = $request->params('response_type');
        $authenticity_token = $request->params('authenticity_token');
        $clients_model = new ClientsModel();
        if ($clients_model->clientExists($api_key)) {
            if ($clients_model->is_oauth2_gt_implicit_enabled($api_key)) {
                if($clients_model->is_oauth2_consent_message_enabled($api_key)) {
                    // Standard behaviour of "implicit grant": having an additional page with a consent message
                    if ($redirect_uri && $api_key && is_null($authenticity_token) && is_null($username) && is_null($password)) {
                        OAuth2Model::render($app, 'REST OAuth - Login für Tokengenerierung', 'oauth2loginform.php', array(
                            'api_key' => $api_key, 
                            'redirect_uri' => $redirect_uri, 
                            'response_type' => $response_type
                        ));
                    } elseif ($username && $password) {
                        $isAuth = AuthLib::authenticateViaIlias($username, $password);
                        $clientValid = AuthLib::checkOAuth2Client($api_key);
                        if ($isAuth == true) {
                            $app->log->debug("Implicit Grant Flow - Auth valid");
                        } else {
                            $app->log->debug("Implicit Grant Flow - Auth NOT valid");
                            $response->setHttpStatus(401);
                            $response->send();
                        }
                        $app->log->debug("Implicit Grant Flow - Client valid: ".print_r($clientValid,true));
                        if ($isAuth == true && $clientValid != false) {
                            $app->log->debug("Implicit Grant Flow - proceed to grant permission form" );
                            $temp_authenticity_token = TokenLib::generateSerializedToken($username, $api_key, "", "", 10);
                            $oauth2_consent_message = $clients_model->getOAuth2ConsentMessage($api_key);
                            
                            OAuth2Model::render($app, 'REST OAuth - Client autorisieren', 'oauth2grantpermissionform.php', array(
                                'api_key' => $api_key, 
                                'redirect_uri' => $redirect_uri, 
                                'response_type' => $response_type, 
                                'authenticity_token' => $temp_authenticity_token, 
                                'oauth2_consent_message' => $oauth2_consent_message
                            ));
                        } else {
                            OAuth2Model::render($app, 'REST OAuth - Login für Tokengenerierung', 'oauth2loginform.php', array(
                                'error_msg' => "Username or password incorrect!",
                                'api_key' => $api_key, 
                                'redirect_uri' => $redirect_uri, 
                                'response_type' => $response_type
                            ));
                        }
                    } elseif ($authenticity_token && $redirect_uri) {
                        $authenticity_token = TokenLib::deserializeToken($authenticity_token);
                        $user = $authenticity_token['user'];

                        if (!TokenLib::tokenExpired($authenticity_token)) { // send bearer token
                            $clients_model = new ClientsModel();
                            if ($clients_model->clientExists($api_key)) {
                                if ($clients_model->is_oauth2_gt_implicit_enabled($api_key)) {
                                    $bearerToken = TokenLib::generateBearerToken($user, $api_key);
                                    $url = $redirect_uri . "#access_token=".$bearerToken['access_token']."&token_type=bearer"."&expires_in=".$bearerToken['expires_in']."&state=xyz";
                                    $app->redirect($url);
                                }
                            }
                            $url = $redirect_uri . "#access_token="."no_access";
                            $app->redirect($url);
                        }
                    }
                } else { // no consent message
                    $app->log->debug("Implicit Grant Flow - Without Consent Message ");
                    if ($redirect_uri && $api_key && is_null($authenticity_token) && is_null($username) && is_null($password)) {
                        $app->log->debug("Implicit Grant Flow - Rendering LoginForm ");
                        
                        OAuth2Model::render($app, 'REST OAuth - Login für Tokengenerierung', 'oauth2loginform.php', array(
                            'api_key' => $api_key, 
                            'redirect_uri' => $redirect_uri, 
                            'response_type' => $response_type
                        ));
                    } elseif ($username && $password) {
                        $isAuth = AuthLib::authenticateViaIlias($username, $password);
                        $clientValid = AuthLib::checkOAuth2Client($api_key);

                        if ($isAuth == true) {
                            $app->log->debug("Implicit Grant Flow - Auth valid");
                        } else {
                            $app->log->debug("Implicit Grant Flow - Auth NOT valid");
                            $response->setHttpStatus(401);
                        }
                        $app->log->debug("Implicit Grant Flow - Client valid: ".print_r($clientValid,true));
                        if ($isAuth == true && $clientValid != false) {
                            $bearerToken = TokenLib::generateBearerToken($username, $api_key);
                            $url = $redirect_uri . "#access_token=".$bearerToken['access_token']."&token_type=bearer"."&expires_in=".$bearerToken['expires_in']."&state=xyz";
                            $app->redirect($url);
                        }else {
                            OAuth2Model::render($app, 'REST OAuth - Login für Tokengenerierung', 'oauth2loginform.php', array(
                                'error_msg' => "Username or password incorrect!",
                                'api_key' => $api_key, 
                                'redirect_uri' => $redirect_uri, 
                                'response_type' => $response_type
                            ));
                        }
                    } // username, passw
                }
            } else {
                $response->setHttpStatus(401);
                $response->send();
            }
        } else {
            $response->setHttpStatus(401);
            $response->send();
        }

    }
    // ----------------------------------------------------------------------------------------------
    // Token endpoint routines
    
    
    /**
     * The token endpoint part of the user credentials auth flow.
     * @param $app
     * @param $request
     */
    public function handleTokenEndpoint_userCredentials($app, $request) {
        $response = new Oauth2Response($app);
        $user = $request->getParam('username');
        $pass = $request->getParam('password');
        $api_key = $request->getParam('api_key');
        
        $isAuth = AuthLib::authenticateViaIlias($user, $pass);

        if ($isAuth == false) {
            $response->setHttpStatus(401);
            $response->setOutputFormat('plain');
            $response->send();
        } else {
            $clients_model = new ClientsModel();
            if ($clients_model->clientExists($api_key)) {
                if ($clients_model->is_oauth2_gt_resourceowner_enabled($api_key)) {
                    $allowed_users = $clients_model->getAllowedUsersForApiKey($api_key);
                    $access_granted = false;
                    $uid = (int)RESTLib::loginToUserId($user);

                    if (in_array(-1, $allowed_users) || in_array($uid, $allowed_users)) {
                        $access_granted = true;
                    }
                    if ($access_granted == true) {
                        $app->log->debug("access granted");
                        $bearer_token = TokenLib::generateBearerToken($user, $api_key);
                        $response->setHttpHeader('Cache-Control', 'no-store');
                        $response->setHttpHeader('Pragma', 'no-cache');
                        $response->setField("access_token", $bearer_token['access_token']);
                        $response->setField("expires_in", $bearer_token['expires_in']);
                        $response->setField("token_type", $bearer_token['token_type']);
                        $response->setField("scope", $bearer_token['scope']);
                        if ($clients_model->is_resourceowner_refreshtoken_enabled($api_key)) {
                            $refresh_token = $this->getRefreshToken(TokenLib::deserializeToken($bearer_token['access_token']));
                            $response->setField("refresh_token", $refresh_token);
                        }
                    } else {
                        $response->setHttpStatus(401);
                    }
                } else {
                    $response->setHttpStatus(401);
                }
            } else {
                $response->setHttpStatus(401);
            }
            $response->send();
        }
        
    }
    

    /**
     * The token endpoint part of the client credentials auth flow.
     * @param $app
     * @param $request
     */
    public function handleTokenEndpoint_clientCredentials($app, $request) {
        $response = new Oauth2Response($app);
        $api_key = $request->getParam('api_key');
        $api_secret = $request->getParam('api_secret');

        $clients_model = new ClientsModel();
        if ($clients_model->clientExists($api_key)) {
            if ($clients_model->is_oauth2_gt_clientcredentials_enabled($api_key)) {
                $uid = (int)$clients_model->getClientCredentialsUser($api_key);
                $user = RESTLib::userIdtoLogin($uid);
                $authResult = AuthLib::checkOAuth2ClientCredentials($api_key, $api_secret);
                if (!$authResult) {
                    $response->setHttpStatus(401);
                }
                else {
                    $bearer_token = TokenLib::generateBearerToken($user,$api_key);
                    $response->setHttpHeader('Cache-Control', 'no-store');
                    $response->setHttpHeader('Pragma', 'no-cache');
                    $response->setField("access_token",$bearer_token['access_token']);
                    $response->setField("expires_in",$bearer_token['expires_in']);
                    $response->setField("token_type",$bearer_token['token_type']);
                    $response->setField("scope",$bearer_token['scope']);
                }
            } else {
                $response->setHttpStatus(401);
            }
        } else {
            $response->setHttpStatus(401);
        }
        $response->send();


    }

    
    /**
     * The token endpoint part of the authorization auth flow.
     * This method exchanges an authorization code with a bearer token.
     * @param $app
     * @param $request
     */
    public function handleTokenEndpoint_authorizationCode($app, $request) {
        $app->log->debug("Handle Token-Endpoint > AuthCode Request");
        $response = new Oauth2Response($app);
        $code = $request->getParam("code");
        $redirect_uri = $request->getParam("redirect_uri");

        $api_key = $_POST['api_key'];
        $app->log->debug("Handle Token-Endpoint > api-key " . $api_key);
        $api_secret = $request->getParam('api_secret'); // also check by other means

        $app->log->debug("Handle Token-Endpoint > checkOAuth2ClientCredentials( ".$api_key.",".$api_secret.")");
        $isClientAuthorized = AuthLib::checkOAuth2ClientCredentials($api_key, $api_secret);
        
        if (!$isClientAuthorized) {
            $app->response()->status(401);
        } else {
            $app->log->debug("Handle Token-Endpoint > HERE ");
            $code_token = TokenLib::deserializeToken($code);
            $app->log->debug("Handle Token-Endpoint > code token " . print_r($code_token,true));
            if (!TokenLib::tokenExpired($code_token)){
                $t_redirect_uri = $code_token['misc'];
                $t_user = $code_token['user'];
                $t_client_id = $code_token['api_key'];

                if ($t_redirect_uri == $redirect_uri && $t_client_id == $api_key) {

                    $clients_model = new ClientsModel();
                    if ($clients_model->clientExists($t_client_id)) {
                        if ($clients_model->is_oauth2_gt_authcode_enabled($t_client_id)) {
                            $allowed_users = $clients_model->getAllowedUsersForApiKey($t_client_id);
                            $access_granted = false;
                            $uid = (int)RESTLib::loginToUserId($t_user);

                            if (in_array(-1, $allowed_users) || in_array($uid, $allowed_users)) {
                                $access_granted = true;
                            }
                            if ($access_granted == true) {
                                $app->log->debug("auth code access granted. user: ".$t_user." key: ".$api_key);
                                $bearer_token = TokenLib::generateBearerToken($t_user, $api_key);
                                $response->setHttpHeader('Cache-Control', 'no-store');
                                $response->setHttpHeader('Pragma', 'no-cache');
                                $response->setField("access_token", $bearer_token['access_token']);
                                $response->setField("expires_in", $bearer_token['expires_in']);
                                $response->setField("token_type", $bearer_token['token_type']);
                                $response->setField("scope", $bearer_token['scope']);
                                if ($clients_model->is_authcode_refreshtoken_enabled($api_key)) { // optional
                                    $refresh_token = $this->getRefreshToken(TokenLib::deserializeToken($bearer_token['access_token']));
                                    $response->setField("refresh_token", $refresh_token);
                                }

                            } else {
                                $response->setHttpStatus(401);
                            }
                        } else {
                            $response->setHttpStatus(401);
                        }
                    }

                }
            } else {
                $response->setHttpStatus(401);
            }
        }
        $response->send();
    }

    
    /**
     * Token-endpoint for refresh tokens.
     * Cf. RFC6749 Chapter 6.  Refreshing an Access Token
     * @param $app
     * @throws Exception
     */
    public function handleTokenEndpoint_refreshToken2Bearer($app) {
        $request = new RESTRequest($app);
        $response = new Oauth2Response($app);

        $refresh_token = $request->getParam("refresh_token");
        $bearer_token = $this->getBearerTokenForRefreshToken($refresh_token);

        $response->setHttpHeader('Cache-Control', 'no-store');
        $response->setHttpHeader('Pragma', 'no-cache');
        $response->setField("access_token",$bearer_token['access_token']);
        $response->setField("expires_in",$bearer_token['expires_in']);
        $response->setField("token_type",$bearer_token['token_type']);
        $response->setField("scope",$bearer_token['scope']);
        $response->send();
    }
    // ----------------------------------------------------------------------------------------------
    // Refresh Token Support
    
    
    /**
     * Returns a refresh token for a valid bearer token.
     * @param $bearer_token_array
     * @return string
     */
    public function getRefreshToken($bearer_token_array) {
        $user_id = RESTLib::loginToUserId($bearer_token_array['user']);
        $api_key = $bearer_token_array['api_key'];
        $entry = $this->_checkRefreshTokenEntry($user_id, $api_key);

        $newRefreshToken = TokenLib::serializeToken(TokenLib::generateOAuth2RefreshToken($bearer_token_array['user'], $bearer_token_array['api_key']));
        if ($entry == null) { // Create new entry
            $this->_createNewRefreshTokenEntry($user_id,  $api_key, $newRefreshToken);
            return $newRefreshToken;
        } else { // Reset an existing entry
            $this->_resetRefreshTokenEntry($user_id, $api_key, $newRefreshToken);
            return $newRefreshToken;
        }
    }

    
    /**
     * Returns a new bearer token for a valid refresh token.
     * Validation check and bookkeeping is realized via an internal refresh token table.
     * @param $refresh_token
     * @return array|bool
     */
    public function getBearerTokenForRefreshToken($refresh_token) {
        $refresh_token_array = TokenLib::deserializeToken($refresh_token);
        if (TokenLib::tokenValid($refresh_token_array) == true) {
            $user = $refresh_token_array['user'];
            $user_id = RESTLib::loginToUserId($user);
            $api_key = $refresh_token_array['api_key'];
            $entry = $this->_checkRefreshTokenEntry($user_id, $api_key);
            if ($entry == null) {
                return false;
            } else {
                if ($entry['num_refresh_left'] > 0 ) {
                    if ($entry['refresh_token'] == $refresh_token) {
                        $this->_issueExistingRefreshToken($user_id, $api_key);
                        $bearer_token = TokenLib::generateBearerToken($user, $api_key);
                        return $bearer_token;
                    } else {
                        return false;
                    }
                } else {
                    $this->_deleteRefreshTokenEntry($user_id, $api_key);
                    return false;
                }
            }
        } else {
            return "Token not valid.";
        }
    }
    

    /**
     * Returns the refresh token for an existing refresh token entry.
     * Decreases num_refresh_left field and updates the issuing time stamp.
     */
    protected function _issueExistingRefreshToken($user_id, $api_key) {
        global $ilDB;
        
        $query = "
            SELECT refresh_token, num_refresh_left 
            FROM ui_uihk_rest_oauth2 
            JOIN ui_uihk_rest_keys 
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id 
            AND ui_uihk_rest_oauth2.user_id=".$user_id." 
            AND ui_uihk_rest_keys.api_key='".$api_key."'
        ";
        $set = $ilDB->query($query);
        if ($set != null && $entry = $ilDB->fetchAssoc($set)) {
            $ct_num_refresh_left = $entry['num_refresh_left'];
            $refresh_token = $entry['refresh_token'];

            $this->_updateRefreshTokenEntry($user_id, $api_key, "num_refresh_left", $ct_num_refresh_left-1);
            $this->_updateRefreshTokenEntry($user_id, $api_key, "last_refresh_timestamp", date("Y-m-d H:i:s",time()));
            return $refresh_token;
        }
    }


    /**
     * Resets an existing refresh token entry:
     *  - Overwrites refresh token field
     *  - Increases field "num_resets"
     *  - Overwrites field num_refresh_left
     *  - Overwrites last_refresh_timestamp
     */
    protected function _resetRefreshTokenEntry($user_id, $api_key, $newRefreshToken) {
        global $ilDB;
        
        $query = "
            SELECT num_resets 
            FROM ui_uihk_rest_oauth2 
            JOIN ui_uihk_rest_keys 
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id 
            AND ui_uihk_rest_oauth2.user_id=".$user_id." 
            AND ui_uihk_rest_keys.api_key='".$api_key."'
        ";
        
        $set = $ilDB->query($query);
        if ($set != null && $entry = $ilDB->fetchAssoc($set)) {
            $ct_num_resets = $entry['num_resets'];

            $this->_updateRefreshTokenEntry($user_id, $api_key, "refresh_token", $newRefreshToken);
            $this->_updateRefreshTokenEntry($user_id, $api_key, "num_resets", $ct_num_resets + 1);
            $this->_updateRefreshTokenEntry($user_id, $api_key, "last_refresh_timestamp", date("Y-m-d H:i:s",time()));
            $this->_updateRefreshTokenEntry($user_id, $api_key, "num_refresh_left", 10000);
        }
    }

    /**
     * Tester of _checkRefreshTokenEntry
     * @param $bearer_token_array
     * @return array
     */
    /*public function getRefreshEntryInfo($bearer_token_array)
    {
        $user_id = RESTLib::loginToUserId($bearer_token_array['user']);
        $api_key = $bearer_token_array['api_key'];

        $entry = $this->_checkRefreshTokenEntry($user_id, $api_key);
        if ($entry != null) {
            $result = array();
            $result['num_refresh_left'] = $entry['num_refresh_left'];
            $result['num_resets'] = $entry['num_resets'];
            $result['last_refresh_timestamp'] = $entry['last_refresh_timestamp'];
            return $result;

        }
        return array();
    }*/

    
    /**
     * Provides information about an entry:
     * 1) Entry exists: yes or no.
     * 2) How many refreshs are left (num_refresh_left)
     * 3) Number of resets (num_resets).
     * 3) Last refresh timestamp (last_refresh_timestamp).
     *
     * @param $user_id
     * @param $api_key
     * @return array
     */
    protected function _checkRefreshTokenEntry($user_id, $api_key) {
        global $ilDB;

        $query = "
            SELECT * 
            FROM ui_uihk_rest_oauth2 
            JOIN ui_uihk_rest_keys 
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id 
            AND ui_uihk_rest_oauth2.user_id=".$user_id." 
            AND ui_uihk_rest_keys.api_key='".$api_key."'
        ";
        $set = $ilDB->query($query);
        if ($set != null && $entry = $ilDB->fetchAssoc($set))
            return $entry;
        else
            return null;
    }

    
    /**
     * Creates a new Refresh-Token Entry (helper).
     *
     * @param $user_id
     * @param $api_key
     * @param $refresh_token
     * @return mixed the insertion id
     */
    protected function _createNewRefreshTokenEntry($user_id, $api_key, $refresh_token) {
        global $ilDB;
        
        $sql = sprintf('SELECT id FROM ui_uihk_rest_keys WHERE api_key = "%s"', $api_key);
        $query = $ilDB->query($sql);
        if ($query != null && $row = $ilDB->fetchAssoc($query)) {
            $api_id = $row['id'];

            $a_columns = array(
                "user_id" => array("text", $user_id),
                "api_id" => array("text", $api_id),
                "refresh_token" => array("text", $refresh_token),
                "num_refresh_left" => array("integer", 10000),
                "last_refresh_timestamp" => array("date", date("Y-m-d H:i:s",0)),
                "init_timestamp" => array("date", date("Y-m-d H:i:s",time())),
                "num_resets" => array("integer", 0)
            );

            $ilDB->insert("ui_uihk_rest_oauth2", $a_columns);
            return $ilDB->getLastInsertId();
        }
    }

    
    /**
     * Deletes a Refresh Token Entry
     * @param $user_id
     * @param $api_key
     * @return mixed
     */
    protected function _deleteRefreshTokenEntry($user_id, $api_key) {
        global $ilDB;
        
        $query = "
            DELETE ui_uihk_rest_oauth2
            FROM ui_uihk_rest_oauth2 
            JOIN ui_uihk_rest_keys 
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id 
            AND ui_uihk_rest_oauth2.user_id=".$user_id." 
            AND ui_uihk_rest_keys.api_key='".$api_key."'
        ";
        $numAffRows = $ilDB->manipulate($query);
        
        return $numAffRows;
    }

    
    /**
     * Updates a refresh token entry (helper).
     * @param $user_id
     * @param $api_key
     * @param $fieldname
     * @param $newval
     * @return mixed
     */
    public function _updateRefreshTokenEntry($user_id, $api_key, $fieldname, $newval) {
        global $ilDB;
        
        $query = "
            UPDATE ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys 
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id 
            AND ui_uihk_rest_oauth2.user_id=".$user_id." 
            AND ui_uihk_rest_keys.api_key='".$api_key."'
            SET ".$fieldname." = \"".$newval."\"
        ";
        $numAffRows = $ilDB->manipulate($query);
        
        return $numAffRows;
    }
    // ----------------------------------------------------------------------------------------------
    // Further OAuth2 routines
    
    
    /**
     * Tokeninfo - Tokens obtained via the implicit code grant MUST by validated by the Javascript client
     * to prevent the "confused deputy problem".
     * @param $app
     */
    public function handleTokeninfoRequest($app)
    {
        $request = $app->request();
        $access_token = $request->params('access_token');
        if (!isset($access_token)) {
            $a_data = array();
            $jsondata = $app->request()->getBody(); // json
            $a_data = json_decode($jsondata, true);
            $access_token = $a_data['token'];
            if (!isset($access_token)) {
                $headers = apache_request_headers();
                $authHeader = $headers['Authorization'];
                if ($authHeader!=null) {
                    $a_auth = explode(" ",$authHeader);
                    $access_token = $a_auth[1];    // Bearer Access Token
                    if ($access_token == null) {
                        $access_token = $a_auth[0]; // Another kind of Token
                    }
                }
            }
        }

        $token = TokenLib::deserializeToken($access_token);
        $valid = TokenLib::tokenValid($token);
        $result = array();
        if ($valid) {
            $result['api_key'] = $token['api_key'];
            // scope
            $result['user'] =  $token['user'];
            $result['type'] =  $token['type'];
            $result['expires_in'] = TokenLib::getRemainingTime($token);

        } else {
            $app->response()->status(400);
            $result['error'] = "Invalid token.";
        }
        echo json_encode($result);
    }

    
    /**
     * Allows for exchanging an ilias session to a bearer token.
     * This is used for administration purposes.
     * @param $app
     */
    public function handleRTokenToBearerRequest($app)
    {

        $result = array();
        $user_id = "";
        $rtoken = "";
        $session_id = "";
        $api_key = "";

        $request = $app->request();
        if (count($request->post()) == 0) {
            $a_data = array();
            $reqdata = $app->request()->getBody(); // json
            $a_data = json_decode($reqdata, true);
            //var_dump($a_data);
            $user_id = $a_data['user_id'];
            $rtoken = $a_data['rtoken'];
            $session_id = $a_data['session_id'];
            $api_key = $a_data['api_key'];
        } else {
            $user_id = $request->params('user_id');
            $rtoken = $request->params('rtoken');
            $session_id = $request->params('session_id');
            $api_key = $request->params('api_key');
        }

        $isAuth = AuthLib::authFromIlias($user_id, $rtoken, $session_id);

        if ($isAuth == false) {
            //$app->response()->status(400);
            $result['status'] = "error";
            $result['error'] = "Invalid token.";
            $result['user_id']=$user_id;
            $result['rtoken']=$rtoken;
            $result['session_id']=$session_id;

        }
        else {
            $user = RESTLib::userIdtoLogin($user_id);
            $access_token = TokenLib::generateBearerToken($user, $api_key);
            $result['status'] = "success";
            $result['user'] = $user;
            $result['token'] = $access_token;
        }
        $app->response()->header('Content-Type', 'application/json');
        $app->response()->header('Cache-Control', 'no-store');
        $app->response()->header('Pragma', 'no-cache');
        echo json_encode($result); // output-format: {"access_token":"03807cb390319329bdf6c777d4dfae9c0d3b3c35","expires_in":3600,"token_type":"bearer","scope":null}
    }

    
    /**
     * Simplifies rendering output by allowing to reuse common code.
     * Core.php which includes many preset JavaScript and CSS libraries will always
     * be used as a base template and $file will be included into its body.
     *
     * @param $title - Sets the pages <title> tag
     * @param $file - This file will be included inside <body></body> tags
     * @param $data - Optional data (may be an array) that is passed to the template
     */
    public static function render($app, $title, $file, $data) {
        // Needed to get relative path to document-root (where restplugin.php is)
        global $ilPluginAdmin;
        
        // Build absolute-path (relative to document-root)
        $sub_dir = "core/auth/views";
        $rel_path = $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", "REST")->getDirectory();
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $scriptName = str_replace('\\', '/', $scriptName);
        $scriptName = ($scriptName == '/' ? '' : $scriptName);
        $abs_path = $scriptName."/".$rel_path."/RESTController/".$sub_dir;
        
        // Supply data to slim application
        $app->render($sub_dir.'/core.php', array(
            'tpl_path' => $abs_path,
            'tpl_title' => $title,
            'tpl_file' => $file,
            'tpl_data' => $data
        ));
    }
}

?>