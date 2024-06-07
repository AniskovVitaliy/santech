<?php

class ModelCatalogParser extends Model
{
    public function getFieldsFrom($table_name)
    {
        $result = [];

        $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . DB_PREFIX . $table_name. "'";

        foreach ($this->db->query($query)->rows as $key => $value) {
            $result[] = $value['COLUMN_NAME'];
        }

        return $result;
    }

    public function getAttributes()
    {
        $query = "SELECT
                    " . DB_PREFIX . "attribute_description.attribute_id, 
                    " . DB_PREFIX . "attribute_description.name as attribute_name, 
                    " . DB_PREFIX . "attribute_group_description.name as attribute_group_name
                 FROM
                    " . DB_PREFIX . "attribute
                    INNER JOIN
                    " . DB_PREFIX . "attribute_description USING(attribute_id)
                    INNER JOIN 
                    " . DB_PREFIX . "attribute_group USING(attribute_group_id)
                    INNER JOIN
                    " . DB_PREFIX . "attribute_group_description USING(attribute_group_id)
                    WHERE " . DB_PREFIX . "attribute_description.language_id = " . 1 . " AND " . DB_PREFIX . "attribute_group_description.language_id = " . 1 . "";

        $result = $this->db->query($query);

        return $result->rows;
    }

    public function getFilters()
    {
        $query = "SELECT
                    " . DB_PREFIX . "filter_group_description.filter_group_id,
                    " . DB_PREFIX . "filter_group_description.name
                  FROM
                    " . DB_PREFIX . "filter_group_description
                    WHERE " . DB_PREFIX . "filter_group_description.language_id = " . 1 . "";

        $result = $this->db->query($query);

        return $result->rows;
    }

