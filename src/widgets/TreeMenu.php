<?php
namespace Crud\widgets;

use yii\helpers\Html;

use Crud\models\tree_node\Node;
use Crud\models\tree_node\Type;
use Crud\widgets\assets\TreeMenuAsset;

/**
 *
 */
class TreeMenu extends \yii\base\Widget
{
    public $currentId = null;

    public $notRoot = true;

    public $rootName;

    public $maxLevel;

    public $plusIcon = 'bi bi-plus';

    public $minusIcon = 'bi bi-dash';

    public $asset = TreeMenuAsset::class;

    /**
     * The query that is used to fetch data
     * @var \yii\db\ActiveQueryInterface|null
     */
    protected $query;

    protected $tree = [];

    protected $nodes = [];

    protected $id2parent = [];

    protected $parent2id = [];

    protected $isFolders = [];

    protected $currentFolderId;

    protected $currentParents = [];

    protected static $nullParentId = 0;

    protected $_isRoot;

    protected $_isHideRoot;

    public function init()
    {
        parent::init();

        $view = $this->getView();
        foreach ((array) $this->asset as $asset) {
            $view->registerAssetBundle($asset);
        }

        $this->nodes = $this->isFolders = [];
        $this->parent2id = $this->id2parent = [];
        $this->currentFolderId = null;

        $query = $this->getQuery();
        foreach ($query->all() as $node) {
            /* @var $node Node */
            if (null === $node->parent_id) {
                $node->parent_id = self::$nullParentId;
            }

            $this->nodes[$node->id] = $node->name;
            $this->id2parent[$node->id] = $node->parent_id;
            $this->parent2id[$node->parent_id][] = $node->id;

            $isFolder = Type::isFolderByType($node->type_id);
            $this->isFolders[$node->id] = $isFolder;

            if (!$this->currentId || $this->currentId != $node->id) {
                continue;
            }

            if ($isFolder) {
                $this->currentFolderId = $node->id;
            } else {
                $this->currentFolderId = $node->parent_id;
            }
        }

        $this->currentParents = [];
        if ($this->currentFolderId) {
            $id = $this->currentFolderId;
            $this->currentParents[] = $id;
            while (isset($this->id2parent[$id])) {
                $id = $this->id2parent[$id];
                $this->currentParents[] = $id;
            }
        }

        $this->tree = [];
        $this->date2tree($this->tree, self::$nullParentId);
    }

    protected function date2tree(&$tree, $parent)
    {
        $tree[$parent] = (object) [
            'id' => $parent,
            'name' => $parent != self::$nullParentId? $this->nodes[$parent] : null,
            'isExpand' => in_array($parent, $this->currentParents),
            'isFolder' => !$parent || isset($this->isFolders[$parent]) && $this->isFolders[$parent],
        ];

        if (!isset($this->parent2id[$parent])) {
            return;
        }

        $tree[$parent]->nodes = [];
        foreach ($this->parent2id[$parent] as $id) {
            $this->date2tree($tree[$parent]->nodes, $id);
        }
    }

    public function run()
    {
        echo $this->html();
    }

    public function html()
    {
        return $this->_tree2html($this->tree[self::$nullParentId]);
    }

    protected function _tree2html($node)
    {
        $str = '';

        $id = $node->id;
        $this->_isRoot = self::$nullParentId == $id;
        $this->_isHideRoot = $this->_isRoot && $this->notRoot;

        $attrs = $this->getNodeAttrs($node);
        $str .= Html::beginTag('div', $attrs) . "\n";

        $str .= $this->getNodeName($node);

        if (isset($node->nodes)) {
            $str .= Html::beginTag('div', ['class' => 'nodes']) . "\n";

            foreach ($node->nodes as $node) {
                $str .= $this->_tree2html($node);
            }

            $str .= "</div>\n";
        }

        $str .= "</div>\n";

        return $str;
    }

    protected function getNodeAttrs($node)
    {
        $attrs = ['class' => 'tree-node'];
        if ($this->_isHideRoot) {
            Html::addCssClass($attrs, 'not-root');
        }

        if ($this->_isRoot) {
            Html::addCssClass($attrs, 'tree-menu');
        }

        if ($node->isExpand || $this->_isRoot) {
            Html::addCssClass($attrs, 'expanded');
        } else {
            Html::addCssClass($attrs, 'collapsed');
        }

        if (!isset($node->nodes) || !$node->nodes) {
            Html::addCssClass($attrs, 'no-nodes');
        }

        return $attrs;
    }

    protected function getNodeName($node)
    {
        if ($this->_isHideRoot) {
            return '';
        }

        $str = '';
        $id = $node->id;

        $attrs = [];
        if (isset($node->nodes) && $node->nodes) {
            $str .= Html::tag('i', '', ['class' => "{$this->plusIcon} to-expand"]);
            $str .= Html::tag('i', '', ['class' => "{$this->minusIcon} to-collapse"]);
        }

        if ($this->currentId && $this->currentId == $id) {
            Html::addCssClass($attrs, 'link-danger');
        }

        $name = ($this->_isRoot)? $this->rootName : $node->name;
        $str .= Html::a($name, [$this->getNodeAction($node), 'id' => $id], $attrs);
        $str .= "\n";

        return $str;
    }

    public function getQuery()
    {
        if (null !== $this->query) {
            return $this->query;
        }

        $this->query = Node::getClass()::find()->orderBy('level, parent_id, sort');
        if ($this->maxLevel) {
            $this->query->where(['<=', 'level', $this->maxLevel]);
        }

        return $this->query;
    }

    public function setQuery(ActiveQueryInterface $query)
    {
        $this->query = $query;
    }

    public function getNodeAction($node)
    {
        return $node->isFolder? 'index' : 'update';
    }
}