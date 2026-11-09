<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use humhub\modules\euro_office\models\Configuration;
use yii\web\Request;

/**
 * Signs and verifies tokens exchanged with the document server using the shared secret
 */
final class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(private readonly Configuration $config)
    {
    }

    public function encode(array $payload): string
    {
        return JWT::encode($payload, $this->config->jwtSecret, self::ALGORITHM);
    }

    /**
     * @throws \UnexpectedValueException if the token is invalid or expired
     */
    public function decode(string $token): array
    {
        return json_decode(
            json_encode(JWT::decode($token, new Key($this->config->jwtSecret, self::ALGORITHM))),
            true,
        );
    }

    /**
     * Returns the token sent by the document server in the configured header, if any
     */
    public function getTokenFromHeader(Request $request): ?string
    {
        $header = (string)$request->headers->get($this->config->jwtHeader ?: 'Authorization');
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
