<?php
namespace Crud\behaviors;

use yii\db\ActiveRecord;

use Crud\behaviors\viaTable\LinkModel;

use Exception;

/**
 *
 */
class ViaTableBehavior extends \yii\base\Behavior
{
    public $attr;

    protected $fromAttr = [];

    protected $toAttr = [];

    protected $idAttr = [];

    protected $viaTable = [];

    protected $new = [];

    protected $exist = [];

    protected $targetModelClass = [];

    protected $querys = [];

    protected $_init;

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'initDesc',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
        ];
    }

    public function initDesc()
    {
        if ($this->_init) {
            return;
        }

        foreach ((array) $this->attr as $attr) {
            $method = "get{$attr}";
            $queryLink = $this->owner->{$method}();

            $linkDesc = $queryLink->link;
            if (count($linkDesc) > 1) {
                throw new Exception('Unsupport db link description');
            }
            $this->toAttr[$attr] = current($linkDesc);

            $viaLinkDesc = $queryLink->via->link;
            if (count($viaLinkDesc) > 1) {
                throw new Exception('Unsupport via link description');
            }
            $this->fromAttr[$attr] = key($viaLinkDesc);
            $this->idAttr[$attr] = current($viaLinkDesc);

            $this->viaTable[$attr] = current($queryLink->via->from);

            $queryLink->via = null;
            $queryLink->primaryModel = null;

            $this->querys[$attr] = $queryLink;
        }

        $this->_init = true;
    }

    public function afterSave()
    {
        $this->initDesc();
        foreach ((array) $this->attr as $attr) {
            if (!isset($this->new[$attr])) {
                continue;
            }

            $exist = $this->getExist($attr);
            $new = $this->getNew($attr);

            $delete = array_diff($exist, $new);
            $add = array_diff($new, $exist);

            if (!$delete && !$add) {
                continue;
            }

            $this->initLinkClass($attr);
            $id = $this->getModelId($attr);

            if ($delete) {
                $where = [
                    $this->fromAttr[$attr] => $id,
                    $this->toAttr[$attr] => $delete,
                ];
                LinkModel::deleteAll($where);
            }

            foreach ($add as $addId) {
                $link = new LinkModel();
                $link->{$this->fromAttr[$attr]} = $id;
                $link->{$this->toAttr[$attr]} = $addId;
                $link->save();
            }
        }
    }

    public function checkAttr($attr)
    {
        // for safe
        $this->initDesc();

        // not set attr value - skip validation
        if (!isset($this->new[$attr])) {
            return true;
        }

        $query = $this->querys[$attr];
        $possibleIds = $query->asArray()->select($this->idAttr[$attr])->column();
        $notPossible = array_diff($this->getNew($attr), $possibleIds);
        if (!$notPossible) {
            return true;
        }

        $notExist = implode("', '", $notPossible);
        $this->owner->addError($attr, "No exist object with id '{$notExist}'");
        return false;
    }

    public function __set($name, $new)
    {
        if (!in_array($name, (array) $this->attr)) {
            return parent::__set($name, $new);
        }

        $this->new[$name] = '' === $new? [] : $new;
        return true;
    }

    public function canSetProperty($name, $checkVars = true): bool
    {
        return in_array($name, (array) $this->attr) || parent::canSetProperty($name, $checkVars);
    }

    public function hasMethod($name)
    {
        foreach ((array) $this->attr as $attr) {
            $method = "check{$attr}Attr";
            if (strtolower($name) == strtolower($method)) {
                return true;
            }
        }

        return parent::hasMethod($name);
    }

    public function __call($name, $params)
    {
        foreach ((array) $this->attr as $attr) {
            $method = "check{$attr}Attr";
            if (strtolower($name) == strtolower($method)) {
                return $this->checkAttr($attr);
            }
        }

        return parent::__call($name, $params);
    }

    public function getExist($attr)
    {
        if (isset($this->exist[$attr])) {
            return $this->exist[$attr];
        }

        $this->initLinkClass($attr);

        $id = $this->getModelId($attr);
        return $this->exist[$attr] = LinkModel::find()->select($this->toAttr[$attr])
                    ->where([$this->fromAttr[$attr] => $id])->column();
    }

    public function getNew($attr)
    {
        return $this->new[$attr];
    }

    public function initLinkClass($attr)
    {
        LinkModel::setTableName($this->viaTable[$attr]);

        return LinkModel::class;
    }

    protected function getModelId($attr)
    {
        return $this->owner->{$this->idAttr[$attr]};
    }
}