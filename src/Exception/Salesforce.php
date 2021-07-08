<?php

namespace Nexcess\Salesforce\Exception;

use AT\Exceptable\Spl\RuntimeException;

/**
 * Errors returned from the Salesforce Api.
 */
class Salesforce extends RuntimeException {

  public const CREATE_FAILED = 1;
  public const DELETE_FAILED = 2;
  public const GET_FAILED = 3;
  public const UPDATE_FAILED = 4;
  public const UPSERT_FAILED = 5;

  public const INFO = [
    self::CREATE_FAILED => [
      'message' => 'failed to create record',
      'format' => 'failed to create record: {sf_error_code} ({status_code}) {sf_error_message}'
    ],
    self::DELETE_FAILED => [
      'message' => 'failed to delete record',
      'format' => 'failed to delete record: {sf_error_code} ({status_code}) {sf_error_message}'
    ],
    self::GET_FAILED => [
      'message' => 'failed to get record',
      'format' => 'failed to get record: {sf_error_code} ({status_code}) {sf_error_message}'
    ],
    self::UPDATE_FAILED => [
      'message' => 'failed to update record',
      'format' => 'failed to update record: {sf_error_code} ({status_code}) {sf_error_message}'
    ],
    self::UPSERT_FAILED => [
      'message' => 'failed to upsert record',
      'format' => 'failed to upsert record: {sf_error_code} ({status_code}) {sf_error_message}'
    ]
  ];
}
