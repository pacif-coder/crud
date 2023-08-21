<?php
namespace Crud\export;

use Yii;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

use yii\grid\DataColumn;
use yii\helpers\FileHelper;

/**
 *
 */
class GridView2Spreadsheet extends \yii\grid\GridView
{
    public $spreadsheet;

    public $sheet;

    public $convertFormat = [
        'text' => 'raw',
        'ntext' => 'raw',
    ];

    /**
     * Runs the widget.
     */
    public function save($path)
    {
        if (!$this->spreadsheet) {
            $this->spreadsheet = new Spreadsheet();
        }

        if (!$this->sheet) {
            $this->sheet = $this->spreadsheet->getActiveSheet();
        }

        // задаем имя листа
//        $this->sheet->setTitle('Мой лист');

        $this->renderSpreadsheetItems();

        $path = Yii::getAlias($path);
        $writerType = ucfirst(pathinfo($path, PATHINFO_EXTENSION));
        $writer = IOFactory::createWriter($this->spreadsheet, $writerType);

        FileHelper::createDirectory(dirname($path), 0777);
        $writer->save($path);

        return $path;
    }

    /**
     * Renders the data models for the grid view.
     */
    public function renderSpreadsheetItems()
    {
        if ($this->showHeader) {
            $this->renderSpreadsheetHeader();
        }

        $this->renderSpreadsheetBody();
    }

    public function renderSpreadsheetBody()
    {
        $models = array_values($this->dataProvider->getModels());
        $keys = $this->dataProvider->getKeys();

        foreach ($models as $index => $model) {
            $key = $keys[$index];
            $this->renderSpreadsheetRow($model, $key, $index);
        }
    }

    public function renderSpreadsheetRow($model, $key, $index)
    {
        $col = 1;
        foreach ($this->columns as $column) {
            /* @var $column Column */
            if (!is_a($column, DataColumn::class)) {
                continue;
            }

            $val = $column->getDataCellValue($model, $key, $index);
            if (null !== $val) {
                $val = $this->formatter->format($val, $column->format);
            } else {
                $val = '';
            }

            $this->sheet->setCellValueByColumnAndRow($col, $index + 2, $val);
            $col++;
        }
    }

    /**
     * Renders the table header.
     */
    public function renderSpreadsheetHeader()
    {
        $col = 1;
        foreach ($this->columns as $column) {
            /* @var $column DataColumn */
            if (!is_a($column, DataColumn::class)) {
                continue;
            }

            if (isset($this->convertFormat[$column->format])) {
                $column->format = $this->convertFormat[$column->format];
            }

            $header = $column->header? $column->header : $column->label;
            $this->sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }
    }
}