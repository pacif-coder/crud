<?php
namespace Crud\widgets;

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

use Crud\helpers\ChildeClass;
use Crud\helpers\ParentModel;
use Crud\models\tree_node\Type;
use Crud\widgets\assets\TreeMenuAsset;
use Crud\models\ModelWithParentInterface;

/**
 *
 */
class TreeMenu extends \yii\base\Widget
{
    public $currentModel;

    public $asset = TreeMenuAsset::class;

    public $source = 'name-index';

    public $attrs = [
        'class' => 'tree-menu',
        'data-role' => 'tree-menu',
        'data-id' => '',
        'data-type' => '',

        'data-current-id' => null,
        'data-current-type' => null,
    ];

    protected static $template = <<<HTML
        <div class='node d-none' data-role='template'>
            <div class='exp-col' data-role='exp-col'>
                <i class='bi bi-chevron-right to-exp small' data-role='to-exp'></i>
                <i class='bi bi-chevron-down to-col small' data-role='to-col'></i>
            </div>
            <div class='tran'><a href='/' class='small' data-role='link'></a></div>
            <div class='children' data-role='children'>
                <div class='text-center' data-role='wait'>
                    <div class='spinner-border spinner-border-sm text-info'>
                        <span class='visually-hidden'></span>
                    </div>
                </div>
            </div>
        </div>
HTML;

    public function init()
    {
        parent::init();

        $view = $this->getView();
        foreach ((array) $this->asset as $asset) {
            $view->registerAssetBundle($asset);
        }
    }

    public function run()
    {
        return $this->html();
    }

    public function html()
    {
        $attrs = $this->attrs;
        $attrs['data-source'] = Url::toRoute($this->source);

        $parents = [
            ['id' => '', 'type' => ''],
        ];

        if ($this->currentModel && is_a($this->currentModel, ModelWithParentInterface::class)) {
            $parentInTree = ParentModel::loadParents($this->currentModel);
            foreach ($parentInTree as $treeModelDesc) {
                $id = $treeModelDesc['id'];
                $childeClass = ChildeClass::getChildeClass($treeModelDesc['model']);
                $type = Type::getTypeByClass($childeClass);
                $parents[] = ['id' => $id, 'type' => $type];
            }
        }
        $attrs['data-parents'] = Json::encode($parents);

        $attrs['data-not-open-last-parent'] = 1;

        return Html::tag('div', self::$template, $attrs);
    }
}