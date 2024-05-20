<?php
namespace Crud\widgets;

use Yii;
use yii\helpers\Html;

use Crud\helpers\ParentModel;

class PathInput extends \yii\widgets\InputWidget
{
    public function run()
    {
        $path = $this->calcParentPath();

        $str = Html::beginTag('div', ['class' => 'input-group']);
        $str .= Html::tag('span', $path, ['class' => 'input-group-text']);
        $str .= $this->renderInputHtml('text');

        $str .= Html::endTag('div');

        return $str;
    }

    public function calcParentPath()
    {
        $path = $this->getHostInfo() . '/';
        if (!ParentModel::getParentModelAttr($this->model)) {
            return $path;
        }

        $parents = ParentModel::loadParents($this->model);
        foreach ($parents as $node) {
            $path .= $node['model']->path . '/';
        }

        return $path;
    }

    /**
     * Workaround yii2 [[\yii\web\Request::getHostInfo()]] - with some server
     * settings, the 'Host' parameter in the request header comes
     * with a redundant port parameter, so for https
     * connections, the value is 'https://server.com:433'
     */
    protected function getHostInfo()
    {
        $hostInfo = Yii::$app->request->getHostInfo();

        $hostInfoComponents = parse_url($hostInfo);
        if (!isset($hostInfoComponents['port'])) {
            return $hostInfo;
        }

        $secure = Yii::$app->request->getIsSecureConnection();
        $post = $hostInfoComponents['port'];
        if (($post !== 80 && !$secure) || ($post !== 443 && $secure)) {
            return $hostInfo;
        }

        $postInfo = ':' . $post;
        return substr($hostInfo, 0, -strlen($postInfo));
    }
}