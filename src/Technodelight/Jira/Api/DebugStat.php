<?php

namespace Technodelight\Jira\Api;

class DebugStat
{
    private $stat = [];
    private $measure;
    private $start;

    public function start($method, $url, $data = null)
    {
        $this->start = microtime(true);
        $this->measure = [
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'time' => 0
        ];
    }

    public function stop()
    {
        $this->measure['time'] = microtime(true) - $this->start;
        $this->stat[] = $this->measure;
        unset($this->measure);
    }

    public function __construct($registerShutdown = true)
    {
        if ($GLOBALS['magical_debug_mode'] && $registerShutdown) {
            register_shutdown_function([$this, 'printout']);
        }
    }

    public function merge(DebugStat $debugStat)
    {
        $this->stat = array_merge($this->stat, $debugStat->stat);
    }

    public function printout()
    {
        $stats = [];
        foreach ($this->stat as $stat) {
            $id = md5(serialize($stat));
            if (!isset($stats[$id])) {
                $stats[$id] = $stat;
                $stats[$id]['calls'] = 1;
            } else {
                $stats[$id]['time'] += $stat['time'];
                $stats[$id]['calls']++;
            }
        }

        $totalCalls = 0;
        $totalTime = 0;
        foreach ($stats as $stat) {
            printf(
                '%s %s, calls %d time %1.4f' . PHP_EOL,
                strtoupper($stat['method']),
                $stat['url'],
                $stat['calls'],
                $stat['time']
            );
            $totalCalls+= (int) $stat['calls'];
            $totalTime+= (float) $stat['time'];
        }
        printf(
            'total: calls %d time %1.4f avg %1.4f' . PHP_EOL,
            $totalCalls,
            $totalTime,
            $totalTime / $totalCalls
        );
    }
}
