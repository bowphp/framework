<?php

namespace Bow\Http;

use Bow\Http\Exception\AccessControlException;

class AccessControl
{
    /**
     * The instance of Response
     *
     * @var Response
     */
    private $response;
    
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
    private function push($allow, $excepted)
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
     * @param  array $excepted [optional]
     * @return AccessControl
     * @throws
     */
    public function allowOrigin(array $excepted)
    {
        if (!is_array($excepted)) {
            throw new AccessControlException(
                'Waiting for a data table.' . gettype($excepted) . ' given.',
                E_USER_ERROR
            );
        }

        return $this->push(
            'Access-Control-Allow-Origin',
            implode(', ', $excepted)
        );
    }

    /**
     * Active Access-control-Allow-Methods
     *
     * @param  array $excepted
     * @return AccessControl
     * @throws AccessControlException
     */
    public function allowMethods(array $excepted)
    {
        if (count($excepted) == 0) {
            throw new AccessControlException(
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
     * @return AccessControl
     * @throws AccessControlException
     */
    public function allowHeaders(array $excepted)
    {
        if (count($excepted) == 0) {
            throw new AccessControlException('The list is empty.', E_USER_ERROR);
        }

        return $this->push('Access-Control-Allow-Headers', implode(', ', $excepted));
    }

    /**
     * Active Access-control-Allow-Credentials
     *
     * @return AccessControl
     */
    public function allowCredentials()
    {
        return $this->push('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Active Access-control-Max-Age
     *
     * @param  string $excepted
     * @return AccessControl
     * @throws AccessControlException
     */
    public function maxAge($excepted)
    {
        if (!is_numeric($excepted)) {
            throw new AccessControlException(
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
     * @return AccessControl
     * @throws AccessControlException
     */
    public function exposeHeaders(array $excepted)
    {
        if (count($excepted) == 0) {
            throw new AccessControlException(
                'The list is empty',
                E_USER_ERROR
            );
        }

        return $this->push('Access-Control-Expose-Headers', implode(', ', $excepted));
    }
}
