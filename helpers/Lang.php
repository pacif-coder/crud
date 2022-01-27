<?php
namespace app\modules\crud\helpers;

use Yii;
use yii\i18n\PhpMessageSource;

use Exception;

/**
 *
 */
class Lang
{
    public static function addCategory2Path($category, $messageDir)
    {
        if (!file_exists($messageDir) || !is_dir($messageDir)) {
            throw new Exception("File path '{$messageDir}' is not exist or not directory");
        }

        $category = trim($category, '\\');
        $category .= '\\*';

        Yii::$app->i18n->translations[$category] = [
            'class' => PhpMessageSource::class,
            'basePath' => $messageDir,
        ];

        uksort(Yii::$app->i18n->translations, [self::class, '_cmpLen']);
    }

    public static function _cmpLen($key1, $key2)
    {
        return strlen($key1) < strlen($key2);
    }
}
