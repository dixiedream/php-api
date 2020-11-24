<?php

namespace routes;

use exceptions\InvalidDataException;
use exceptions\MissingDataException;
use models\ApiKeyQuery;
use shared\Logger;

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }
}

class Filters
{
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(Logger $logger = null)
    {
        $this->logger = $logger ?? new Logger('authFilter');
    }

    public function auth()
    {
        try {
            $passedKey = $this->getKey();

            if (empty($passedKey)) {
                throw new MissingDataException();
            }

            $key = ApiKeyQuery::create()->findOneByKeyValue($passedKey);
            if (empty($key)) {
                throw new InvalidDataException();
            }
        } catch (MissingDataException $th) {
            $this->logger->error('AUTH_FAILED', ['e' => $th->getMessage()]);
            return $this->denyRequest();
        } catch (InvalidDataException $th) {
            $this->logger->error('AUTH_FAILED', ['e' => $th->getMessage()]);
            return $this->denyRequest();
        } catch (\Exception $th) {
            $this->logger->critical('AUTH_FAILED', ['e' => $th->getMessage()]);
            http_response_code(500);
            return false;
        }
    }

    /**
     * Check if it's super admin
     */
    public function admin()
    {
        return false;
    }

    protected function denyRequest()
    {
        http_response_code(401);
        header('WWW-Authenticate: X-API-KEY realm="Access to API"');
        return false;
    }

    /**
     * @return null|string
     */
    private function getKey(): ?string
    {
        $headers = getallheaders();
        return $headers['X-API-KEY'] ?? null;
    }
}
