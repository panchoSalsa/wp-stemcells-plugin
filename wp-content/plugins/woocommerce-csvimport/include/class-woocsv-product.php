<?php
if ( !defined ('ABSPATH') ) {
    exit;
} // Exit if accessed directly

// @since 3.0.5 changed $woocsv_import->header to $this->header for future scheduling

class woocsv_import_product
{
    // @since 3.0.2 skip products if flag is set to true during runtime
    private $logger;

    public $batch_code;

    public $skip = FALSE;

    public $new = TRUE;

    public $header = array ();

    public $tags = array ();

    public $categories = array ();

    public $images = array ();

    public $raw_data = array ();

    public $shipping_class = '';

    public $featured_image = '';

    public $product_gallery = '';

    public $product_type = 'simple';


    /* since 3.0.6
        no more use of the global $woocsv_import
    */
    public $log = array ();

    //body
    public $body = array (
        'ID'             => '',
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'post_title'     => '',
        'post_name'      => '',
        'post_date'      => '',
        'post_date_gmt'  => '',
        'post_content'   => '',
        'post_excerpt'   => '',
        'post_parent'    => 0,
        'post_password'  => '',
        'comment_status' => 'open',
        'ping_status'    => 'open',
        'menu_order'     => 0,
        'post_author'    => '',
    );

    public $meta = array (
        '_sku'                         => '',
        '_downloadable'                => 'no',
        '_virtual'                     => 'no',
        '_price'                       => '',
        '_visibility'                  => 'visible',
        '_stock'                       => '',
        '_stock_status'                => 'instock',
        '_backorders'                  => 'no',
        '_manage_stock'                => 'yes',
        '_sale_price'                  => '',
        '_regular_price'               => '',
        '_weight'                      => '',
        '_length'                      => '',
        '_width'                       => '',
        '_height'                      => '',
        '_tax_status'                  => 'taxable',
        '_tax_class'                   => '',
        '_upsell_ids'                  => array (),
        '_crosssell_ids'               => array (),
        '_sale_price_dates_from'       => '',
        '_sale_price_dates_to'         => '',
        '_min_variation_price'         => '',
        '_max_variation_price'         => '',
        '_min_variation_regular_price' => '',
        '_max_variation_regular_price' => '',
        '_min_variation_sale_price'    => '',
        '_max_variation_sale_price'    => '',
        '_featured'                    => 'no',
        '_file_path'                   => '',
        '_download_limit'              => '',
        '_download_expiry'             => '',
        '_product_url'                 => '',
        '_button_text'                 => '',
        //		'total_sales'=>0,
    );

    public function __construct ( Allaerd\LogToFile $logger )
    {
        $this->logger = $logger;
    }

    public function parse_data ()
    {
        do_action ('woocsv_before_parse_data');

        $this->hasMandetoryFields ();

        $this->checkPostStatus ();

        $this->checkPingStatus ();

        $this->checkPostTitle ();

        $this->checkMenuOrder ();

        $this->chekcStockStatus ();

        $this->checkVisibility ();

        $this->checkBackorders ();

        $this->checkFeatured ();

        $this->checkManageStock ();

        $this->handlePrices ();

        //add action after parsing all data
        do_action ('woocsv_after_parse_data');
    }

