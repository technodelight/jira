<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\IssueStats;

class Serializer
{
    /**
     * @param array|null $events
     * @return Event[]
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function unserialize($events): array
    {
        return array_map(
            function(array $event) {
                return Event::fromArray($event);
            },
            $events ?: []
        );
    }

    /**
     * @param Event[] $events
     * @return array
     */
    public function serialize(array $events): array
    {
        return array_map(
            function(Event $event) {
                return $event->asArray();
            },
            $events
        );
    }
}
