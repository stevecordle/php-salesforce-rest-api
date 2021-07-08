<?php

namespace Nexcess\Salesforce\Authentication;

use Nexcess\Salesforce\ {
  Authentication\Authentication,
  Exception\Authentication as AuthenticationException
};

use GuzzleHttp\Client as HttpClient;

/**
 * Password-based Salesforce authentication.
 */
class Password implements Authentication {

  protected const LOGIN_ENDPOINT = 'https://login.salesforce.com';

  /** @var string Salesforce Api access token. */
  private string $accessToken;

  /** @var string Salesforce Api URL. */
  private string $instanceUrl;

  /**
   * Authenticates with the Salesforce Api using a password.
   *
   * This method does not validate or process provided options.
   *
   * @param array $parameters Authentication parameters:
   *  - string "grant_type"
   *  - string "client_id"
   *  - string "client_secret"
   *  - string "username"
   *  - string "password"
   * @throws AuthenticationException If authentication fails
   */
  public function authenticate(array $parameters) : void {
    $response = (new HttpClient(['base_uri' => self::LOGIN_ENDPOINT]))
      ->post('/services/oauth2/token', ['form_params' => $parameters]);

    $auth = json_decode($response->getBody());
    if (! isset($auth->access_token, $auth->instance_url)) {
      AuthenticationException::throw(
        AuthenticationException::FAILED,
        ['response' => $response, 'parameters' => $this->obfuscate($parameters)]
      );
    }

    $this->accessToken = $auth->access_token;
    $this->instanceUrl = $auth->instance_url;
  }

  /**
   * Builds a new Http client using this authentication
   *
   * @throws AuthenticationException If authentication has not yet succeeded
   */
  public function httpClient() : HttpClient {
    if (! isset($this->instanceUrl, $this->accessToken)) {
      AuthenticationException::throw(AuthenticationException::NOT_AUTHENTICATED);
    }

    return new HttpClient([
      'base_uri' => $this->instanceUrl,
      'headers' => ['Authorization' => "OAuth {$this->accessToken}"]
    ]);
  }

  /**
   * Obfuscates (e.g., for logging) authentication parameters.
   *
   * The "client_secret" and "password", if present,
   *  are hashed and can be compared to expected values using password_verify().
   *
   * @param string[] $parameters The authentication parameters to obfuscate
   */
  protected function obfuscate(array $parameters) : array {
    foreach (['client_secret', 'password'] as $key) {
      if (isset($parameters[$key])) {
        $parameters[$key] = password_hash($parameters[$key], PASSWORD_DEFAULT);
      }
    }

    return $parameters;
  }
}
