<?php

namespace fparser;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class Exel implements fparserinterface
{
    private array $data;

    public function __construct($file_path)
    {
        $data = [];

        if (file_exists($file_path)) {
            $reader = new Xlsx();
            $speedsheet = $reader->load($file_path);
            $data = $speedsheet->getActiveSheet()->toArray(null, true, true, true);
        }

        if (isset($data[1])) {
            $data = [
                'fields' => array_shift($data),
                'data' => $data
            ];

            $data['fields'] = array_diff($data['fields'], [null]);

            foreach ($data['data'] as $key => $value) {
                if ($value[array_key_first($value)] === null) unset($data['data'][$key]);
            }
        }

        $this->data = $data;

        return $this;
    }

    public function get($option = '') {
        return !empty($option) && isset($this->data[$option]) ? $this->data[$option] : $this->data;
    }
}