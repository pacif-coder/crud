<?php
namespace app\modules\crud\grid;

use Yii;
use yii\helpers\Url;

use app\modules\crud\grid\GridView;
use app\modules\crud\grid\dragable\DragableAsset;

/**
 * @XXX
 *
 */
class Dragable extends \yii\base\BaseObject
{
    public $action = 'sort';

    public $orderParam = 'sort';

    public $selector = 'tbody';

    public function attach(GridView &$grid)
    {
        $view = $grid->getView();
        $view->registerAssetBundle(DragableAsset::class);

        $grid->options['data-dragable'] = true;
        $grid->options['data-dragable-selector'] = $this->selector;
        $grid->options['data-dragable-order-param'] = $this->orderParam;

        $params = Yii::$app->request->getQueryParams();
        $params[0] = $this->action;

        $grid->options['data-dragable-url'] = Url::toRoute($params);
    }

}
