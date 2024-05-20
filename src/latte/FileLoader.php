<?php
declare(strict_types=1);

namespace Crud\latte;

use Yii;
use Latte;
use Latte\Helpers;

/**
 *
 */
class FileLoader extends \Latte\Loaders\FileLoader
{
    /** @var array|null */
    protected $templateDirs;

    public function __construct(?array $templateDirs = null)
    {
        $this->templateDirs = $templateDirs? [] : null;

        foreach ($templateDirs as $i => $dir) {
            $this->templateDirs[$i] = $this->normalizePath("{$dir}/");
        }
    }

    public function getReferredName($file, $referringFile): string
    {
//        if ($file && DIRECTORY_SEPARATOR == $file{0}) {
//            return $file;
//        }

        $parent = parent::getReferredName($file, $referringFile);
        if ($referringFile != $parent && file_exists($parent) ) {
//            return $parent;
        }

        $suffix = null;
        foreach ($this->templateDirs as $dir) {
            if (!Helpers::startsWith($parent, $dir)) {
                continue;
            }

            $suffix = substr($parent, strlen($dir));
            break;
        }

        if (!$suffix) {
            throw new Latte\RuntimeException("Template '$file' is not found.");
        }

        foreach ($this->templateDirs as $dir) {
            $file = $dir . $suffix;
            if ($referringFile == $file) {
                continue;
            }

            if (file_exists($file)) {
//                Yii::info("$file $parent $referringFile");
                return $file;
            }
        }

        return $file;
/*
        if ('/var/www/modules/cms/views/default/index.latte' == $parent && '/var/www/modules/cms/views/default/index.latte' == $referringFile) {
            return '/var/www/vendor/pacif-coder/crud/src/views/crud/index.latte';
        }

        if ('/var/www/modules/cms/views/default/edit.latte' == $parent && '/var/www/modules/cms/views/default/edit.latte' == $referringFile) {
            return '/var/www/vendor/pacif-coder/crud/src/views/crud/edit.latte';
        }

        //\Yii::info("getReferredName {$parent} $referringFile");
        return $parent;
*/
    }
}