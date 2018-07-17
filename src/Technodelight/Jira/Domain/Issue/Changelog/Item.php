<?php

namespace Technodelight\Jira\Domain\Issue\Changelog;

class Item
{
    /**
     * @var mixed
     */
    private $from;
    /**
     * @var mixed
     */
    private $to;
    /**
     * @var string
     */
    private $fromString;
    /**
     * @var string
     */
    private $toString;
    /**
     * @var string
     */
    private $field;
    /**
     * @var string
     */
    private $fieldId;

    public static function fromArray(array $item)
    {
        $instance = new self;
        $instance->from = $item['from'];
        $instance->to = $item['to'];
        $instance->fromString = $item['fromString'];
        $instance->toString = $item['toString'];
        $instance->field = $item['field'];
        $instance->fieldId = isset($item['fieldId']) ? $item['fieldId'] : '';

        return $instance;
    }

    /**
     * @return mixed
     */
    public function from()
    {
        return $this->from;
    }

    /**
     * @return mixed
     */
    public function to()
    {
        return $this->to;
    }

    /**
     * @return string
     */
    public function fromString()
    {
        return $this->normalise($this->fromString);
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->normalise($this->toString);
    }

    /**
     * @return bool
     */
    public function isMultiLine()
    {
        return count(explode(PHP_EOL, $this->fromString())) > 1
            || count(explode(PHP_EOL, $this->toString())) > 1;
    }

    /**
     * @return string
     */
    public function field()
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function fieldId()
    {
        return $this->fieldId;
    }

    /**
     * Strings should be normalised as if wysiwyg was used on windows, the contents would be json encoded
     *
     * @param string $string
     * @return string
     */
    private function normalise($string)
    {
        // it can be json string as if wysiwyg was used on windows, the contents would be json encoded to preserve line endings
        if (null !== json_decode($string)) {
            $string = json_decode($string);
        }

        // line endings
        return strtr(
            $string,
            [
                "\r\n" => PHP_EOL,
            ]
        );
    }
}
