<?php

namespace Technodelight\Jira\Api\Shell;

class Command
{
    const TYPE_STANDALONE = 1;
    const TYPE_OPT = 2;
    const TYPE_LONGOPT = 3;
    const PREFIX_OPT = '-';
    const PREFIX_LONGOPT = '--';

    private $args = [];
    private $squashOptions = false;

    public static function create()
    {
        return new self;
    }

    public function withArgument($argument)
    {
        $this->arg(self::TYPE_STANDALONE, $argument);
        return $this;
    }

    public function withOption($option, $value = null)
    {
        $this->arg(
            $this->isLongOpt($option) ? self::TYPE_LONGOPT : self::TYPE_OPT,
            $option,
            $value
        );

        return $this;
    }

    public function pipe()
    {
        $this->arg(self::TYPE_STANDALONE, '|');

        return $this;
    }

    public function withStdErrTo($destination)
    {
        $this->arg(self::TYPE_STANDALONE, '2> ' . $destination);

        return $this;
    }

    public function withStdOutTo($destination)
    {
        $this->arg(self::TYPE_STANDALONE, '> ' . $destination);

        return $this;
    }

    public function squashOptions()
    {
        $this->squashOptions = true;
        return $this;
    }

    public function __toString()
    {
        $parts = [];
        $args = $this->args;
        if ($this->squashOptions) {
            $opts = [];
            foreach ($args as $idx => $arg) {
                if ($arg['type'] == self::TYPE_OPT) {
                    $opts[] = $this->render($arg, true);
                    unset($args[$idx]);
                }
            }
            $parts[] = $this->render(['type' => self::TYPE_OPT, 'name' => join('', $opts), 'value' => null]);
        }

        foreach ($args as $arg) {
            $parts[] = $this->render($arg);
        }

        return join(' ', $parts);
    }

    private function arg($type, $name, $value = null)
    {
        $this->args[] = [
            'type' => $type,
            'name' => $name,
            'value' => $value,
        ];
    }

    private function render(array $arg, $squashOptions = false)
    {
        if ($arg['type'] == self::TYPE_STANDALONE || ($squashOptions && !$arg['value'])) {
            return $arg['name'];
        } else {
            return sprintf(
                '%s%s%s',
                $arg['type'] == self::TYPE_LONGOPT ? self::PREFIX_LONGOPT : self::PREFIX_OPT,
                $arg['name'],
                $arg['value'] ? '=' . $arg['value'] : ''
            );
        }
    }

    private function isLongOpt($option)
    {
        return strlen($option) > 1 ? true : false;
    }
}
