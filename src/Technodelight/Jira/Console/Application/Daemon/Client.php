<?php

namespace Technodelight\Jira\Console\Application\Daemon;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Client
{
    private $address;
    private $port;

    public function __construct($address, $port)
    {
        $this->address = $address;
        $this->port = $port;
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->debug($output, sprintf(
            "Attempting to connect to '%s' on port '%d'...",
            $this->address,
            $this->port
        ));

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            throw new \RuntimeException("socket_create() failed: reason: " . socket_strerror(socket_last_error()));
        }


        $result = socket_connect($socket, $this->address, $this->port);
        if ($result === false) {
            throw new \RuntimeException(
                "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket))
            );
        }

        $this->debug($output, 'sending input: ' . (string) $input);

        $send = (string) $input;
        socket_write($socket, $send . PHP_EOL, strlen($send));
        while ($out = socket_read($socket, 2048)) {
            $output->write($out);
        }

        $this->debug($output, "Closing socket...");
        socket_close($socket);
    }

    private function debug(OutputInterface $output, $string)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln($string);
        }
    }
}
