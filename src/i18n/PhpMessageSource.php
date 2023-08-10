<?php
namespace Crud\i18n;

use Yii;

/**
 * @xxx
 *
 */
class PhpMessageSource extends \yii\i18n\PhpMessageSource
{
    public $categoryPrefix;

    /**
     * Returns message file path for the specified language and category.
     *
     * @param string $category the message category
     * @param string $language the target language
     * @return string path to message file
     */
    protected function getMessageFilePath($category, $language)
    {
        $language = (string) $language;
        if ($language !== '' && !preg_match('/^[a-z0-9_-]+$/i', $language)) {
            throw new InvalidArgumentException(sprintf('Invalid language code: "%s".', $language));
        }

        $messageFile = Yii::getAlias($this->basePath) . "/$language/";

        $len = strlen($this->categoryPrefix);
        if ($this->categoryPrefix && $this->categoryPrefix == substr($category, 0, $len)) {
            $category = ltrim(substr($category, $len), '\\');
        }

        if (isset($this->fileMap[$category])) {
            $messageFile .= $this->fileMap[$category];
        } else {
            $messageFile .= str_replace('\\', '/', $category) . '.php';
        }

        return $messageFile;
    }
}
