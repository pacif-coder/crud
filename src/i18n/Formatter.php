<?php
namespace Crud\i18n;

use Crud\helpers\Html;

/**
 *
 */
class Formatter extends \yii\i18n\Formatter
{
    /**
     * Formats the array as HTML-encoded text paragraphs.
     * Each text paragraph is enclosed within a `<div>` tag.
     * @param array|null $value the value to be formatted.
     * @return string the formatted result.
     */
    public function asArray2paragraphs($value)
    {
        if ($value === null) {
            return $this->nullDisplay;
        }

        $result = '';
        foreach ($value as $part) {
            $result .= "<div>" . Html::encode($part) . "</div>\n";
        }

        return trim($result);
    }
}