    public function addProduct($item)
    {
        $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE model = '" . $this->db->escape($item['product']['model']) . "'");

        if ($query->num_rows) return ['unique' => false, 'product_id' => $query->row['product_id']];

        $manufacturer = $this->db->query("SELECT * FROM " . DB_PREFIX . "manufacturer WHERE " . DB_PREFIX . "manufacturer.name = '" . $item['product']['manufacturer_id'] . "'");
        $manufacturer_id = $manufacturer->num_rows ? (int)$manufacturer->row['manufacturer_id'] : 0;

        $product_query = "INSERT INTO " . DB_PREFIX . "product 
                            SET model = '" . $this->db->escape($item['product']['model']) . "',
                                sku = '" . $this->db->escape($item['product']['sku']) . "', 
                                upc = '" . $this->db->escape($item['product']['upc']) . "', 
                                ean = '" . $this->db->escape($item['product']['ean']) . "', 
                                jan = '" . $this->db->escape($item['product']['jan']) . "', 
                                isbn = '" . $this->db->escape($item['product']['isbn']) . "', 
                                mpn = '" . $this->db->escape($item['product']['mpn']) . "', 
                                location = '" . $this->db->escape($item['product']['location']) . "',
                                quantity = '" . (isset($item['product']['quantity']) ? (int)$item['product']['quantity'] : 1) . "', 
                                minimum = '" . (isset($item['product']['minimum']) ? (int)$item['product']['minimum'] : 1) . "', 
                                subtract = '" . (isset($item['product']['subtract']) ? (int)$item['product']['subtract'] : 1) . "', 
                                stock_status_id = '" . (isset($item['product']['stock_status_id']) ? (int)$item['product']['stock_status_id'] : 7) . "', 
                                date_available = '" . $this->db->escape($item['product']['date_available'] ?? date('Y-m-d')) . "', 
                                manufacturer_id = '" . $manufacturer_id . "', 
                                shipping = '" . (isset($item['product']['shipping']) ? (int)$item['product']['shipping'] : 1) . "', 
                                price = '" . (isset($item['product']['price']) ? (float)$item['product']['price'] : 0) . "', 
                                points = '" . (isset($item['product']['points']) ? (int)$item['product']['points'] : 0) . "', 
                                weight = '" . (isset($item['product']['width']) ? (float)$item['product']['width'] : 0) . "', 
                                weight_class_id = '" . (isset($item['product']['weight_class_id']) ? (int)$item['product']['weight_class_id'] : 1) . "', 
                                length = '" . (isset($item['product']['length']) ? (float)$item['product']['length'] : 0) . "', 
                                width = '" . (isset($item['product']['width']) ? (float)$item['product']['width'] : 0) . "', 
                                height = '" . (isset($item['product']['height']) ? (float)$item['product']['height'] : 0) . "', 
                                length_class_id = '" . (isset($item['product']['length_class_id']) ? (int)$item['product']['length_class_id'] : 1) . "', 
                                status = '" . (isset($item['product']['status']) ? (int)$item['product']['status'] : 1) . "', 
                                tax_class_id = '" . (isset($item['product']['tax_class_id']) ? (int)$item['product']['tax_class_id'] : 0) . "', 
                                sort_order = '" . (isset($item['product']['sort_order']) ? (int)$item['product']['sort_order'] : 1) . "', 
                                date_added = NOW(), 
                                date_modified = NOW()";

        $this->db->query($product_query);

        $product_id = $this->db->getLastId();

        $languages = $this->db->query("SELECT language_id as id, name FROM " . DB_PREFIX . "language")->rows;

        foreach ($languages as $language) {

            if ($language['name'] === 'Russian') {

                $description = '<p>' . $item['product_description']['description'] . '</p>' ?? '';

                if ($item['product_attribute']) {
                    $product_attributes = array_filter($item['product_attribute']);

                    $product_attributes_query = "SELECT name, attribute_id FROM " . DB_PREFIX . "attribute_description WHERE attribute_id IN (" . implode(',', array_keys($product_attributes)) . ") AND language_id = 1";

                    $product_attributes_name = $this->db->query($product_attributes_query)->rows;

                    $description .= '<table class="table table-bordered"><tbody>';

                    foreach ($product_attributes_name as $row) {
                        $description .= '<tr>';
                        $description .= '<td>' . $row['name'] . '</td>';
                        $description .= '<td>' . $product_attributes[(int)$row['attribute_id']] . '</td>';
                        $description .= '</tr>';
                    }

                    $description .= '</tbody></table>';
                }

                $product_description_query = "INSERT INTO " . DB_PREFIX . "product_description 
                            SET product_id = '" . (int)$product_id . "', 
                                language_id = '" . (int)$language['id'] . "',
                                name = '" . $this->db->escape($item['product_description']['name']) . "', 
                                description = '" . $this->db->escape(htmlspecialchars($description)) . "', 
                                tag = '" . $this->db->escape(!empty($item['product_description']['tag']) ? $item['product_description']['tag'] . '' : '') . "', 
                                meta_title = '" . $this->db->escape($item['product_description']['meta_title'] . ' купить в Гомеле по улице Мазурова, 28В') . "', 
                                meta_description = '" . $this->db->escape(!empty($item['product_description']['meta_description']) ? $item['product_description']['meta_description'] . ' купить в Гомеле по улице Мазурова, 28В' : '') . "', 
                                meta_keyword = '" . $this->db->escape(!empty($item['product_description']['meta_keyword']) ? $item['product_description']['meta_keyword'] . ' купить в Гомеле по улице Мазурова, 28В' : '') . "'";

                $this->db->query($product_description_query);
                continue;
            }

            $description = !empty($item['product_description']['description']) ? '<p>' . $this->translit($item['product_description']['description']) . '</p>' : '';

            if ($item['product_attribute']) {
                $product_attributes = array_filter($item['product_attribute']);

                $product_attributes_query = "SELECT name, attribute_id FROM " . DB_PREFIX . "attribute_description WHERE attribute_id IN (" . implode(',', array_keys($product_attributes)) . ") AND language_id = 1";

                $product_attributes_name = $this->db->query($product_attributes_query)->rows;

                $description .= '<table class="table table-bordered"><tbody>';

                foreach ($product_attributes_name as $row) {
                    $description .= '<tr>';
                    $description .= '<td>' . $this->translit($row['name']) . '</td>';
                    $description .= '<td>' . $this->translit($product_attributes[(int)$row['attribute_id']]) . '</td>';
                    $description .= '</tr>';
                }

                $description .= '</tbody></table>';
            }

            $product_description_query = "INSERT INTO " . DB_PREFIX . "product_description 
                            SET product_id = '" . (int)$product_id . "', 
                                language_id = '" . (int)$language['id'] . "',
                                name = '" . $this->db->escape($this->translit($item['product_description']['name'])) . "', 
                                description = '" . $this->db->escape(htmlspecialchars($description)) . "', 
                                tag = '" . $this->db->escape(!empty($item['product_description']['tag']) ? $this->translit($item['product_description']['tag']) : '') . "', 
                                meta_title = '" . $this->db->escape($this->translit($item['product_description']['meta_title']) . ' buy in Gomel on Mazurova street, 28B') . "', 
                                meta_description = '" . $this->db->escape(!empty($item['product_description']['meta_description']) ? $this->translit($item['product_description']['meta_description']) : '') . " buy in Gomel on Mazurova street, 28B',
                                meta_keyword = '" . $this->db->escape(!empty($item['product_description']['meta_keyword']) ? $this->translit($item['product_description']['meta_keyword']) : '') . " buy in Gomel on Mazurova street, 28B'";

            $this->db->query($product_description_query);
        }

        if (isset($item['product']['image'])) {
            $images = explode(',', $item['product']['image']);
            $image = array_shift($images);

            if (!empty($image)) {
                $image = file_exists(DIR_IMAGE . 'catalog/demo/goods/' . trim($image)) ? trim($image) : '';

                $product_image_query = "UPDATE " . DB_PREFIX . "product
                                        SET image = 'catalog/demo/goods/" . $this->db->escape($image) . "'
                                        WHERE product_id = '" . (int)$product_id . "'";

                $this->db->query($product_image_query);
            }

            if (!empty($images)) {
                foreach ($images as $key => $img) {
                    $img = file_exists(DIR_IMAGE . 'catalog/demo/goods/' . trim($img)) ? trim($img) : '';

                    $products_image_query = "INSERT INTO " . DB_PREFIX . "product_image
                            SET product_id = '" . (int)$product_id . "',
                                image = 'catalog/demo/goods/" . $this->db->escape($img) . "',
                                sort_order = '" . (int)$key . "'";

                    $this->db->query($products_image_query);
                }
            }
        }

        if (isset($item['product_attribute'])) {
            foreach ($languages as $language) {
                if ($language['name'] === 'Russian') {
                    foreach ($item['product_attribute'] as $attribute_id => $attribute_value) {
                        if ($attribute_value) {
                            $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$attribute_id . "' AND language_id = '" . (int)$language['id'] . "'");
                            $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$attribute_id . "', language_id = '" . (int)$language['id'] . "', text = '" . $this->db->escape($attribute_value) . "'");
                        }
                    }
                    continue;
                }
                foreach ($item['product_attribute'] as $attribute_id => $attribute_value) {
                    if ($attribute_value) {
                        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$attribute_id . "' AND language_id = '" . (int)$language['id'] . "'");
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$attribute_id . "', language_id = '" . (int)$language['id'] . "', text = '" . $this->db->escape($this->translit($attribute_value)) . "'");
                    }
                }
            }
        }

        if ($item['product_filter']) {
            foreach ($item['product_filter'] as $filter_group_id => $filter_name) {

                if(!$filter_name) continue;

                $filter_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "filter_description WHERE " . DB_PREFIX . "filter_description.language_id = 1 AND " . DB_PREFIX . "filter_description.filter_group_id = " . $filter_group_id . " AND " . DB_PREFIX . "filter_description.name = '". trim($filter_name) ."'")->row;

                $product_filter_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_filter WHERE " . DB_PREFIX . "product_filter.product_id = " . $product_id . " AND " . DB_PREFIX . "product_filter.filter_id = " . $filter_query['filter_id']);

                if (!$product_filter_query->num_rows) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_filter 
                                        SET product_id = '" . (int)$product_id . "',
                                            filter_id = '" . (int)$filter_query['filter_id'] . "'");
                }

                if (isset($item['product_to_category']['category_id'])) {
                    $categories_id = explode(',', $item['product_to_category']['category_id']);

                    foreach ($categories_id as $category_id) {
                        $category_filter_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_filter WHERE " . DB_PREFIX . "category_filter.category_id = " . $category_id . " AND " . DB_PREFIX . "category_filter.filter_id = " . $filter_query['filter_id']);

                        if (!$category_filter_query->num_rows) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "category_filter 
                                            SET category_id = '" . (int)$category_id . "',
                                                filter_id = '" . (int)$filter_query['filter_id'] . "'");
                        }
                    }
                }

            }
        }

        if (isset($item['product_to_category']['category_id'])) {
            $categories_id = explode(',', $item['product_to_category']['category_id']);

            foreach ($categories_id as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category
                                    SET product_id = '" . (int)$product_id . "',
                                        category_id = '" . (int)trim($category_id) . "'");
            }
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . 0 . "'");

        $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . 0 . "', layout_id = '" . 0 . "'");

        $this->cache->delete('product');

        return ['unique' => true, 'product_id' => $product_id];
    }

    private function translit($value)
    {
        $converter = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];

        $value = mb_strtolower($value);
        $value = strtr($value, $converter);
        $value = mb_ereg_replace('[^-0-9a-z]', '_', $value);
        $value = mb_ereg_replace('[-]+', '_', $value);
        $value = trim($value, '_');

        return $value;
    }

}