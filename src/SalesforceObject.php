<?php

namespace Nexcess\Salesforce;

use Nexcess\Salesforce\Exception\Validation as ValidationException;

/**
 * Represents a Salesforce object (record).
 *
 * Declare / assign
 */
class SalesforceObject {

  /** @var string $type Object type. */
  protected string $type;

  /**
   * @param string $type Object type
   * @param array $fields Map of object field names:values
   */
  public function __construct(string $type, array $fields) {
    $this->type = $type;
    foreach ($fields as $field => $value) {
      $this->$field = $value;
    }
  }

  /**
   * Gets the object's fields as an array.
   *
   * @param bool $omit_unset Exclude fields with no values set?
   * @return array Map of object field names:values
   */
  public function toArray(bool $omit_unset = true) : array {
    $fields = (fn ($object) => get_object_vars($object))
      ->bindTo(null, null)($this);

    return $omit_unset ?
      array_filter($fields, fn ($value) => isset($value)) :
      $fields;
  }

  /**
   * Gets the object type.
   */
  public function type() : string {
    return $this->type;
  }

  /**
   * Checks that the object is in a valid state, e.g., before object creation or update.
   *
   * @throws ValidationException If validation fails
   */
  protected function validate() : void {}
}
