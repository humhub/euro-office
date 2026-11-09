<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\services;

use humhub\modules\euro_office\Module;
use humhub\modules\file\models\File;
use humhub\modules\user\models\User;
use RuntimeException;
use Yii;

/**
 * Processes the status callbacks of the document server
 *
 * @see https://api.onlyoffice.com/docs/docs-api/usage-api/callback-handler/
 */
class CallbackHandler
{
    public const STATUS_EDITING = 1;
    public const STATUS_MUST_SAVE = 2;
    public const STATUS_CORRUPTED = 3;
    public const STATUS_CLOSED_WITHOUT_CHANGES = 4;
    public const STATUS_MUST_FORCE_SAVE = 6;
    public const STATUS_FORCE_SAVE_CORRUPTED = 7;

    public function __construct(
        private readonly Module $module,
        private readonly DocumentServerClient $client,
    ) {
    }

    /**
     * @param array $data the verified callback payload
     * @throws RuntimeException if the document could not be saved
     */
    public function handle(File $file, array $data): void
    {
        $status = (int)($data['status'] ?? 0);

        if (in_array($status, [self::STATUS_CORRUPTED, self::STATUS_FORCE_SAVE_CORRUPTED], true)) {
            Yii::error('Document server reported an error while saving file ' . $file->id, 'euro-office');
            return;
        }

        if (!in_array($status, [self::STATUS_MUST_SAVE, self::STATUS_MUST_FORCE_SAVE], true)) {
            return;
        }

        if (empty($data['url'])) {
            throw new RuntimeException('Callback without document URL');
        }

        if (isset($data['filetype']) && strtolower($data['filetype']) !== DocumentFormats::getExtension($file)) {
            throw new RuntimeException('Unexpected file type ' . $data['filetype'] . ' for file ' . $file->id);
        }

        $user = $this->findEditor($file, $data);
        if ($user === null) {
            throw new RuntimeException('No user with edit permission for file ' . $file->id);
        }

        $content = $this->client->download($data['url']);
        if ($content === '') {
            throw new RuntimeException('Empty document received for file ' . $file->id);
        }

        $file->setStoredFileContent($content);
        $file->updateAttributes(['updated_by' => $user->id]);
    }

    /**
     * Returns the user who last edited the document, if this user is still allowed to edit the file
     */
    private function findEditor(File $file, array $data): ?User
    {
        // For status 2 and 6, "users" contains the user who last changed the document
        $guids = array_merge(
            (array)($data['users'] ?? []),
            array_column((array)($data['actions'] ?? []), 'userid'),
        );

        foreach (array_unique(array_filter($guids, 'is_string')) as $guid) {
            $user = User::find()->where(['guid' => $guid])->active()->one();
            if ($user !== null && $this->module->canEdit($file, $user)) {
                return $user;
            }
        }

        return null;
    }
}