    public function handlePrices ()
    {
        global $woocsv_import;


        //handle prices
        if ( $woocsv_import->get_merge_products () == 1 ) {
            $regular_price = (in_array ('regular_price', $this->header) && strlen ($this->meta[ '_regular_price' ]) > 0) ? $this->meta[ '_regular_price' ] : $this->meta[ '_regular_price' ];
            $sale_price = (in_array ('sale_price', $this->header) && strlen ($this->meta[ '_sale_price' ]) > 0) ? $this->meta[ '_sale_price' ] : $this->meta[ '_sale_price' ];
            $price = (in_array ('price', $this->header) && strlen ($this->meta[ '_price' ]) > 0) ? $this->meta[ '_price' ] : $this->meta[ '_price' ];
        } else {
            $regular_price = (in_array ('regular_price', $this->header) && strlen ($this->meta[ '_regular_price' ]) > 0) ? $this->meta[ '_regular_price' ] : '';
            $sale_price = (in_array ('sale_price', $this->header) && strlen ($this->meta[ '_sale_price' ]) > 0) ? $this->meta[ '_sale_price' ] : '';
            $price = (in_array ('price', $this->header) && strlen ($this->meta[ '_price' ]) > 0) ? $this->meta[ '_price' ] : '';
        }

        $regular_price = $this->normalizePrice ($regular_price);
        $sale_price = $this->normalizePrice ($sale_price);
        $price = $this->normalizePrice ($price);

        //product on sale
        if ( $sale_price > 0 && $sale_price < $regular_price ) {
            $this->logger->log (__ ('Product is on sale', 'woocommerce-csvimport'));
            $price = $sale_price;
        } else {
            //the product is not on sale
            $price = $regular_price;
            $sale_price = '';
        }

        //set prices
        $this->meta[ '_regular_price' ] = $regular_price;
        $this->meta[ '_sale_price' ] = $sale_price;
        $this->meta[ '_price' ] = $price;
    }

    public function merge_product ( $id )
    {
        //get post data and store it
        $post = get_post ($id, 'ARRAY_A');
        $this->body = $post;

        //get meta data and store it
        $post_meta = get_metadata ('post', $id, '', TRUE);
        foreach ( $post_meta as $key => $value ) {
            $this->meta[ $key ] = maybe_unserialize ($value[ 0 ]);
        }

        //get product_tpe
        $product_types = wp_get_object_terms ($this->body[ 'ID' ], 'product_type');

        if ( !is_wp_error ($product_types) ) {
            foreach ( $product_types as $product_type ) {
                $this->product_type = $product_type->name;
            }
        }

    }

    public function get_product_by_id ( $sku = NULL )
    {
        global $wpdb;
        $product_id = NULL;

        /* ! @since 3.0.6  use new function wc_get_product_id_by_sku to get SKU */
        if ( function_exists ('wc_get_product_id_by_sku') ) {
            $product_id = wc_get_product_id_by_sku ($sku);
        } else {
            $product_id = $wpdb->get_var ($wpdb->prepare ("SELECT max(post_id) FROM $wpdb->postmeta a, $wpdb->posts b
				WHERE a.post_id= b.id and meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));
        }

        if ( $product_id ) {
            $product[ 'ID' ] = $product_id;
        }

        return $product_id = apply_filters ('woocsv_get_product_id', $product_id, $sku);
    }

