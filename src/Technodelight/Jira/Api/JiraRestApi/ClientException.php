<?php

namespace Technodelight\Jira\Api\JiraRestApi;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;

class ClientException extends \RuntimeException
{
    private const UNKNOWN_ERROR = 'Unknown error happened';

    public static function fromException(GuzzleClientException $exception): ClientException
    {
        if ($errorResponse = self::decodeResponse($exception)) {
            return self::fromErrorResponse($errorResponse, $exception);
        }

        return new self(self::UNKNOWN_ERROR, $exception->getCode(), $exception);
    }

    private static function fromErrorResponse(
        array $errorResponse,
        GuzzleClientException $previousException = null
    ): ClientException {
        if (isset($errorResponse['errors']) || isset($errorResponse['errorMessages'])) {
            return self::fromStandardError($errorResponse, $previousException);
        } elseif (isset($errorResponse['message'])) {
            return self::fromRemoteException($errorResponse, $previousException);
        }

        return self::fromUnknownError($errorResponse, $previousException);
    }

    private static function fromStandardError(
        array $errorResponse,
        GuzzleClientException $previousException = null
    ): ClientException {
        return new self(
            implode(PHP_EOL, array_merge($errorResponse['errors'] ?? [], $errorResponse['errorMessages'] ?? [])),
            $previousException?->getCode(),
            $previousException
        );
    }

    private static function fromRemoteException(
        array $errorResponse,
        GuzzleClientException $previousException = null
    ): ClientException {
        return new self(
            $errorResponse['message'] . PHP_EOL
            . 'stack trace: ' . PHP_EOL
            . $errorResponse['stackTrace'],
            $previousException?->getCode(),
            $previousException
        );
    }

    private static function fromUnknownError(
        array $errorResponse,
        GuzzleClientException $previousException = null
    ): ClientException {
        return new self(
            self::UNKNOWN_ERROR . PHP_EOL
            . 'contents of response: ' . PHP_EOL
            . var_export($errorResponse, true),
            $previousException?->getCode(),
            $previousException
        );
    }

    private static function decodeResponse(GuzzleClientException $exception): mixed
    {
        if (null !== $exception->getResponse()) {
            return json_decode($exception->getResponse()->getBody()->getContents(), true);
        }

        return [];
    }
}
