<?php

namespace Technodelight\Jira\Api\EditApp;

use Technodelight\ShellExec\Command;
use Technodelight\ShellExec\Exec;
use Technodelight\ShellExec\Passthru;

class AdaptableFactoryWithPreference
{
    private static $supportedDrivers = ['vim'];

    public static function build($preference)
    {
        return new EditApp(self::driver($preference));
    }

    private static function driver($preference)
    {
        if (self::which($preference) && self::hasDriver($preference)) {
            $className = self::driverClass($preference);
            return new $className(new Passthru());
        }
        foreach (self::$supportedDrivers as $driver) {
            if (self::which($driver)) {
                $className = self::driverClass($driver);
                return new $className(new Passthru());
            }
        }

        throw new \UnexpectedValueException('Cannot find any driver for editing');
    }

    private static function hasDriver($preference)
    {
        return class_exists(self::driverClass($preference));
    }

    private static function which($executable)
    {
        try {
            $shell = new Exec('which');
            $shell->exec(
                Command::create()
                    ->withArgument($executable)
                    ->withStdErrTo('/dev/null')
                    ->withStdOutTo('/dev/null')
            );
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @param $preference
     * @return string
     */
    private static function driverClass($preference): string
    {
        return 'Technodelight\Jira\Api\EditApp\Driver\\' . ucfirst($preference);
    }
}
