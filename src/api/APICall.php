<?php

namespace api;

use shared\Logger;

class APICall
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Used for static assets in Api response
     * @var string
     */
    protected $basePath;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger = null, string $basePath = null)
    {
        $this->logger = $logger ?? new Logger(static::class);
        $this->basePath = $basePath ?? getenv('HOSTNAME') . '/uploads';
    }

    /**
     * Handy function useful for getting raw data passed as JSON in body requests
     * @param bool $fetchJSON
     * @return array
     */
    protected function getPostData(bool $fetchJSON = true): array
    {
        if ($fetchJSON && empty($_POST)) {
            $data = file_get_contents('php://input');
            return json_decode($data, true);
        } else {
            return $_POST;
        }
    }

    /**
     * @param array $data
     */
    public function success(array $data = [], int $httpResponseCode = 200)
    {
        http_response_code($httpResponseCode);
        $this->callExit($data);
    }

    /**
     * @param array|null $data
     * @param int $httpResponseCode
     */
    public function fail(int $httpResponseCode = 400, array $data = null)
    {
        http_response_code($httpResponseCode);
        $this->callExit($data);
    }

    /**
     * @param array $data
     * @param int $responseCode
     * @param string|null $message
     */
    public function respond(array $data = null, int $responseCode = 200, string $message = null)
    {
        if (!empty($message)) {
            $responseData['message'] = $message;
        }
        if (!empty($data)) {
            $responseData = array_merge($responseData, $data);
        }

        http_response_code($responseCode);
        $this->callExit($responseData);
    }

    /**
     * Handy function for testing
     * @param array $decodedJSON
     */
    private function callExit(array $decodedJSON = null)
    {
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode($decodedJSON));
    }
}
