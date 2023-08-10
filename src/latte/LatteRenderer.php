<?php
namespace Crud\latte;

use Yii;
use yii\base\View;
use yii\helpers\FileHelper;

use Crud\latte\FileLoader;
use Crud\latte\Engine;

/**
 *
 */
class LatteRenderer extends \yii\base\ViewRenderer
{
    /**
     * @var string the directory or path alias pointing to where Twig cache will be stored. Set to false to disable
     * templates cache.
     */
    public $cachePath = '@runtime/latte';

    /**
     */
    public $options = [
        'templateDirs' => null,
    ];

    /**
     * @var array
     */
    public $globalUseClass = [];

    /**
     * @var array Custom filters.
     */
    public $filters = [];

    /**
     * @var array Custom extensions.
     */
    public $extensions = [];

    /**
     * @var Engine
     */
    public $engine;

    public function init()
    {
        $this->engine = new Engine();

        // cache directory
        if ($this->cachePath) {
            $dir = Yii::getAlias($this->cachePath);
            FileHelper::createDirectory($dir);
            $this->engine->setTempDirectory($dir);
        }

        if ($this->globalUseClass) {
            $compiler = $this->engine->getCompiler();
            $compiler->globalUseClass = $this->globalUseClass;
        }

        if (isset($this->options['templateDirs']) && $this->options['templateDirs']) {
            $loader = new FileLoader($this->options['templateDirs']);
            $this->engine->setLoader($loader);
        }
    }

    /**
     * Renders a view file.
     *
     * This method is invoked by [[View]] whenever it tries to render a view.
     * Child classes must implement this method to render the given view file.
     *
     * @param View $view the view object used for rendering the file.
     * @param string $file the view file.
     * @param array $params the parameters to be passed to the view file.
     *
     * @return string the rendering result
     */
    public function render($view, $file, $params)
    {
        if (is_array($params)) {
            $params['view'] = $view;
            $params['app'] = Yii::$app;
            $params['context'] = $view->context;
        } elseif (is_object($params)) {
            $params->view = $view;
            $params->app = Yii::$app;
            $params->context = $view->context;
        }

        return $this->engine->renderToString($file, $params);
    }
}