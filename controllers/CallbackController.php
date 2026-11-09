<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\euro_office\Module;
use humhub\modules\euro_office\services\CallbackHandler;
use humhub\modules\euro_office\services\DocumentServerClient;
use humhub\modules\euro_office\services\FileAccessToken;
use humhub\modules\euro_office\services\JwtService;
use humhub\modules\file\models\File;
use Throwable;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Server-to-server endpoints called by the document server.
 *
 * There is no user session: requests are authorized by the file access token in the URL
 * and the JWT signed by the document server with the shared secret.
 *
 * @property Module $module
 */
class CallbackController extends Controller
{
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public $access = ControllerAccess::class;

    private File $file;
    private JwtService $jwt;

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $configuration = $this->module->getConfiguration();
        if (!$configuration->isConfigured()) {
            throw new NotFoundHttpException();
        }

        $file = FileAccessToken::findFile(Yii::$app->request->get('token'));
        if ($file === null) {
            throw new NotFoundHttpException();
        }

        $this->file = $file;
        $this->jwt = new JwtService($configuration);

        return parent::beforeAction($action);
    }

    /**
     * The document server fetches the file to be opened
     */
    public function actionDownload()
    {
        $token = $this->jwt->getTokenFromHeader(Yii::$app->request);
        if ($token === null) {
            throw new ForbiddenHttpException('Missing token');
        }

        try {
            $this->jwt->decode($token);
        } catch (Throwable) {
            throw new ForbiddenHttpException('Invalid token');
        }

        return Yii::$app->response->sendContentAsFile(
            $this->file->store->getContent(),
            $this->file->file_name,
            ['mimeType' => $this->file->mime_type],
        );
    }

    /**
     * The document server reports the editing status, e.g. that the document must be saved
     */
    public function actionTrack()
    {
        $body = json_decode(Yii::$app->request->getRawBody(), true);
        if (!is_array($body)) {
            throw new BadRequestHttpException('Invalid JSON body');
        }

        $data = $this->getVerifiedPayload($body);

        try {
            (new CallbackHandler($this->module, new DocumentServerClient($this->module->getConfiguration())))
                ->handle($this->file, $data);
        } catch (Throwable $e) {
            Yii::error('Could not process callback for file ' . $this->file->id . ': ' . $e->getMessage(), 'euro-office');
            return $this->asJson(['error' => 1]);
        }

        return $this->asJson(['error' => 0]);
    }

    /**
     * The payload is either signed as a whole in the "token" field of the body,
     * or as "payload" claim of the token in the HTTP header
     */
    private function getVerifiedPayload(array $body): array
    {
        try {
            if (!empty($body['token']) && is_string($body['token'])) {
                return $this->jwt->decode($body['token']);
            }

            $token = $this->jwt->getTokenFromHeader(Yii::$app->request);
            if ($token !== null) {
                $decoded = $this->jwt->decode($token);
                if (isset($decoded['payload']) && is_array($decoded['payload'])) {
                    return $decoded['payload'];
                }
            }
        } catch (Throwable) {
            throw new ForbiddenHttpException('Invalid token');
        }

        throw new ForbiddenHttpException('Missing token');
    }
}
