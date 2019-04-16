<?php

namespace Technodelight\Jira\Api\JiraTagConverter\Components;

class PanelParser
{
    private $body;

    public function __construct($body)
    {
        $this->body = $body;
    }

    public function parseAndReplace()
    {
        $panels = $this->collectPanels($this->body);

        return $this->replacePanels($this->body, $panels);
    }

    /**
     * @param string $body
     * @return array
     */
    private function collectPanels($body)
    {
        $parser = new DelimiterBasedStringParser('{panel}', '{panel}');
        $matches = $parser->parse($body);
        $panels = [];
        foreach ($matches as $match) {
            $panel = new Panel();
            $panel->appendSource($match);
            $panels[] = $panel;
        }
        return $panels;
    }

    /**
     * @param string $body
     * @param Panel[] $panels
     * @return string
     */
    protected function replacePanels($body, $panels)
    {
        foreach ($panels as $panel) {
            /** @var Panel $panel */
            $originalPanel = $panel->source();

            $startPos = strpos($body, $originalPanel);
            $body = substr($body, 0, $startPos)
                . (string) $panel
                . substr($body, $startPos + strlen($originalPanel));
        }

        return $body;
    }
}
