<?php
/**
 * This file is part of the Swiftype App Search PHP Client package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swiftype\AppSearch\Connection\Handler;

use GuzzleHttp\Ring\Core;
use Swiftype\AppSearch\Exception\ConnectionException;
use Swiftype\AppSearch\Exception\CouldNotConnectToHostException;
use Swiftype\AppSearch\Exception\CouldNotResolveHostException;
use Swiftype\AppSearch\Exception\OperationTimeoutException;

/**
 * This handler manage connections errors and throw comprehensive exceptions to the user.
 *
 * @package Swiftype\AppSearch\Connection\Handler
 * @author  Aurélien FOUCRET <aurelien.foucret@elastic.co>
 */
class ConnectionErrorHandler
{
    /**
     * @var callable
     */
    private $handler;

    /**
     * Constructor.
     *
     * @param callable $handler Original handler.
     */
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Proxy the response and throw an exception if a connection error is detected.
     *
     * @param array $request Request.
     *
     * @return array
     */
    public function __invoke($request)
    {
        $response = Core::proxy(($this->handler)($request), function ($response) use ($request) {
            if (isset($response['error']) === true) {
                throw $this->getConnectionErrorException($request, $response);
            }

            return $response;
        });

        return $response;
    }

    /**
     * Process error to raised a more comprehensive exception.
     *
     * @param array $request  Request.
     * @param array $response Response.
     *
     * @return ConnectionException
     */
    private function getConnectionErrorException($request, $response)
    {
        $exception = null;
        $message   = $response['error']->getMessage();
        $exception = new ConnectionException($message);
        if (isset($response['curl']['errno'])) {
            switch ($response['curl']['errno']) {
                case CURLE_COULDNT_RESOLVE_HOST:
                    $exception = new CouldNotResolveHostException($message, null, $response['error']);
                    break;
                case CURLE_COULDNT_CONNECT:
                    $exception = new CouldNotConnectToHostException($message, null, $response['error']);
                    break;
                case CURLE_OPERATION_TIMEOUTED:
                    $exception = new OperationTimeoutException($message, null, $response['error']);
                    break;
            }
        }

        return $exception;
    }
}