    public function save ()
    {

        // @since 3.0.2 if skip is true, skip the product during import
        if ( $this->skip ) {
            return FALSE;
        }

        // ! @since 3.0.6
        // do it all in one update
        //save tags before
        if ( $this->tags ) {
            $this->body[ 'tax_input' ] = array ( 'product_tag' => explode ('|', $this->tags) );
        }

        //save the post
        $post_id = wp_insert_post ($this->body, TRUE);

        if ( is_wp_error ($post_id) ) {
            //@todo add object(WP_Error)#1300 (2) { ["errors"]=> array(1) { ["invalid_date"]=> array(1) { [0]=> string(37) "Whoops, the provided date is invalid."  to logging

            $this->logger->log (__ ('Product could not be saved and skipped', 'woocommerce-csvimport'));

            return FALSE;

        } else {

            $this->logger->log (sprintf (__ ('Product saved with ID: %s', 'woocommerce-csvimport'), $post_id));
            $this->body[ 'ID' ] = $post_id;

        }

        do_action ('woocsv_product_after_body_save', $post_id, $this);

        //save the product type
        wp_set_object_terms ($post_id, $this->product_type, 'product_type', FALSE);

        do_action ('woocsv_product_before_meta_save', $post_id);

        //save the meta
        foreach ( $this->meta as $key => $value ) {
            update_post_meta ($post_id, $key, $value);
        }

        do_action ('woocsv_product_before_categorie_save');

        //save categories
        if ( !empty($this->categories) ) {
            $this->save_categories ($post_id);
        }

        do_action ('woocsv_product_before_images_save');

        if ( !empty($this->featured_image) ) {
            $this->save_featured_image ();
        } else {
            $this->logger->log (__ ('No featured image available ', 'woocommerce-csvimport'));
        }

        //save the product gallery
        if ( !empty($this->product_gallery) ) {
            $this->save_product_gallery ();
        }

        do_action ('woocsv_product_before_shipping_save');

        // save shipping class
        if ( $this->shipping_class ) {
            $this->save_shipping_class ();
        }

        do_action ('woocsv_after_save', $this);

        //clear transients
        if ( function_exists ('wc_delete_product_transients') ) {
            wc_delete_product_transients ($post_id);
        }

        do_action ('woocsv_product_after_save');

        //clear transients and return the ID
        wc_delete_product_transients ($post_id);
        return $post_id;
    }

    public function save_tags ( $post_id )
    {
        global $woocsv_import;
        //2.1.1 If merging do not delete else clear currrent tag
        if ( !$woocsv_import->get_merge_products () ) {
            wp_set_object_terms ($this->body[ 'ID' ], NULL, 'product_tag');
        }

        //handle tags
        foreach ( $this->tags as $tags ) {
            $tags = explode ('|', $tags);
            wp_set_object_terms ($post_id, $tags, 'product_tag', TRUE);
        }
    }

    public function save_shipping_class ()
    {
        global $woocsv_import;

        //2.2.2 If merging do not delete else clear currrent tag
        if ( !$woocsv_import->get_merge_products () ) {
            wp_set_object_terms ($this->body[ 'ID' ], NULL, 'product_shipping_class');
        }

        $term = term_exists ($this->shipping_class, 'product_shipping_class');

        // @since  2.2.2 beter handling for shipping class
        if ( !is_array ($term) ) {
            $term = wp_insert_term ($this->shipping_class, 'product_shipping_class');
        }

        if ( !is_wp_error ($term) ) {
            wp_set_object_terms ($this->body[ 'ID' ], array ( (int)$term[ 'term_id' ] ), 'product_shipping_class');
        }
    }

    public function save_categories ()
    {
        global $woocsv_import;

        //check out http://wordpress.stackexchange.com/questions/24498/wp-insert-term-parent-child-problem
        delete_option ("product_cat_children");

        //clear currrent
        //2.1.1 If merging do not delete else clear currrent category
        if ( !$woocsv_import->get_merge_products () ) {
            wp_set_object_terms ($this->body[ 'ID' ], NULL, 'product_cat');
        }

        foreach ( $this->categories as $category ) {
            $cats = explode ('|', $category);
            foreach ( $cats as $cat ) {
                $cat_taxs = explode ('->', $cat);

                $parent = 0;

                foreach ( $cat_taxs as $cat_tax ) {

                    $new_cat = term_exists ($cat_tax, 'product_cat', $parent);
                    if ( !is_array ($new_cat) ) {
                        $new_cat = wp_insert_term ($cat_tax, 'product_cat', array ( 'slug' => $cat_tax, 'parent' => $parent ));
                    }
                    if ( !is_wp_error ($new_cat) ) {
                        $parent = $new_cat[ 'term_id' ];
                    }

                    if ( !is_wp_error ($new_cat) && $woocsv_import->get_add_to_categories () == 1 ) {
                        wp_set_object_terms ($this->body[ 'ID' ], (int)$new_cat[ 'term_id' ], 'product_cat', TRUE);
                    }
                }

                if ( !is_wp_error ($new_cat) && $woocsv_import->get_add_to_categories () == 0 ) {
                    wp_set_object_terms ($this->body[ 'ID' ], (int)$new_cat[ 'term_id' ], 'product_cat', TRUE);
                }
            }
        }
    }

