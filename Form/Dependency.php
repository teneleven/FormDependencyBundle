<?php

namespace Teneleven\Bundle\FormDependencyBundle\Form;

final class Dependency
{
    const MATCH = 'match';
    const NOT_MATCH = 'not_match';
    const CONTAIN = 'contain';
    const IS_EMPTY = 'empty';
    const IS_NOT_EMPTY = 'not_empty';

    /**
     * @var string
     */
    private $field;

    /**
     * @var string|null
     */
    private $value;

    /**
     * @var string
     */
    private $matchType;

    /**
     * @var bool
     */
    private $required;

    /**
     * @param string      $field
     * @param string|null $value
     * @param string|null $matchType
     * @param bool        $required
     */
    public function __construct($field, $value, $matchType = null, $required = true)
    {
        if (!$matchType) {
            $matchType = $value === null ? self::IS_EMPTY : self::MATCH;
        }

        $this->field = $field;
        $this->matchType = $matchType;
        $this->value = $value;
        $this->required = $required;
    }

    public static function match($field, $value, $required = true)
    {
        return new self($field, $value, self::MATCH, $required);
    }

    public static function notMatch($field, $value, $required = true)
    {
        return new self($field, $value, self::NOT_MATCH, $required);
    }

    public static function contain($field, $value, $required = true)
    {
        return new self($field, $value, self::CONTAIN, $required);
    }

    public static function isEmpty($field, $required = true)
    {
        return new self($field, null, self::IS_EMPTY, $required);
    }

    public static function isNotEmpty($field, $required = true)
    {
        return new self($field, null, self::IS_NOT_EMPTY, $required);
    }

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     */
    public function getMatchType()
    {
        return $this->matchType;
    }

    /**
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param bool $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function matches($value)
    {
        switch ($this->matchType) {
            case self::MATCH:
                return $value == $this->value;
                break;
            case self::NOT_MATCH:
                return $value != $this->value;
                break;
            case self::CONTAIN:
                return in_array($this->value, (array) $value);
                break;
            case self::IS_EMPTY:
                return empty($value);
                break;
            case self::IS_NOT_EMPTY:
                return !empty($value);
                break;
        }

        return false;
    }
}
