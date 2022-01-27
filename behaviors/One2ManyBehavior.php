<?php
namespace app\modules\crud\behaviors;

use yii\db\ActiveRecord;

use Exception;

/**
 * XXX add ckech on key exist
 */
class One2ManyBehavior extends \yii\base\Behavior
{
    public $attr;

    protected $fromModelClass = [];

    protected $fromAttr = [];

    protected $toAttr = [];

    protected $idAttr = [];

    protected $new = [];

    protected $exist = [];

    protected $querys = [];

    public function events()
    {
        return [
            ActiveRecord::EVENT_INIT => 'afterInit',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
        ];
    }

    public function afterInit()
    {
        foreach ((array) $this->attr as $attr) {
            $method = "get{$attr}";
            $queryLink = $this->owner->{$method}();

            $linkDesc = $queryLink->link;
            if (count($linkDesc) > 1) {
                throw new Exception('Unsupport db link description');
            }

            $this->fromAttr[$attr] = key($linkDesc);

            $fromClass = $queryLink->modelClass;
            $this->fromModelClass[$attr] = $fromClass;

            $this->toAttr[$attr] = current($linkDesc);

            $keys = $fromClass::primaryKey();
            if (count($keys) > 1) {
                throw new Exception("Unsupport key count in '{$fromClass}' class");
            }

            $this->idAttr[$attr] = current($keys);

            $this->querys[$attr] = $queryLink;
        }
    }

    public function afterSave()
    {
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

            $class = $this->fromModelClass[$attr];
            $id = $this->getModelId($attr);

            if ($delete) {
                $query = $class::find()->where([$this->idAttr[$attr] => $delete]);
                foreach ($query->all() as $model) {
                    $model->{$this->fromAttr[$attr]} = null;
                    $model->save();
                }
            }

            foreach ($class::findAll([$this->idAttr[$attr] => $add]) as $model) {
                $model->{$this->fromAttr[$attr]} = $id;
                $model->save();
            }
        }
    }

    public function checkAttr($attr)
    {
        $query = $this->querys[$attr];

        $id = $this->getModelId($attr);
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

    public function getExist($attr)
    {
        if (isset($this->exist[$attr])) {
            return $this->exist[$attr];
        }

        $id = $this->getModelId($attr);
        $query = $this->fromModelClass[$attr]::find()->select($this->idAttr[$attr])
                 ->where([$this->fromAttr[$attr] => $id]);

        return $this->exist[$attr] = $query->column();
    }

    public function getNew($attr)
    {
        return $this->new[$attr];
    }

    protected function getModelId($attr)
    {
        return $this->owner->{$this->toAttr[$attr]};
    }
}