    public function save_featured_image ()
    {
        $imageID = FALSE;

        if ( $this->is_valid_url ($this->featured_image) ) {
            $this->logger->log (__ ('featured image is imported using the URL', 'woocommerce-csvimport'));
            $imageID = $this->save_image_with_url ($this->featured_image);
        } else {
            $this->logger->log (__ ('featured image is imported using the filename', 'woocommerce-csvimport'));
            $imageID = $this->save_image_with_name ($this->featured_image);
        }

        // @ since XXX check if the url is valid
        if ( !is_wp_error ($imageID) ) {
            set_post_thumbnail ($this->body[ 'ID' ], $imageID);
            $this->logger->log (__ ('The image is attached as featured image', 'woocommerce-csvimport'));
        } else {
            $this->logger->log (__ ('The image could not be attached as featured image', 'woocommerce-csvimport'));
        }


    }

    public function save_product_gallery ()
    {

        $images = explode ('|', $this->product_gallery);
        $gallery = FALSE;
        foreach ( $images as $image ) {
            if ( $this->is_valid_url ($image) ) {
                $imageID = $this->save_image_with_url ($image);
            } else {
                $imageID = $this->save_image_with_name ($image);
            }

            if ( $imageID ) {
                $gallery[] = $imageID;
            }
        }

        if ( $gallery ) {
            $meta_value = implode (',', $gallery);
            update_post_meta ($this->body[ 'ID' ], '_product_image_gallery', $meta_value);
        }

    }

    //@since 3.0.5 use WP functions to upload and handle images with url's
    function save_image_with_url ( $url )
    {
        global $woocsv_import;

        $tmp = download_url ($url, 10);

        if ( is_wp_error ($tmp) ) {
            //something went wrong during download
            //@unlink( $file_array[ 'tmp_name' ] );
            $this->logger->log (sprintf (__ ('Image with url: %s could not be downloaded', 'woocommerce-csvimport'), $url));

            return FALSE;
        }

        $post_id = $this->body[ 'ID' ];
        $file_array = array ();

        @preg_match ('/[^\?]+\.(jpg|jpe|jpeg|gif|png|pdf)/i', $url, $matches);
        if ( !$matches ) {
            $file_array[ 'tmp_name' ] = $tmp;
            $file_array[ 'name' ] = sanitize_file_name ($url);
            $desc = '';
        } else {
            $file_array[ 'name' ] = basename ($matches[ 0 ]);
            $file_array[ 'tmp_name' ] = $tmp;
            $desc = $file_array[ 'name' ];
        }

        // do the validation and storage stuff
        $id = media_handle_sideload ($file_array, $post_id, $desc);

        // If error storing permanently, unlink
        if ( is_wp_error ($id) ) {
            @unlink ($file_array[ 'tmp_name' ]);
            $this->logger->log (sprintf (__ ('Image with url: %s could not be linked to the post', 'woocommerce-csvimport'), $url));
            return $id;
        }

        $this->logger->log (sprintf (__ ('Image with url: %s uploaded', 'woocommerce-csvimport'), $url));

        return $id;
    }

    public function save_image_with_name ( $image )
    {
        /* use  get_posts to retrieve image instead of query direct! */

        //set up the args
        $args = array (
            'numberposts' => 1,
            'orderby'     => 'post_date',
            'order'       => 'DESC',
            'post_type'   => 'attachment',
            //'post_mime_type'=> 'image',
            'post_status' => 'any',
            'meta_query'  => array (
                'relation' => 'OR',
                array (
                    'key'     => '_wp_attached_file',
                    'value'   => '/' . sanitize_file_name( $image ),
                    'compare' => 'LIKE',
                ),
                array (
                    'key'     => '_wp_attached_file',
                    'value'   => sanitize_file_name( $image ),
                )
            ),
        );
        //get the images
        $images = get_posts ($args);

        if ( !empty($images) ) {
            //we found a match, return it!
            return (int)$images[ 0 ]->ID;
        } else {
            //no image found with the same name, return false
            return FALSE;
        }

    }

