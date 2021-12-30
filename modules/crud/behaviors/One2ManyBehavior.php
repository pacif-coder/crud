<?php
namespace app\modules\crud\behaviors;

use yii\db\ActiveRecord;

use app\modules\crud\behaviors\one2many\LinkModel;

use Exception;

/**
 *
 */
class One2ManyBehavior extends \yii\base\Behavior
{
    public $attr;

    protected $fromAttr = [];

    protected $toAttr = [];

    protected $idAttr = [];

    protected $viaTable = [];

    protected $new = [];

    protected $exist = [];

    protected $querys = [];

    public function afterInit()
    {
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
    }

    public function checkAttr($attr)
    {
        $query = $this->querys[$attr];

        $id = $this->getModelId($attr);
        $possibleIds = $query->asArray()->select($this->idAttr[$attr])->column();
        $notPossible = array_diff($this->new[$attr], $possibleIds);
        if (!$notPossible) {
            return;
        }

        $notExist = implode("', '", $notPossible);
        $this->owner->addError($attr, "No exist object with id '{$notExist}'");
    }

    public function afterSave()
    {
        foreach ((array) $this->attr as $attr) {
            if (!isset($this->new[$attr])) {
                continue;
            }

            $exist = $this->getExist($attr);
            $delete = array_diff($exist, $this->new[$attr]);
            $add = array_diff($this->new[$attr], $exist);

            if (!$delete && !$add) {
                continue;
            }

            $id = $this->getModelId($attr);
            LinkModel::setTableName($this->viaTable[$attr]);

            if ($delete) {
                $where = [
                    $this->fromAttr[$attr] => $id,
                    $this->toAttr[$attr] => $delete,
                ];
                LinkModel::deleteAll($where);
            }

            if ($add) {
                foreach ($add as $addId) {
                    $link = new LinkModel();
                    $link->{$this->fromAttr[$attr]} = $id;
                    $link->{$this->toAttr[$attr]} = $addId;
                    $link->save();
                }
            }
        }
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_INIT => 'afterInit',
        ];
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

    public function getExist($attr)
    {
        if (isset($this->exist[$attr])) {
            return $this->exist[$attr];
        }

        $id = $this->getModelId($attr);
        LinkModel::setTableName($this->viaTable[$attr]);

        return $this->exist[$attr] = LinkModel::find()->select($this->toAttr[$attr])
                    ->where([$this->fromAttr[$attr] => $id])->column();
    }

    protected function getModelId($attr)
    {
        return $this->owner->{$this->idAttr[$attr]};
    }
}