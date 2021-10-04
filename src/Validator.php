<?php
/**
 * @package Nexcess/Salesforce
 * @author Nexcess.net <nocworx@nexcess.net>
 * @copyright 2021 LiquidWeb Inc.
 * @license MIT
 */

namespace Nexcess\Salesforce;

use Nexcess\Salesforce\Error\Validation as ValidationException;

/**
 * Validation utility methods.
 *
 * All validation methods should accept a value as the first argument (may be typehinted)
 *  and throw a ValidationException if validation fails.
 * Validation methods should return void.
 *
 * Extend this class and add your own validation codes/messages,
 *  or use ValidatationException::createWithMessage() to throw an ad-hoc exception with a cutom message.
 */
class Validator {

  /**
   * Validates a Salesforce 18-character alphanumeric Id.
   *
   * @param string $value
   * @throws ValidationException BAD_ID if validation fails
   */
  public static function byteLength(string $value, ? int $minimum, ? int $maximum) : void {
    $byte_length = strlen($value);
    if (
      ($minimum === null || $byte_length >= $minimum) &&
      ($maximum === null || $byte_length <= $maximum)
    ) {
      return;
    }

    throw ValidationException::create(
      ValidationException::BAD_BYTE_LENGTH,
      ['value' => $value, 'length' => $byte_length, 'minimum' => $minimum, 'maximum' => $maximum]
    );
  }

  /**
   * Validates a Salesforce 18-character alphanumeric Id.
   *
   * @param string $value
   * @throws ValidationException BAD_CHARACTER_LENGTH if validation fails
   */
  public static function characterLength(string $value, ? int $minimum, ? int $maximum) : void {
    $character_length = mb_strlen($value);
    if (
      ($minimum === null || $character_length >= $minimum) &&
      ($maximum === null || $character_length <= $maximum)
    ) {
      return;
    }

    throw ValidationException::create(
      ValidationException::BAD_CHARACTER_LENGTH,
      ['value' => $value, 'length' => $character_length, 'minimum' => $minimum, 'maximum' => $maximum]
    );
  }

  /**
   * Validates a value as one of an enumerated set.
   *
   * @param string $value
   * @param string[] $valid_values
   * @throws ValidationException BAD_ENUM_VALUE if validation fails
   */
  public static function enum(string $value, array $valid_values) : void {
    if (in_array($value, $valid_values)) {
      return;
    }

    throw ValidationException::create(
      ValidationException::BAD_ENUM_VALUE,
      ['value' => $value, 'valid_values' => join('|', $valid_values)]
    );
  }

  /**
   * Validates a Salesforce 18-character alphanumeric Id.
   *
   * @param string $value
   * @throws ValidationException BAD_ID if validation fails
   */
  public static function Id(string $value) : void {
    if (preg_match('(^[a-z0-9]{18}$)i', $value)) {
      return;
    }

    throw ValidationException::create(ValidationException::BAD_ID, ['value' => $value]);
  }
}