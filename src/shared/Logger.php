<?php

namespace shared;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Logger
{
    /**
     * @var MonologLogger
     */
    private $monolog;

    /**
     * @param string $name
     */
    public function __construct(string $name = null)
    {
        $consoleHandler = new StreamHandler('php://stdout', MonologLogger::DEBUG);
        $this->monolog = new MonologLogger($name ?? 'CallCenter');
        $this->monolog->pushHandler($consoleHandler);
    }

    /**
     * For 200 status codes
     * @param string $message
     * @param array $data
     */
    public function info(string $message, array $data = [])
    {
        $this->monolog->info($message, $data);
    }

    /**
     * For 400 status codes
     * @param string $message
     * @param array $data
     */
    public function error(string $message, array $data = [])
    {
        $this->monolog->error($message, $data);
    }

    /**
     * For 500 status codes
     * @param string $message
     * @param array $data
     */
    public function critical(string $message, array $data = [])
    {
        $this->monolog->critical($message, $data);
    }
}
