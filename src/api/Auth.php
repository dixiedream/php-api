<?php

namespace api;

use controllers\Auth as ControllersAuth;
use exceptions\InvalidDataException;
use exceptions\MissingDataException;
use exceptions\PermissionDeniedException;
use models\Operator;
use models\OperatorQuery;

class Auth extends APICall
{
    public function post()
    {
        try {
            $post = $this->getPostData();
            $this->logger->info('CREATE_AUTH_REQUEST', ['username' => $post['username']]);
            $this->fieldsAreValid($post);
            $this->login($post['username'], $post['password']);
            $this->success();
        } catch (PermissionDeniedException $e) {
            $this->logger->error('CREATE_AUTH_REQUEST_FAILED', ['e' => $e]);
            $this->fail(401, ['error' => $e->getMessage()]);
        } catch (MissingDataException $e) {
            $this->logger->error('CREATE_AUTH_REQUEST_FAILED', ['e' => $e]);
            $this->fail();
        } catch (InvalidDataException $e) {
            $this->logger->error('CREATE_AUTH_REQUEST_FAILED', ['e' => $e]);
            $this->fail();
        } catch (\Exception $e) {
            $this->logger->critical('CREATE_AUTH_REQUEST_FAILED', ['e' => $e]);
            $this->fail(500);
        }
    }

    public static function getOperator()
    {
        $op = ControllersAuth::getLogged();
        return $op;
    }

    /**
     * @param string $username
     * @param string $password
     * @return Operator
     * @throws \Exception
     */
    private function login(string $username, string $password): Operator
    {
        $operator = OperatorQuery::create()->findOneByUsername($username);
        if (empty($operator)) {
            throw new InvalidDataException('Operator not found');
        }

        if (!password_verify($password, $operator->getPassword())) {
            throw new InvalidDataException('Invalid password');
        }

        if ($operator->isLocked()) {
            throw new PermissionDeniedException('Permission denied');
        }

        $operator->setLastAccess(new \DateTime('now'))->save();

        return $operator;
    }

    /**
     * @param array $post
     * @return bool
     * @throws MissingDataException
     */
    private function fieldsAreValid(array $post): bool
    {
        $fields = ['username', 'password'];
        foreach ($fields as $field) {
            if (!key_exists($field, $post)) {
                throw new MissingDataException();
            }
        }

        return true;
    }
}