    public function fill_in_data ()
    {
        global $woocsv_import;
        do_action ('woocsv_product_before_fill_in_data');

        $id = FALSE;

        //check if the product already exists by checking it's ID
        if ( in_array ('ID', $this->header) ) {
            $tempID = $this->raw_data[ array_search ('ID', $this->header) ];
            if ( $tempID ) {

                //use get_post instead of get_posts
                $test = new WC_Product($tempID);

                if ( $test->post ) {
                    $this->logger->log (sprintf (__ ('Product found (ID), ID is: %s', 'woocommerce-csvimport'), $tempID));
                    $this->new = FALSE;
                    // @ since 3.0.5 add ID else merging will not work using ID's
                    $id = $tempID;
                } else {
                    /* set the ID to null */
                    $this->raw_data[ array_search ('ID', $this->header) ] = '';
                    $this->body[ 'ID' ] = '';
                    $this->logger->log (sprintf (__ ('ID : %s not found!', 'woocommerce-csvimport'), $tempID));
                }
            }

        }

        //check if the product already exists by checking it's sku
        if ( empty($id) && in_array ('sku', $this->header) && $woocsv_import->get_match_by () == 'sku' ) {
            $sku = $this->raw_data[ array_search ('sku', $this->header) ];

            if ( !empty($sku) ) {
                $id = $this->get_product_by_id ($sku);
                if ( !empty($id) ) {
                    $this->new = FALSE;
                    $this->logger->log (sprintf (__ ('Product found (SKU), ID is: %s', 'woocommerce-csvimport'), $id));
                } else {
                    $this->logger->log (__ ('New product', 'woocommerce-csvimport'));
                }
            }
        }

        //check if the product already exists by checking it's post title
        if ( empty($id) && in_array ('post_title', $this->header) && $woocsv_import->get_match_by () == 'title' ) {
            $post_title = $this->raw_data[ array_search ('post_title', $this->header) ];

            if ( $post_title ) {
                $testID = get_page_by_title ($post_title, ARRAY_A, 'product');
                if ( $testID ) {
                    $this->logger->log (__ ('Product found by title', 'woocommerce-csvimport'));
                    $id = $testID[ 'ID' ];
                    $this->new = FALSE;
                } else {
                    $this->logger->log (__ ('Product not found', 'woocommerce-csvimport'));
                }
            }
        }

        //check for if we need to merge the product

        if ( $id && $woocsv_import->get_merge_products () == 1 ) {
            $this->merge_product ($id);
        }
        //fill in the product body
        foreach ( $this->body as $key => $value ) {
            if ( in_array ($key, $this->header) ) {
                $key_body = array_search ($key, $this->header);
                if ( isset ($this->raw_data[ $key_body ]) ) {
                    $this->body[ $key ] = $this->raw_data[ $key_body ];
                }
            }
        }

        // get the author
        if ( isset($this->body[ 'post_author' ]) && in_array ('post_author', $this->header) ) {

            $user = get_user_by (($woocsv_import->get_match_author_by ()) ? $woocsv_import->get_match_author_by () : 'login', $this->body[ 'post_author' ]);
            if ( $user ) {
                $this->body[ 'post_author' ] = $user->ID;
                $this->logger->log (__ ('user found', 'woocommerce-csvimport'));
            } else {
                $this->body[ 'post_author' ] = '';
                $this->logger->log (__ ('user not found', 'woocommerce-csvimport'));
            }
        }

        //fill in the ID if the product already exists
        if ( $id ) {
            $this->body[ 'ID' ] = $id;
        }

        //fill in the meta data
        // @ since 3.0.5
        // trim meta values to loose spaces
        foreach ( $this->meta as $key => $value ) {
            if ( in_array (substr ($key, 1), $this->header) ) {
                $this->meta[ $key ] = trim ($this->raw_data[ array_search (substr ($key, 1), $this->header) ]);
            }
        }

        // @ since 3.0.5
        // if the product is new add total_sales to show it in the front end
        // some themes needed total_sales for popularity sorting
        if ( !empty ($this->body[ 'ID' ]) ) {
            $this->meta[ 'total_sales' ] = 0;
        }

        //check if there are tags
        if ( in_array ('tags', $this->header) ) {
            $key = array_search ('tags', $this->header);
            $this->tags = $this->raw_data[ $key ];
        }

        //check if there is a shipping
        if ( in_array ('shipping_class', $this->header) ) {
            $key = array_search ('shipping_class', $this->header);
            $this->shipping_class = trim ($this->raw_data[ $key ]);
        }

        //check if there are categories
        if ( in_array ('category', $this->header) ) {
            foreach ( $this->header as $key => $value ) {
                if ( $value == 'category' ) {
                    $this->categories[] = $this->raw_data[ $key ];
                }
            }
        }

        /* change_stock */
        if ( in_array ('change_stock', $this->header) ) {
            $key = array_search ('change_stock', $this->header);
            $change_stock = $this->raw_data[ $key ];

            //get the stock
            $stock = get_post_meta ($this->body[ 'ID' ], '_stock', TRUE);

            //if the stock is empty set it to 0
            if ( !$stock ) {
                $stock = 0;
            }

            //calculate the new stock level
            $new_stock = $stock + $change_stock;

            //set new stock in the meta
            $this->meta[ '_stock' ] = $new_stock;

            //set log
            $this->logger->log (sprintf (__ ('Change stock modus: stock changed from %s to %s', 'woocommerce-csvimport'), $stock, $new_stock));
        }

        //check if there is a featured image
        if ( in_array ('featured_image', $this->header) ) {
            $key = array_search ('featured_image', $this->header);
            $this->featured_image = $this->raw_data[ $key ];
        }

        //check if there is a product gallery
        if ( in_array ('product_gallery', $this->header) ) {
            $key = array_search ('product_gallery', $this->header);
            $this->product_gallery = $this->raw_data[ $key ];
        }

        $this->logger->log (__ ($this->featured_image, 'woocommerce-csvimport'));

        $this->handle_post_parent ();

        do_action ('woocsv_product_after_fill_in_data');

    }

