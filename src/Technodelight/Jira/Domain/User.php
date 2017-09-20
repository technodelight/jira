<?php

namespace Technodelight\Jira\Domain;

class User
{
    private $key, $name, $emailAddress, $avatarUrls, $displayName, $active, $timeZone, $locale;

    public static function fromArray(array $array)
    {
        $user = new self;
        $user->key = $array['key'];
        $user->name = $array['name'];
        $user->emailAddress = isset($array['emailAddress']) ? $array['emailAddress'] : '';
        $user->avatarUrls = isset($array['avatarUrls']) ? $array['avatarUrls'] : [];
        $user->displayName = $array['displayName'];
        $user->active = isset($array['active']) ? $array['active'] : true;
        $user->timeZone = isset($array['timeZone']) ? $array['timeZone'] : '';
        $user->locale = isset($array['locale']) ? $array['locale'] : null;
        return $user;
    }

    public function id()
    {
        return $this->key;
    }

    public function key()
    {
        return $this->key;
    }

    public function name()
    {
        return $this->name;
    }

    public function emailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @return array
     */
    public function avatarUrls()
    {
        return $this->avatarUrls;
    }

    public function displayName()
    {
        return $this->displayName;
    }

    public function active()
    {
        return $this->active;
    }

    public function timeZone()
    {
        return $this->timeZone;
    }

    public function locale()
    {
        return $this->locale;
    }

    public function __toString()
    {
        return $this->displayName();
    }
}
