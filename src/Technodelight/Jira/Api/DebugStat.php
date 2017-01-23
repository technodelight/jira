<?php

namespace Technodelight\Jira\Api;

class DebugStat
{
    private $enabled = false;
    private $stat;
    private $measure;
    private $start;

    public function start($method, $url, $data = null)
    {
        if ($this->enabled) {
            $this->start = microtime(true);
            $this->measure = [
                'method' => $method,
                'url' => $url,
                'data' => $data,
                'time' => 0
            ];
        }
    }

    public function stop()
    {
        if ($this->enabled) {
            $this->measure['time'] = microtime(true) - $this->start;
            $this->stat[] = $this->measure;
            unset($this->measure);
        }
    }

    public function __construct()
    {
        if ($GLOBALS['magical_debug_mode']) {
            $this->enabled = true;
            register_shutdown_function([$this, 'printout']);
        }
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

        foreach ($stats as $id => $stat) {
            $div = [];
            if ($stat['method'] == 'multiGet') {
                foreach ($stat['data'] as $url) {
                    $div[] = [
                        'method' => 'multiGet',
                        'url' => $url,
                        'data' => null,
                        'time' => $stat['time'] / count($stat['data']),
                        'calls' => $stat['calls']
                    ];
                }
                unset($stats[$id]);
            }
            foreach ($div as $measure) {
                $stats[] = $measure;
            }
        }

        usort($stats, function($a, $b) {
            if ($a['time'] == $b['time']) {
                return 0;
            }
            return $a['time'] < $b['time'] ? 1 : -1;
        });

        $totalCalls = 0;
        $totalTime = 0;
        foreach ($stats as $stat) {
            echo sprintf(
                '%s %s, calls %d time %1.4f' . PHP_EOL,
                strtoupper($stat['method']),
                $stat['url'],
                $stat['calls'],
                $stat['time']
            );
            $totalCalls+= (int) $stat['calls'];
            $totalTime+= (float) $stat['time'];
        }
        echo sprintf(
            'total: calls %d time %1.4f avg %1.4f' . PHP_EOL,
            $totalCalls,
            $totalTime,
            $totalTime / $totalCalls
        );
    }
}
