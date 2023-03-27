<?php

namespace Prioritet\Exchange;

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\SyntaxError;

class Serialize
{

    private array $paths = [
        'products' => '/upload/exchange/products.csv',
        'cards' => '/upload/exchange/cards.csv',
        'orders' => '/upload/exchange/orders_1c.csv',
    ];


    /**
     * @throws \Exception
     */
    public function serialized($name_file)
    {
        if (!$this->paths[$name_file]) {
            throw new \Exception('Файл выгрузки не найден: ' . $name_file);
        }

        if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $this->paths[$name_file]))
        {
            throw new \Exception('Файл выгрузки не найден: ' . $name_file);
        }

        $stream = fopen($_SERVER['DOCUMENT_ROOT'] . $this->paths[$name_file], 'r');

        $csv = Reader::createFromStream($stream);

        $csv->setDelimiter(';');
        if($name_file != 'products'){
            $csv->setHeaderOffset(0);
        }
//        $csv->skipEmptyRecords();

        $stmt = Statement::create()
            ->limit(200);

        $elements = [];
        $records = $stmt->process($csv);



        if($name_file == 'products'){
            $records = $this->convert_header_duplicate($records);
        }

        foreach ($records as $record) {

            $out = [];
            foreach ($record as $key => $cell) {
                if (trim($key)) {
                    $out[trim($key)] = $cell;
                }
            }
            if ($name_file == 'cards') {
                $elements[$out['code']] = $out;
            } else {
                $elements[] = $out;
            }
        }

        return $elements;
    }

    private function object_to_array($data)
    {
        if (is_array($data) || is_object($data))
        {
            $result = [];
            foreach ($data as $key => $value)
            {
                $result[$key] = (is_array($value) || is_object($value)) ? $this->object_to_array($value) : $value;
            }
            return $result;
        }
        return $data;
    }

    private function convert_header_duplicate($records){

        $records = $this->object_to_array($records);
        $header = [];

        $new_recorder_to_header = [];
        foreach ($records as $key => $record){

            if($key == 0){
                $record[44] = 'action_price';
                foreach ($record as $cell_header){
                    $header[] = trim($cell_header);
                }
            }else{
                $new_row = [];
                foreach ($record as $key_head => $cell){
                    if($header[$key_head]){
                        $new_row[$header[$key_head]] = $cell ?? "";
                    }
                }
                $new_recorder_to_header[] = $new_row;
            }
        }

        return $new_recorder_to_header;
    }

    /**
     * @throws \Exception
     */
    public function serializedCustom($full_path_file)
    {
        if (!file_exists($full_path_file)) {
            throw new \Exception('Файл выгрузки не найден: ' . $full_path_file);
        }
        $stream = fopen($full_path_file, 'r');

        $csv = Reader::createFromStream($stream);

        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0); //set the CSV header offset
        $csv->skipEmptyRecords();

        $stmt = Statement::create();
//            ->limit(25);

        $elements = [];
        $records = $stmt->process($csv);
        foreach ($records as $record) {
            $out = [];
            foreach ($record as $key => $cell) {
                if (trim($key)) {
                    $out[trim($key)] = $cell;
                }
            }
            $elements[] = $out;
        }

        return $elements;
    }
}

