<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use humhub\modules\file\models\File;
use Throwable;
use Yii;

/**
 * Signed token embedded in the download and callback URLs handed to the document server.
 *
 * It binds the URL to a single file, so the (unauthenticated) server-to-server endpoints
 * cannot be used to access other files. Requests are additionally authenticated by the
 * JWT of the document server (see JwtService).
 *
 * The token has no expiration, because the document server calls the callback URL only
 * after the last user closed the editor, which can take an arbitrary amount of time.
 */
final class FileAccessToken
{
    private const ALGORITHM = 'HS256';

    public static function create(File $file): string
    {
        return JWT::encode(['fid' => $file->id], self::getKey(), self::ALGORITHM);
    }

    public static function findFile(?string $token): ?File
    {
        if ($token === null || $token === '') {
            return null;
        }

        try {
            $data = JWT::decode($token, new Key(self::getKey(), self::ALGORITHM));
        } catch (Throwable $e) {
            Yii::info('Invalid file access token: ' . $e->getMessage(), 'euro-office');
            return null;
        }

        return empty($data->fid) ? null : File::findOne(['id' => (int)$data->fid]);
    }

    private static function getKey(): string
    {
        return hash_hmac('sha256', 'euro-office-file-access', (string)Yii::$app->settings->get('secret'));
    }
}
