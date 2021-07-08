<?php

namespace Nexcess\Salesforce\Authentication;

/**
 * Authentication information for a Salesforce login.
 */
interface Authentication {

  /**
   * Gets the Salesforce access token.
   */
  public function accessToken() : string;

  /**
   * Gets the Salesforce Api URL associated with the access token.
   */
  public function instanceUrl() : string;
}
