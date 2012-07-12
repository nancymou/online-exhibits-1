<?php

class CsvImport_ColumnMap_Url extends CsvImport_ColumnMap
{
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = CsvImport_ColumnMap::TARGET_TYPE_URL;
    }

    public function map($row, $result)
    {
        $urlString = trim($row[$this->_columnName]);
        if ($urlString) {
            $urls = explode(',', $urlString);
            $result[] = $urls;
        }
        return $result;
    }
}
