<?php

namespace Technodelight\Jira\Console\IssueStats;

class Serializer
{
    /**
     * @param array $events
     * @return Event[]
     */
    public function unserialize(array $events)
    {
        return array_map(
            function(array $event) {
                return Event::fromArray($event);
            },
            $events
        );
    }

    /**
     * @param Event[] $events
     * @return array
     */
    public function serialize(array $events)
    {
        return array_map(
            function(Event $event) {
                return $event->asArray();
            },
            $events
        );
    }
}
