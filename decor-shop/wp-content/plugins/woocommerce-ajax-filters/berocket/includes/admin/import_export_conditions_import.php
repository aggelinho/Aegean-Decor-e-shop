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
                    foreach($condition[$products_key] as $product_key => $product_slug) {
                        $condition[$products_key][$product_key] = BeRocket_import_export::slug_to_id($product_slug, 'post', array(
                            'post_type' => 'product',
                        ));
                    }
                    $condition[$products_key] = array_values(array_filter($condition[$products_key]));
                }
                break;
            case 'category':
            case 'woo_category':
                if( ! empty($condition['category']) && is_array($condition['category']) ) {
                    foreach($condition['category'] as $category_key => $category_slug) {
                        $condition['category'][$category_key] = BeRocket_import_export::slug_to_id($category_slug, 'taxonomy', array(
                            'taxonomy' => 'product_cat',
                        ));
                    }
                    $condition['category'] = array_values(array_filter($condition['category']));
                } elseif( ! empty($condition['category']) ) {
                    $condition['category'] = BeRocket_import_export::slug_to_id($condition['category'], 'taxonomy', array(
                        'taxonomy' => 'product_cat',
                    ));
                }
                break;
            case 'shippingclass':
                if( ! empty($condition['term']) ) {
                    $condition['term'] = BeRocket_import_export::slug_to_id($condition['term'], 'taxonomy', array(
                        'taxonomy' => 'product_shipping_class',
                    ));
                }
                break;
            case 'attribute':
            case 'woo_attribute':
                if( ! empty($condition['attribute']) && ! empty($condition['values']) && is_array($condition['values']) ) {
                    foreach($condition['values'] as $taxonomy => $term_slug) {
                        if( empty($term_slug) || ! taxonomy_exists($taxonomy) ) {
                            continue;
                        }
                        $condition['values'][$taxonomy] = BeRocket_import_export::slug_to_id($term_slug, 'taxonomy', array(
                            'taxonomy' => $taxonomy,
                        ));
                    }
                }
                break;
            case 'page_id':
                if( ! empty($condition['pages']) && is_array($condition['pages']) ) {
                    foreach($condition['pages'] as $page_key => $page_slug) {
                        if( ! is_array($page_slug) ) {
                            continue;
                        }
                        $condition['pages'][$page_key] = BeRocket_import_export::slug_to_id($page_slug, 'post', array(
                            'post_type' => 'page',
                        ));
                    }
                    $filtered_pages = array();
                    foreach($condition['pages'] as $page_id) {
                        if( $page_id !== false && $page_id !== null && $page_id !== '' ) {
                            $filtered_pages[] = $page_id;
                        }
                    }
                    $condition['pages'] = $filtered_pages;
                }
                break;
        }

        $options[$key][$group_id][$condition_id] = $condition;
    }
}
