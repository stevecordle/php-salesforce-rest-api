<?php

namespace Nexcess\Salesforce;

use Nexcess\Salesforce\ {
  Authentication\Authentication,
  Exception\Salesforce as SalesforceException,
  Exception\Validation as ValidationException,
  Result,
  SalesforceObject
};

use GuzzleHttp\Client as HttpClient;

use Psr\Http\Message\StreamInterface as Stream;

class Client {

  /** @var string Target Salesforce Api version. */
  public const API_VERSION = 'v51.0';

  /** @var string Base path for Api requests. */
  protected const API_PATH = '/services/data/' . self::API_VERSION;

  /**
   * Relevant HTTP status codes.
   *
   * @var int HTTP_OK
   * @var int HTTP_CREATED
   * @var int HTTP_NO_CONTENT
   */
  protected const HTTP_OK = 200;
  protected const HTTP_CREATED = 201;
  protected const HTTP_NO_CONTENT = 204;

  /** @var HttpClient Api client. */
  protected HttpClient $httpClient;

  /* @var string[] Map of salesforce object type:fully qualified SalesforceObject classnames. */
  protected array $objectMap = [];

  /**
   * @param Authentication $auth Authentication details
   * @param string[] $object_map Map of salesforce object type:fully qualified classnames
   */
  public function __construct(Authentication $auth, array $object_map = []) {
    $this->httpClient = $auth->httpClient();
    $this->mapObjects($object_map);
  }

  /**
   * Creates a new Salesforce record.
   *
   * @param SalesforceObject $object The object to create
   * @throws SalesforceException CREATE_FAILED on failure
   * @throws ValidationException If object state is invalid
   */
  public function create(SalesforceObject $object) : Result {
    $object->validate();

    $response = $this->httpClient->post(
      self::API_PATH . "/sobjects/{$object->type()}",
      ['json' => $object->toArray()]
    );
    if ($response->getStatusCode() !== self::HTTP_CREATED) {
      throw SalesforceException::create(
        SalesforceException::CREATE_FAILED,
        $this->getResponseContext($response)
      );
    }

    return Result::from($response, $this->objectMap);
  }

  /**
   * Deletes an existing record in Salesforce.
   *
   * @param SalesforceObject $object The object to create
   * @throws SalesforceException DELETE_FAILED on failure
   */
  public function delete(SalesforceObject $object) : Result {
    return $this->deleteByExternalId($object->type(), 'Id', $object->id());
  }

  /**
   * Deletes an existing record in Salesforce given its type and an External Id.
   *
   * @param string $type Salesforce object type
   * @param string $id_field The External Id field to look up by
   * @param string $id 18-character Salesforce Id
   * @throws SalesforceException DELETE_FAILED on failure
   */
  public function DeleteByExternalId(string $type, string $id_field, string $id) : Result {
    $response = $this->httpClient->delete(
      self::API_PATH . "/sobjects/{$type}/{$id_field}/{$id}"
    );
    if ($response->getStatusCode() !== self::HTTP_NO_CONTENT) {
      throw SalesforceException::create(
        SalesforceException::DELETE_FAILED,
        $this->getResponseContext($response)
      );
    }

    return Result::from($response, $this->objectMap);
  }

  /**
   * Gets a Salesforce record given its type and Id.
   *
   * @param string $type Salesforce object type
   * @param string $id 18-character Salesforce Id
   * @throws SalesforceException GET_FAILED on failure
   */
  public function get(string $type, string $id) : Result {
    return $this->getByExternalId($type, 'Id', $id);
  }

  /**
   * Gets a Salesforce record given its type and an External Id.
   *
   * @param string $type Salesforce object type
   * @param string $id_field The External Id field to look up by
   * @param string $id 18-character Salesforce Id
   * @throws SalesforceException GET_FAILED on failure
   */
  public function getByExternalId(string $type, string $id_field, string $id) : Result {
    $response = $this->httpClient->get(self::API_PATH . "/sobjects/{$type}/{$id_field}/{$id}");
    if ($response->getStatusCode() !== self::HTTP_OK) {
      throw SalesforceException::create(
        SalesforceException::GET_FAILED,
        $this->getResponseContext($response)
      );
    }

    return Result::from($response, $this->objectMap);
  }

  /**
   * Maps Salesforce object types to desired php classnames.
   *
   * @param string[] $object_map Map of salesforce object type:fully qualified classnames
   */
  public function mapObjects(array $object_map) : void {
    foreach ($object_map as $type => $fqcn) {
      if (! is_a($fqcn, SalesforceObject::class, true)) {
        // throw
      }

      $this->objectMap[$type] = $fqcn;
    }
  }

  /**
   * Performs a SOQL query.
   * Template parameter values are escaped prior to formatting.
   */
  public function query(string $method, string $template, array $parameters) : Result {
    $response = $this->httpClient->get(
      self::API_PATH . "/query",
      ['query' => ['q' => $this->parseSoql($template, $parameters)]]
    );
    if ($response->getStatusCode() !== self::HTTP_OK) {
      throw SalesforceException::create(
        SalesforceException::GET_FAILED,
        $this->getResponseContext($response)
      );
    }

    return Result::from($response, $this->objectMap);
  }

  /**
   * Streams the raw http response from the given path (useful for, e.g., downloading Attachments).
   *
   * @param string $path The Api path to request
   */
  public function stream(string $path) : Stream {
    return $this->httpClient->get($path)->getBody();
  }

  /**
   * Updates an existing record in Salesforce.
   *
   * @param SalesforceObject $object The object to update from
   * @throws SalesforceException UPDATE_FAILED on failure
   * @throws ValidationException If object state is invalid
   */
  public function update(SalesforceObject $object) : Result {
    $object->validate();

    $response =$this->httpClient->patch(
      self::API_PATH . "/sobjects/{$object->type()}/{$object->id()}",
      ['json' => $object->toArray()]
    );

    if ($response->getStatusCode() !== self::HTTP_OK) {
      throw SalesforceException::create(
        SalesforceException::UPDATE_FAILED,
        $this->getResponseContext($response)
      );
    }

    return Result::from($response, $this->objectMap);
  }

  /**
   * Updates a record in Salesforce, associating it with the given External id.
   * The record is created if it does not already exist.
   *
   * @param SalesforceObject $object The object to update from
   * @param string $id_field The External Id field to look up by
   * @param string $id 18-character Salesforce Id
   * @throws SalesforceException UPSERT_FAILED on failure
   * @throws ValidationException If object state is invalid
   */
  public function upsert(SalesforceObject $object, string $id_field, string $id) : Result {
    $object->validate();

    $response = $this->httpClient->patch(
      self::API_PATH . "/sobjects/{$object->type()}/{$id_field}/{$id}",
      ['json' => $object->toArray()]
    );

    $status = $response->getStatusCode();
    if ($status !== self::HTTP_OK && $status !== self::HTTP_CREATED) {
      throw SalesforceException::create(
        SalesforceException::UPSERT_FAILED,
        $this->getResponseContext($response)
      );
    }

    return Result::from($response, $this->objectMap);
  }
}
