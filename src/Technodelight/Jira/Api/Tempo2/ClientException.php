<?php

namespace Technodelight\Jira\Api\Tempo2;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;

class ClientException extends \RuntimeException
{
    const UNKNOWN_ERROR = 'Unknown error happened';
    const NOT_FOUND = 'Not found';

    public static function fromException(GuzzleClientException $exception)
    {
        if ($errorResponse = self::decodeResponse($exception)) {
            return self::fromErrorResponse($errorResponse, $exception);
        } else {
            return new self($exception->getCode() == 404 ? self::NOT_FOUND : self::UNKNOWN_ERROR, $exception->getCode(), $exception);
        }
    }

    private static function fromErrorResponse(array $errorResponse, GuzzleClientException $previousException = null)
    {
        if (isset($errorResponse['errors']) && isset($errorResponse['errorMessages'])) {
            return self::fromStandardError($errorResponse, $previousException);
        } else if (isset($errorResponse['message'])) {
            return self::fromRemoteException($errorResponse, $previousException);
        }

        return self::fromUnkownError($errorResponse, $previousException);
    }

    private static function fromStandardError(array $errorResponse, GuzzleClientException $previousException = null)
    {
        return new self(
            join(PHP_EOL, array_merge($errorResponse['errors'], $errorResponse['errorMessages'])) . PHP_EOL
            . 'reasons: ' . PHP_EOL
            . join(PHP_EOL, $errorResponse['reasons']),
            $previousException ? $previousException->getCode() : null,
            $previousException
        );
    }

    private static function fromRemoteException(array $errorResponse, GuzzleClientException $previousException = null)
    {
        return new self(
            $errorResponse['message'] . PHP_EOL
            . 'stack trace: ' . PHP_EOL
            . $errorResponse['stackTrace'],
            $previousException ? $previousException->getCode() : null,
            $previousException
        );
    }

    private static function fromUnkownError(array $errorResponse, GuzzleClientException $previousException = null)
    {
        return new self(
            self::UNKNOWN_ERROR . PHP_EOL
            . 'contents of response: ' . PHP_EOL
            . var_export($errorResponse, true),
            $previousException ? $previousException->getCode() : null,
            $previousException
        );
    }

    private static function decodeResponse(GuzzleClientException $exception)
    {
        return json_decode($exception->getResponse()->getBody(), true);
    }
}
