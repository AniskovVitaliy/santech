<?php

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ControllerCatalogParser extends Controller
{
    private array $error = [];
    private array $success = [];

    public function index()
    {
        $this->load->language('catalog/parser');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/parser');

        $this->getList();
    }

    protected function getList()
    {
        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/parser', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $files = preg_grep('/\.xlsx/', scandir(DIR_DOWNLOAD));

        foreach ($files as $file) {
            $data['files'][] = [
                'name' => $file,
                'url' => $this->url->link('catalog/parser/add', 'user_token=' . $this->session->data['user_token'] . '&file_name=' . $file, true)
            ];
        }

        $data['delete'] = $this->url->link('catalog/parser/delete', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/parser/parser_list', $data));
    }

    public function delete()
    {
        foreach ($this->request->post['selected'] as $line_number) {
            $file_name = $this->request->post['file_name'][(int)$line_number];
            unlink(DIR_DOWNLOAD . $file_name);
        }

        $this->response->redirect($this->url->link('catalog/parser', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function add()
    {
        $this->load->model('catalog/parser');
        $this->load->language('catalog/parser');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/parser');

        $xlsx = [];

        if ($this->request->get['file_name']) {
            $xlsx = $this->getExelData(DIR_DOWNLOAD . $this->request->get['file_name']);
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

            $form_data = $this->getParserData($this->request->post['db'], $this->request->post['xlsx']);

            foreach ($xlsx['data'] as $xlsx_line => $xlsx_item) {
                $product_data = $this->prepareData($form_data, $xlsx_item);

                if ($this->validateForm($product_data, $xlsx_line)) {
                    $success = $this->model_catalog_parser->addProduct($product_data);
                    $success['xlsx_line'] = $xlsx_line;
                    $this->success[] = $success;
                }
            }

        }

        $this->getForm($xlsx);
    }

    protected function getForm($xlsx)
    {
        $data['error'] = $this->error;
        $data['success'] = $this->success;

        $url = '';

        if (isset($this->request->get['file_name'])) {
            $url .= '&file_name=' . $this->request->get['file_name'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/parser', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['action'] = $this->url->link('catalog/parser/add', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['cancel'] = $this->url->link('catalog/parser', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['db_tables']['product'] = $this->model_catalog_parser->getFieldsFrom('product');
        $data['db_tables']['product_description'] = $this->model_catalog_parser->getFieldsFrom('product_description');
        $data['db_tables']['product_to_category'] = $this->model_catalog_parser->getFieldsFrom('product_to_category');
        $data['db_tables']['product_attribute'] = $this->model_catalog_parser->getAttributes();
        $data['db_tables']['product_filter'] = $this->model_catalog_parser->getFilters();

        $data['xlsx_fields'] = $xlsx['fields'];

        if (isset($this->request->post['xlsx'])) {
            $data['post_xlsx_data'] = $this->request->post['xlsx'];
            $this->session->data['post_xlsx_data'] = $this->request->post['xlsx'];
        } elseif (isset($this->session->data['post_xlsx_data'])) {
            $data['post_xlsx_data'] = $this->session->data['post_xlsx_data'];
        } else {
            $data['post_xlsx_data'] = [];
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/parser/parser_form', $data));
    }

    /**
     * =================================
     * Получает данные exel в виде array
     * =================================
     *
     * @param $file - путь к файлу
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    private function getExelData($file): array
    {
        $data = [];

        if (file_exists($file)) {
            $reader = new Xlsx();
            $speedsheet = $reader->load($file);

            $data = $speedsheet->getActiveSheet()->toArray(null, true, true, true);
        }

        if ($data[1]) {
            $data = [
                'fields' => array_shift($data),
                'data' => $data
            ];

            $data['fields'] = array_diff($data['fields'], [null]);

            foreach ($data['data'] as $key => $value) {
                if ($value[array_key_first($value)] === null) unset($data['data'][$key]);
            }
        }

        return $data;
    }

    private function getParserData($form_db, $form_xlsx)
    {
        foreach ($form_db as $db_table_name => $db_table_data) {
            foreach ($db_table_data as $db_filed => $db_value) {
                $form_db[$db_table_name][$db_filed] = !empty($form_db[$db_table_name][$db_filed]) ? $form_db[$db_table_name][$db_filed] : $form_xlsx[$db_table_name][$db_filed];
            }
        }

        return $form_db;
    }

    private function prepareData($form_data, $xlsx_item)
    {
        $result = [];

        foreach ($form_data as $table_name => $table_fields) {
            foreach ($table_fields as $table_field => $xlsx_key) {
                if (array_key_exists($xlsx_key, $xlsx_item)) {
                    $result[$table_name][$table_field] = $xlsx_item[$xlsx_key];
                    continue;
                }
                if (!empty($xlsx_key)) {
                    $result[$table_name][$table_field] = $xlsx_key;
                    continue;
                }
                $result[$table_name][$table_field] = null;
            }
        }

        return $result;
    }

    protected function validateForm($data, $key)
    {
        if (!$this->user->hasPermission('modify', 'catalog/parser')) {
            $this->error[] = ['warning' => $this->language->get('error_permission')];
            return false;
        }

        $error = [];

        if ((utf8_strlen($data['product_description']['name'] ?? '') < 1) || (utf8_strlen($data['product_description']['name'] ?? '') > 255)) {
            $error['name'] = $this->language->get('error_name');
        }

        if ((utf8_strlen($data['product_description']['meta_title'] ?? '') < 1) || (utf8_strlen($data['product_description']['meta_title'] ?? '') > 255)) {
            $error['meta_title'] = $this->language->get('error_meta_title');
        }

        if ((utf8_strlen($data['product']['model'] ?? '') < 1) || (utf8_strlen($data['product']['model'] ?? '') > 64)) {
            $error['model'] = $this->language->get('error_model');
        }

        if (!empty($error)) {
            $error['n'] = ++$key;
            $this->error[] = $error;
        }

        return empty($error);
    }

}