<?php

class ControllerCronCheckProduct extends Controller
{
    public function index()
    {
        set_time_limit(0);

        $this->load->library('fparser');
        $this->fparser->load(DIR_DOWNLOAD . 'Остатки.xlsx.4StqtUSU0XnbzGJUwhOpiZSlNQIUvwHz', 'exel');

        $xlsx_data = $this->fparser->get('data');

        $sql = "SELECT p.product_id, p.model, pd.name FROM " . DB_PREFIX . "product p 
                                    JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '1'";

        $db_data = $this->db->query($sql)->rows;

        $result = $this->compareData($xlsx_data, $db_data);

        echo '<pre style="background-color: rgba(255,119,122, 0.5)">';
        print_r($result[1]);
        echo '</pre>';
        echo '<pre style="background-color: rgba(189,164,255, 0.5)">';
        print_r($result[0]);
        echo '</pre>';
    }

    public function test()
    {
        $db_data = [
            [
                'product_id' => '1',
                'model' => '11112',
                'name' => 'product number one'
            ],
            [
                'product_id' => '2',
                'model' => '1-2-3-4',
                'name' => 'product-number-one'
            ],
            [
                'product_id' => '3',
                'model' => '1_2_3_4',
                'name' => 'product_number_one'
            ],
            [
                'product_id' => '4',
                'model' => 'article1234b',
                'name' => 'продукт номер один'
            ],
            [
                'product_id' => '5',
                'model' => 'ARTICLE',
                'name' => 'продукт номер два'
            ]
        ];

        $xlsx_data = [
            [
                'A' => '',
                'B' => '1111 product number one',
                'C' => ''
            ],
            [
                'A' => '',
                'B' => 'product number one 1111',
                'C' => ''
            ],
            [
                'A' => '',
                'B' => 'product number one 1111 test',
                'C' => ''
            ],
            [
                'A' => '',
                'B' => 'test 1111 product number one',
                'C' => ''
            ],
            [
                'A' => '',
                'B' => '(1-2-3-4) product-number-one',
                'C' => ''
            ],
            [
                'A' => '',
                'B' => 'product_number_one 1_2_3_4',
                'C' => ''
            ],
            [
                'A' => '',
                'B' => 'article1234b продукт номер один',
                'C' => ''
            ],
            [
                'A' => '',
                'B' => 'продукт номер два ArTIcLE',
                'C' => ''
            ],
            [
                'A' => '',
                'B' => 'тест Article1234B продукт номер один',
                'C' => ''
            ],
            [
                'A' => '',
                'B' => 'продукт номер один article1234B тест',
                'C' => ''
            ],
            [
                'A' => '',
                'B' => null,
                'C' => ''
            ]
        ];

        $result = $this->compareData($xlsx_data, $db_data);

        echo '<pre style="background-color: rgba(255,119,122, 0.5)">';
            print_r($result[1]);
        echo '</pre>';
        echo '<pre style="background-color: rgba(189,164,255, 0.5)">';
        print_r($result[0]);
        echo '</pre>';
    }

    private function compareData(array $xlsx, array $db): array
    {
        $match = [];
        $not_match_in_db = [];

        $i = 0;

        foreach ($db as $db_key => $db_value) {

            $model = false;
            $name = [
                'value' => '',
                'count' => 0,
            ];

            foreach ($xlsx as $xlsx_key => $xlsx_value) {

                if (!empty($xlsx_value['B']) && mb_strlen($db_value['model'], 'UTF-8') > 3 && $this->checkArticle($xlsx_value['B'], $db_value['model'])) {
                    $model = true;

                    $match[$i]['[db] model'] = '<b>' . $db_value['model'] . '</b>';
                    $match[$i]['[file] product_name'] = $xlsx_value['B'];

                    break;
                } else {
                    if (!empty($xlsx_value['B'])) {
                        $checkName = $this->checkName($xlsx_value['B'], $db_value['model'] . ' ' . $db_value['name']);
                        if ($name['count'] < $checkName['count']) {
                            $name['count'] = $checkName['count'];
                            $name['value'] = $xlsx_value['B'];
                        }
                    }
                }

            }

            if (!$model && $name['count'] > 0) {
                $match[$i]['[db] name'] = '<span style="color: rgba(124,108,168, 0.5)">' . $db_value['model'] . '</span> <b>' . $db_value['name'] . '</b>';
                $match[$i]['[file] product_name'] = $name['value'];
            } elseif(!$model && $name['count'] < 1) {
                $not_match_in_db[] = [
                    'model' => $db_value['model'],
                    'name' => $db_value['name']
                ];
            }

            $i++;
        }

        return [$match, $not_match_in_db];
    }

    private function checkName($haystack, $needle)
    {
        $result = [
            'items' => [],
            'count' => 0,
            'haystack' => $haystack,
            'needle' => $needle
        ];

        $needle_arr = explode(' ', $needle);

        foreach ($needle_arr as $needle_item) {
            $needle_item = trim($needle_item);

            if (str_contains($haystack, $needle_item)) {
                $result['items'][] = $needle_item;
                $result['count']++;
            }
        }

        return $result;
    }

    private function checkArticle($haystack, $needle)
    {
        return @preg_match('/(\s+|^)\\(?' . addslashes($needle) . '\\)?(\s+|$)/ui', $haystack);
    }

}