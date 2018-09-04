<?php

namespace Technodelight\Jira\Console\Application;

use Exception;
use Technodelight\Jira\Console\Application;
use Technodelight\Jira\Console\Application\Daemon\Task;

/**
 * @method static Daemon getInstance()
 */
class Daemon extends \Core_Daemon
{
    protected  $loop_interval = 1;

    /**
     * @var Application
     */
    private $app;
    /**
     * @var string
     */
    private $address = '0.0.0.0';
    /**
     * @var int
     */
    private $port = 12345;
    /**
     * @var resource
     */
    private $sock;

    public function setApplication(Application $app)
    {
        $this->app = $app;
        $this->app->setAutoExit(false);

        return $this;
    }

    public function setPort($port)
    {
        $this->port = $port;
    }

    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * The setup method will contain the one-time setup needs of the daemon.
     * It will be called as part of the built-in init() method.
     * Any exceptions thrown from setup() will be logged as Fatal Errors and result in the daemon shutting down.
     *
     * @return void
     * @throws Exception
     */
    protected function setup()
    {
        $sock = $this->sock;
        $this->on(self::ON_SHUTDOWN, function() use ($sock) {
            $this->log(sprintf('Closing sockets for %s:%d', $this->address, $this->port));
             is_resource($sock) && socket_close($sock);
        });
    }

    /**
     * The execute method will contain the actual function of the daemon.
     * It can be called directly if needed but its intention is to be called every iteration by the ->run() method.
     * Any exceptions thrown from execute() will be logged as Fatal Errors and result in the daemon attempting to restart or shut down.
     *
     * @return void
     * @throws Exception
     */
    protected function execute()
    {
        try {
            $this->openSockets();

            if (($msgsock = socket_accept($this->sock)) === false) {
                throw new \RuntimeException(
                    "socket_accept() failed: reason: " . socket_strerror(socket_last_error($this->sock))
                );
            }

            if (false === ($buf = socket_read($msgsock, 2048, PHP_NORMAL_READ))) {
                $this->log("socket_read() failed: reason: " . socket_strerror(socket_last_error($msgsock)));
                is_resource($msgsock) && socket_close($msgsock);
                return;
            }

            if ($buf == 'shutdown') {
                socket_write($msgsock, 'bye' . PHP_EOL);
                socket_close($msgsock);
                $this->shutdown(false);
                $this->restart();
            }
            $task = new Task($this->app, $buf, $msgsock);
            $this->task($task);
        } catch (Exception $e) {
            $this->shutdown(true);
            throw $e;
        }
    }

    /**
     * Return a log file name that will be used by the log() method.
     *
     * You could hard-code a string like '/var/log/myapplog', read an option from an ini file, create a simple log
     * rotator using the date() method, etc
     *
     * Note: This method will be called during startup and periodically afterwards, on even 5-minute intervals: If you
     *       start your application at 13:01:00, the next check will be at 13:05:00, then 13:10:00, etc. This periodic
     *       polling enables you to build simple log rotation behavior into your app.
     *
     * @return string
     */
    protected function log_file()
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jira-cli';
        if (@file_exists($dir) == false) {
            @mkdir($dir, 0777, true);
        }

        return $dir . '/log_' . date('Ymd');
    }

    private function openSockets()
    {
        if (is_resource($this->sock)) {
            return;
        }

        if (($this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            throw new \RuntimeException(
                "socket_create() failed: reason: " . socket_strerror(socket_last_error())
            );
        }

        if (socket_bind($this->sock, $this->address, $this->port) === false) {
            throw new \RuntimeException(
                "socket_bind() failed: reason: " . socket_strerror(socket_last_error($this->sock))
            );
        }

        if (socket_listen($this->sock, 5) === false) {
            throw new \RuntimeException(
                "socket_listen() failed: reason: " . socket_strerror(socket_last_error($this->sock))
            );
        }
        $this->log(sprintf('Listens on %s:%d', $this->address, $this->port));
    }
}
