<?php
namespace Crud\helpers;

use Yii;
use Crud\i18n\PhpMessageSource;
use Crud\helpers\ClassI18N;

use Exception;
use ReflectionClass;

/**
 *
 */
class Lang
{
    protected static $checked = [];

    protected static $regClass = [];

    protected static $regModuleDirs = [];

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

            return $stabTMessage;
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

    public static function regClassPref($class, $pref)
    {
        $class2path = self::getParentClass2path($class);

        $nsPref = '\\' . $pref . '\\';
        $fsPref = DIRECTORY_SEPARATOR . $pref . DIRECTORY_SEPARATOR;

        $classes = [];
        foreach ($class2path as $class => $path) {
            $pos = strpos($class, $nsPref);
            if (!$pos) {
                continue;
            }
            $ns = substr($class, 0, $pos + 1);

            $pos = strpos($path, $fsPref);
            if (!$pos) {
                continue;
            }

            $codeDir = substr($path, 0, $pos + 1);
            $messagesDir = $codeDir . 'messages';
            if (!file_exists($messagesDir) || !is_dir($messagesDir)) {
                continue;
            }

            $classes[] = $class;
            self::addCategory2Path($ns, $messagesDir);
        }

        return $classes;
    }

    public static function classes2messagesCategory($classOrClasses)
    {
        if (is_array($classOrClasses)) {
            $messageCategory = [];
            foreach ((array) $classOrClasses as $class) {
                $messageCategory[] = ClassI18N::class2messagesCategory($class);
            }

            return $messageCategory;
        }

        return ClassI18N::class2messagesCategory($classOrClasses);
    }

    public static function getParentClass2path($className)
    {
        $class2path = [];
        $ref = new ReflectionClass($className);
        $name = $ref->getName();
        if (0 === strpos($name, 'yii\\')) {
            return $class2path;
        }

        $class2path[$name] = $ref->getFileName();

        // Получаем родительский класс, пока он существует
        while ($parentClass = $ref->getParentClass()) {
            $name = $parentClass->getName();
            if (0 === strpos($name, 'yii\\')) {
                break;
            }

            $class2path[$name] = $parentClass->getFileName();
            $ref = $parentClass;
        }

        return $class2path;
    }

    public static function modelInModuleRegistered($class)
    {
        if (in_array($class, self::$regClass)) {
            return;
        }

        self::$regClass[] = $class;

        $classPos = strrpos($class, '\models\\');
        $moduleNamespace = substr($class, 0, $classPos);
        if (in_array($moduleNamespace, self::$regModuleDirs)) {
            return;
        }

        $ref = new ReflectionClass($class);
        $path = $ref->getFileName();

        $dir = DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR;
        $filePos = strrpos($path, $dir);
        $moduleDir = substr($path, 0, $filePos);

        $messageDir = $moduleDir . DIRECTORY_SEPARATOR . 'messages';
        self::addCategory2Path($moduleNamespace, $messageDir);

        self::$regModuleDirs[] = $moduleNamespace;
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
