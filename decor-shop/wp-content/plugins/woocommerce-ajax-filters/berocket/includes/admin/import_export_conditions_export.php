<?php
if( ! is_array($options[$key]) ) {
    return;
}

foreach($options[$key] as $group_id => $conditions) {
    if( ! is_array($conditions) ) {
        continue;
    }

    foreach($conditions as $condition_id => $condition) {
        if( ! is_array($condition) || empty($condition['type']) ) {
            continue;
        }

        switch($condition['type']) {
            case 'product':
                foreach(array('product', 'additional_product') as $products_key) {
                    if( empty($condition[$products_key]) || ! is_array($condition[$products_key]) ) {
                        continue;
                    }
                    foreach($condition[$products_key] as $product_key => $product_id) {
                        $condition[$products_key][$product_key] = BeRocket_import_export::id_to_slug($product_id, 'post', array(
                            'post_type' => 'product',
                        ));
                    }
                }
                break;
            case 'category':
            case 'woo_category':
                if( ! empty($condition['category']) && is_array($condition['category']) ) {
                    foreach($condition['category'] as $category_key => $category_id) {
                        $condition['category'][$category_key] = BeRocket_import_export::id_to_slug($category_id, 'taxonomy', array(
                            'taxonomy' => 'product_cat',
                        ));
                    }
                } elseif( isset($condition['category']) && $condition['category'] !== '' ) {
                    $condition['category'] = BeRocket_import_export::id_to_slug($condition['category'], 'taxonomy', array(
                        'taxonomy' => 'product_cat',
                    ));
                }
                break;
            case 'shippingclass':
                if( isset($condition['term']) && $condition['term'] !== '' ) {
                    $condition['term'] = BeRocket_import_export::id_to_slug($condition['term'], 'taxonomy', array(
                        'taxonomy' => 'product_shipping_class',
                    ));
                }
                break;
            case 'attribute':
            case 'woo_attribute':
                if( ! empty($condition['attribute']) && ! empty($condition['values']) && is_array($condition['values']) ) {
                    foreach($condition['values'] as $taxonomy => $term_id) {
                        if( $term_id === '' || ! taxonomy_exists($taxonomy) ) {
                            continue;
                        }
                        $condition['values'][$taxonomy] = BeRocket_import_export::id_to_slug($term_id, 'taxonomy', array(
                            'taxonomy' => $taxonomy,
                        ));
                    }
                }
                break;
            case 'page_id':
                if( ! empty($condition['pages']) && is_array($condition['pages']) ) {
                    foreach($condition['pages'] as $page_key => $page_id) {
                        if( ! is_numeric($page_id) ) {
                            continue;
                        }
                        $condition['pages'][$page_key] = BeRocket_import_export::id_to_slug($page_id, 'post', array(
                            'post_type' => 'page',
                        ));
                    }
                }
                break;
        }

        $options[$key][$group_id][$condition_id] = $condition;
    }
}
