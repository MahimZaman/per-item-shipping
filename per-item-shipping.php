<?php

/**
 * Plugin Name: Per Item Shipping for WooCommerce
 * Description: First product has a base shipping cost, each additional product adds extra cost.
 * Author: Mahim Zaman (Condixia)
 * Author URI: https://condixia.com
 * Plugin URI: https://github.com/MahimZaman/per-item-shipping
 * Version: 1.0.0 
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_shipping_init', function () {

    if (! class_exists('WC_Shipping_Method')) {
        return;
    }

    class WC_Shipping_Per_Item extends WC_Shipping_Method
    {

        public function __construct($instance_id = 0)
        {

            $this->id           = 'per_item_shipping';
            $this->instance_id  = absint($instance_id);
            $this->method_title = 'Per Item Shipping';
            $this->method_description = 'First product has a base shipping cost. Each additional product adds extra cost.';

            $this->supports = [
                'shipping-zones',
                'instance-settings',
            ];

            $this->init();
        }

        public function init()
        {
            $this->instance_form_fields = [

                'step_notice' => [
                    'title' => 'Step 2: Configure Shipping Pricing',
                    'type'  => 'title',
                    'description' => 'Enter the shipping title and pricing below.',
                ],

                'title' => [
                    'title'   => 'Shipping Title',
                    'type'    => 'text',
                    'default' => 'Per Item Shipping',
                ],

                'description' => [
                    'title'   => 'Description',
                    'type'    => 'textarea',
                    'default' => 'Delivery charges based on number of items',
                ],

                'base_cost' => [
                    'title'   => 'First Product Price',
                    'type'    => 'price',
                    'default' => '0',
                ],

                'additional_cost' => [
                    'title'   => 'Additional Product Price',
                    'type'    => 'price',
                    'default' => '0',
                ],
            ];


            $this->title = $this->get_option('title');

            add_action(
                'woocommerce_update_options_shipping_' . $this->id,
                [$this, 'process_admin_options']
            );
        }

        public function calculate_shipping($package = [])
        {

            $qty = 0;

            foreach ($package['contents'] as $item) {
                $qty += $item['quantity'];
            }

            if ($qty < 1) {
                return;
            }

            $base       = (float) $this->get_option('base_cost', 0);
            $additional = (float) $this->get_option('additional_cost', 0);

            $cost = $base;

            if ($qty > 1) {
                $cost += ($qty - 1) * $additional;
            }

            $this->add_rate([
                'id'          => $this->id . ':' . $this->instance_id,
                'label'       => $this->title,
                'cost'        => $cost,
                'description' => $this->get_option('description'),
            ]);
        }
    }
});

add_filter('woocommerce_shipping_methods', function ($methods) {
    $methods['per_item_shipping'] = 'WC_Shipping_Per_Item';
    return $methods;
});

add_action('admin_footer', function () {
    if (
        isset($_GET['page'], $_GET['tab'], $_GET['section']) &&
        $_GET['page'] === 'wc-settings' &&
        $_GET['tab'] === 'shipping'
    ) : ?>
        <script>
            jQuery(function($) {
                $('a.edit').first()[0]?.click();
            });
        </script>
<?php endif;
});
