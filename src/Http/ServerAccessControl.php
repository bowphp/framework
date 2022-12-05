<?php

declare(strict_types=1);

namespace Bow\Http;

use Bow\Http\Exception\ServerAccessControlException;

class ServerAccessControl
{
    /**
     * The instance of Response
     *
     * @var Response
     */
    private Response $response;

    /**
     * AccessControl constructor.
     *
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * The access control
     *
     * @param string $allow
     * @param string $excepted
     * @return $this
     */
    private function push(string $allow, string $excepted): ServerAccessControl
    {
        if ($excepted === null) {
            $excepted = '*';
        }

        $this->response->addHeader($allow, $excepted);

        return $this;
    }

    /**
     * Active Access-control-Allow-Origin
     *
     * @param  array $excepted
     * @return ServerAccessControl
     * @throws
     */
    public function allowOrigin(array $excepted): ServerAccessControl
    {
        if (count($excepted) == 0) {
            throw new ServerAccessControlException(
                'Waiting for a data table.' . gettype($excepted) . ' given.',
                E_USER_ERROR
            );
        }

        return $this->push('Access-Control-Allow-Origin', implode(', ', $excepted));
    }

    /**
     * Active Access-control-Allow-Methods
     *
     * @param  array $excepted
     * @return ServerAccessControl
     * @throws ServerAccessControlException
     */
    public function allowMethods(array $excepted): ServerAccessControl
    {
        if (count($excepted) == 0) {
            throw new ServerAccessControlException(
                'The list is empty.',
                E_USER_ERROR
            );
        }

        return $this->push('Access-Control-Allow-Methods', implode(', ', $excepted));
    }

    /**
     * Active Access-control-Allow-Headers
     *
     * @param  array $excepted
     * @return ServerAccessControl
     * @throws ServerAccessControlException
     */
    public function allowHeaders(array $excepted): ServerAccessControl
    {
        if (count($excepted) == 0) {
            throw new ServerAccessControlException('The list is empty.', E_USER_ERROR);
        }

        return $this->push('Access-Control-Allow-Headers', implode(', ', $excepted));
    }

    /**
     * Active Access-control-Allow-Credentials
     *
     * @return ServerAccessControl
     */
    public function allowCredentials(): ServerAccessControl
    {
        return $this->push('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Active Access-control-Max-Age
     *
     * @param  string $excepted
     * @return ServerAccessControl
     * @throws ServerAccessControlException
     */
    public function maxAge(string $excepted): ServerAccessControl
    {
        if (!is_numeric($excepted)) {
            throw new ServerAccessControlException(
                'Parameter must be an integer',
                E_USER_ERROR
            );
        }

        return $this->push('Access-Control-Max-Age', $excepted);
    }

    /**
     * Active Access-control-Expose-Headers
     *
     * @param  array $excepted
     * @return ServerAccessControl
     * @throws ServerAccessControlException
     */
    public function exposeHeaders(array $excepted): ServerAccessControl
    {
        if (count($excepted) == 0) {
            throw new ServerAccessControlException(
                'The list is empty',
                E_USER_ERROR
            );
        }

        return $this->push('Access-Control-Expose-Headers', implode(', ', $excepted));
    }
}
