<?php
namespace app\modules\crud\grid;

use yii\base\DynamicModel;
use yii\base\Model;

class SearchModel extends DynamicModel {
    protected $_model;
    protected $_formName;
    protected $_isLoaded;

    public function setModel(Model $model) {
        foreach ($model->attributes() as $attribute) {
            $this->defineAttribute($attribute);
            $this->addRule($attribute, 'safe');
        }

        $this->_formName = $model->formName();
        $this->_isLoaded = null;
    }

    public function load($data, $formName = null) {
        return $this->_isLoaded = parent::load($data, $formName);
    }

    public function isLoaded() {
        return $this->_isLoaded;
    }

    public function filter($query) {
        foreach ($this->attributes() as $attribute) {
            $query->andFilterWhere(['like', $attribute, $this->{$attribute}]);
        }
    }

    public function formName() {
        return $this->_formName;
    }

    public function activeAttributes() {
        return $this->attributes();
    }
}