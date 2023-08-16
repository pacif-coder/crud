<?php
namespace Crud\helpers;

use Yii;
use Crud\i18n\PhpMessageSource;
use Crud\helpers\ClassI18N;

use Exception;

/**
 *
 */
class Lang
{
    protected static $checked = [];

    public static function t($category, $message, $params = [], $language = null)
    {
        $category = self::_category($category);

        if (is_array($category)) {
            $stabTMessage = self::_stab($message, $params);

            foreach ($category as $tmpCategory) {
                $tMessage = Yii::t($tmpCategory, $message, $params, $language);
                if ($stabTMessage != $tMessage) {
                    return $tMessage;
                }
            }

            return $tMessage;
        }

        if (self::isCategoryExist($category)) {
            return Yii::t($category, $message, $params, $language);
        }

        return self::_stab($message, $params);
    }

    public static function isCategoryExist($category)
    {
        $category = trim(strtolower($category), '\\');

        // allways exist
        if ('yii' == $category) {
            return true;
        }

        $translations = Yii::$app->i18n->translations;

        // try wildcard matching
        foreach (array_keys($translations) as $pattern) {
            if (strpos($pattern, '*') > 0 && strpos($category, rtrim($pattern, '*')) === 0) {
                return true;
            }
        }

        // match '*' in the last
        if (isset($translations['*'])) {
            return true;
        }

        return false;
    }

    public static function addCategory2Path($category, $messageDir, $cutCategoryPrefix = true)
    {
        if (!file_exists($messageDir) || !is_dir($messageDir)) {
            throw new Exception("File path '{$messageDir}' is not exist or not directory");
        }

        $category = trim(strtolower($category), '\\');

        $translation = [
            'class' => PhpMessageSource::class,
            'basePath' => $messageDir,
        ];

        if ($cutCategoryPrefix) {
            $translation['categoryPrefix'] = $category;
        }

        Yii::$app->i18n->translations[$category . '\\*'] = $translation;

        uksort(Yii::$app->i18n->translations, [self::class, '_cmpLen']);
    }

    public static function _cmpLen($key1, $key2)
    {
        return strlen($key1) < strlen($key2);
    }

    protected static function _stab($message, $params)
    {
        $placeholders = [];
        foreach ((array) $params as $name => $value) {
            $placeholders['{' . $name . '}'] = $value;
        }

        return ($placeholders === []) ? $message : strtr($message, $placeholders);
    }

    protected static function _category($category)
    {
        if (!is_object($category)) {
            return $category;
        }

        $class = get_class($category);
        return ClassI18N::class2messagesPath($class);
    }
}
