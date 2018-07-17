<?php

namespace Technodelight\Jira\Helper\JiraTagConverter;

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
        $lines = explode(PHP_EOL, $body);
        $isPanelStarted = false;
        $panel = new Panel;
        $panels = [];
        foreach ($lines as $line) {
            if (strpos($line, '{panel}') !== false && !$isPanelStarted) {
                $isPanelStarted = true;
                $startPos = strpos($line, '{panel}');
                $panel->appendSource(substr($line, $startPos) . PHP_EOL);
            } else if ($isPanelStarted && strpos($line, '{panel}') !== false) {
                $panel->appendSource(substr($line, 0, strpos($line, '{panel}') + 7) . PHP_EOL);
                $panels[] = $panel;
                $panel = new Panel;
                $isPanelStarted = false;
            } else if ($isPanelStarted) {
                $panel->appendSource($line . PHP_EOL);
            }
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