    public function handle_post_parent ()
    {
        global $wpdb, $woocsv_product;

        $key = array_search ('post_parent', $woocsv_product->header);
        if ( $key !== FALSE && !empty ($woocsv_product->raw_data[ $key ]) ) {

            //get the parent_id based on the SKU
            $parent_id = $wpdb->get_var ($wpdb->prepare ("SELECT max(post_id)
					FROM $wpdb->postmeta a, $wpdb->posts b
					WHERE a.post_id= b.id and meta_key='_sku' AND meta_value='%s' LIMIT 1", $woocsv_product->raw_data[ $key ]));

            if ( $parent_id ) {
                $woocsv_product->body[ 'post_parent' ] = (int)$parent_id;
                $woocsv_product->log[] = 'post parent found with ID: ' . $parent_id;
            } else {
                $woocsv_product->log[] = 'No parent found for post_parent: ' . $woocsv_product->raw_data[ $key ];
            }
        }
    }

    public function normalizePrice ( $num )
    {

        if ( !$num ) {
            return FALSE;
        }

        $dotPos = strrpos ($num, '.');
        $commaPos = strrpos ($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : ((($commaPos > $dotPos) && $commaPos) ? $commaPos : FALSE);

        if ( !$sep ) {
            return floatval (preg_replace ("/[^0-9]/", "", $num));
        }

        return floatval (preg_replace ("/[^0-9]/", "", substr ($num, 0, $sep)) . '.' . preg_replace ("/[^0-9]/", "", substr ($num, $sep + 1, strlen ($num))));
    }

    // helpers
    public function is_valid_url ( $url )
    {
        $file_headers = @get_headers($url);
        if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return false;
        }
        else {
            return true;
        }
    }

    public function hasMandetoryFields ()
    {
        if ( empty($this->body[ 'post_title' ]) && empty($this->body[ 'post_name' ]) && empty($this->body[ 'post_content' ]) && $this->body[ 'post_type' ] == 'product' ) {
            $this->logger->log (__ ('No title, slug or content. Filled in dummy content', 'woocommerce-csvimport'));
            $this->body[ 'post_content' ] = ' ';
        }
    }

    public function checkPostStatus ()
    {
        $post_status = array ( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' );
        if ( !in_array ($this->body[ 'post_status' ], $post_status) ) {
            $this->logger->log (__ ('post status changed to publish', 'woocommerce-csvimport'));
            $this->body[ 'post_status' ] = 'publish';
        }
    }

    public function checkPingStatus ()
    {
        if ( !in_array ($this->body[ 'ping_status' ], array ( 'open', 'closed' )) ) {
            $this->logger->log (__ ('ping status changed to ping', 'woocommerce-csvimport'));
            $this->body[ 'ping_status' ] = 'open';
        }
    }

    public function checkPostTitle ()
    {
        if ( empty($this->body[ 'post_title' ]) && $this->body[ 'post_type' ] == 'product' ) {
            $this->logger->log (__ ('title is empty status changed to draft', 'woocommerce-csvimport'));
            $this->body[ 'post_status' ] = 'draft';
        }
    }

    public function checkMenuOrder ()
    {
        if ( !is_numeric ($this->body[ 'menu_order' ]) ) {
            $this->logger->log (__ ('menu order changed to 0', 'woocommerce-csvimport'));
            $this->body[ 'menu_order' ] = 0;
        }
    }

    public function chekcStockStatus ()
    {
        if ( in_array ('stock_status', $this->header) && !in_array ($this->meta[ '_stock_status' ], array ( 'instock', 'outofstock' )) ) {
            $this->logger->log (__ ('stock status changed to instock', 'woocommerce-csvimport'));
            $this->meta[ '_stock_status' ] = 'instock';
        }
    }

    public function checkVisibility ()
    {
        if ( in_array ('visibility', $this->header) && !in_array ($this->meta[ '_visibility' ], array ( 'visible', 'catalog', 'search', 'hidden' )) ) {
            $this->logger->log (__ ('visibility changed to visible', 'woocommerce-csvimport'));
            $this->meta[ '_visibility' ] = 'visible';
        }
    }

    public function checkBackorders ()
    {
        if ( in_array ('backorders', $this->header) && !in_array ($this->meta[ '_backorders' ], array ( 'yes', 'no', 'notify' )) ) {
            $this->logger->log (__ ('backorders changed to no', 'woocommerce-csvimport'));
            $this->meta[ '_backorders' ] = 'no';
        }
    }

    public function checkFeatured ()
    {
        if ( in_array ('featured', $this->header) && !in_array ($this->meta[ '_featured' ], array ( 'yes', 'no' )) ) {
            $this->logger->log (__ ('featured changed to no', 'woocommerce-csvimport'));
            $this->meta[ '_featured' ] = 'no';
        }
    }

    public function checkManageStock ()
    {
        if ( in_array ('manage_stock', $this->header) && !in_array ($this->meta[ '_manage_stock' ], array ( 'yes', 'no' )) ) {
            $this->logger->log (__ ('manage_stock changed to no', 'woocommerce-csvimport'));
            $this->meta[ '_manage_stock' ] = 'no';
        }
    }

}
