<?php

namespace Technodelight\Jira\Api\GitShell;

class XmlToArray
{
    private $nodeToForceArray;

    public function __construct($nodeToForceArray)
    {
        $this->nodeToForceArray = $nodeToForceArray;
    }

    public function asArray($string)
    {
        $array = $this->xmlAsArray($this->stringAsXml($string));
        if (isset($array[$this->nodeToForceArray]) && $array[$this->nodeToForceArray] != array_values($array[$this->nodeToForceArray])) {
            $array[$this->nodeToForceArray] = [$array[$this->nodeToForceArray]];
        }
        return $array;
    }

    private function stringAsXml($string)
    {
        return simplexml_load_string(
            sprintf('<root>%s</root>', $string),
            null,
            LIBXML_NOCDATA
        );
    }

    private function xmlAsArray($xmlObject, $out = [])
    {
        foreach ((array) $xmlObject as $index => $node) {
            $out[$index] = (is_object($node) || is_array($node)) ? $this->xmlAsArray($node) : (string) $node;
        }

        return $out;
    }
}
