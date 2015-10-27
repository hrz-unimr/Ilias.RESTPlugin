<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth;


// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 *
 */
class Challenge extends EndpointBase {
  // Variables to controll the short-lived access-token
  const shortTTL       = 30;
  const challengeSize  = 25;
  const type           = 'short-token';


  /**
   * Function: getChallenges($userId)
   *  Fetches the stored challenge (server and client) that
   *  were send from (by client) and to (by server) to check client
   *  response.
   *
   * Parameters:
   *  $userId <Integer> - Unique user-id of user to fetch stored challenge for
   *
   * Return:
   *  server_challenge <String> - Stored client-challenge for given user
   *  client_challenge <String> - Stored server-challenge for given user
   */
  public static function getChallenges($userId) {
    // Build sql-query
    $sql = Libs\RESTLib::safeSQL('
      SELECT client_challenge, server_challenge
      FROM ui_uihk_rest_challenge
      WHERE user_id = %d',
      $userId
    );
    $query = self::getDB()->query($sql);

    // Fetch entry from database
    if ($query != null && $entry = self::getDB()->fetchAssoc($query))
      return $entry;
  }


  /**
   * Function: getChallenges($userId)
   *  Stores challenges (server and client) that were send from
   *   (by client)  and to (by server) to check client response.
   *
   * Parameters:
   *  $userId <Integer> - Unique user-id of user to store challenge for
   *  $sc <String> - Stored client-challenge to store for given user
   *  $cc <String> - Stored server-challenge to store for given user
   *
   * Return:
   *  server_challenge <String> - Stored client-challenge for given user
   *  client_challenge <String> - Stored server-challenge for given user
   */
  public static function saveChallenges($userId, $sc, $cc) {
    // Insert or update database
    $sql = Libs\RESTLib::safeSQL('
      INSERT INTO
      ui_uihk_rest_challenge
      (user_id, server_challenge, client_challenge)
      VALUES(%d, %s, %s)
      ON DUPLICATE KEY
      UPDATE
      server_challenge=%s,
      client_challenge=%s',
      $userId,
      $sc,
      $cc,
      $sc,
      $cc
    );
    self::getDB()->manipulate($sql);
  }


  /**
   * Function: checkClientResponse($cr, $cc, $sc)
   *  Checks the response given by the client to the request
   *  asked by the server.
   *
   * Parameters:
   *  $cr <String> - Client-Response (to server-challange)
   *  $cc <String> - Original Client-Challenge
   *  $sc <String> - Original Server-Challange
   *
   * Returns:
   *  <Boolean> - True if challenge was answered correctly by client
   */
  public static function checkClientResponse($accessToken, $cr) {
    // Fetch refresh-token
    $refreshToken = RefreshEndpoint::getRefreshToken($accessToken, false);

    // Load sc,cc from DB
    $challenges = self::getChallenges($accessToken->getUserId());

    // Reconstruct own version of client-response
    $cr_test  = hash('sha256', $challenges['client_challenge'] . $challenges['server_challenge'] . $refreshToken->getTokenString());

    // Compare client-repsonse with expected response
    return (strcmp($cr, $cr_test) == 0);
  }


  /**
   * Function: updateAccessToken($accessToken)
   *  Generates a short-live access-token from the
   *  initial access-token. This will only last
   *  for a few seconds.
   *
   * Parameters:
   *  $accessToken <AcessToken> - Original Access-Token
   *
   * Returns:
   *  <AcessToken> - Updated Access-Token
   */
  public static function updateAccessToken($accessToken) {
    // Modify token
    $accessToken->setEntry('ttl',  strval(time() + self::shortTTL));
    $accessToken->setEntry('type', self::type);
    $accessToken->setEntry('misc', $_SERVER['REMOTE_ADDR']);

    // Return modified access-token
    return $accessToken;
  }


  /**
   * Function: answerClientChallange($cc)
   *  Generates a server-challenge and a server-response
   *  for the client-challenge.
   *
   * Parameters:
   *  $cc <String> - Client-Challenge to answer
   *
   * Returns:
   *  server_challenge <String> - The challange that needs to be answered by the client to authenticate
   *  server_response <String> - The response for the client-challenge by the server
   */
  public static function answerClientChallange($accessToken, $cc) {
    // Fetch refresh-token
    $refreshToken = RefreshEndpoint::getRefreshToken($accessToken, false);

    //
    if (!$refreshToken)
      throw new Exception('no refresh!');

    // Generate server-challenge and server response
    $sc     = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, self::challengeSize);
    $sr     = hash('sha256', $sc . $cc . $refreshToken->getTokenString());

    // Store challenge
    self::saveChallenges($accessToken->getUserId(), $sc, $cc);

    // Answer client-challenge
    return array(
      'server_challenge'  => $sc,
      'server_response'   => $sr,
    );
  }
}
