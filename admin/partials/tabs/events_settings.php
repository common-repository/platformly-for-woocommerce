<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

    $abandonedCartTime            = !empty($options['abandoned_cart']['time']) ? $options['abandoned_cart']['time'] : Platformly_WooCommerce_Abandoned_Cart::$timeDefault;
    $abandonedCartRemoveAfterDays = !empty($options['abandoned_cart']['remove_after_days']) ? $options['abandoned_cart']['remove_after_days'] : Platformly_WooCommerce_Abandoned_Cart::$removeAfterDays;
?>
<input type="hidden" id="platfromlyWcEventViewProduct" value="<?php echo isset($options['events']['view_product']) ? $options['events']['view_product'] : '' ?>"/>
<input type="hidden" id="platfromlyWcEventAddToCart" value="<?php echo isset($options['events']['add_to_cart']) ? $options['events']['add_to_cart'] : '' ?>"/>
<input type="hidden" id="platfromlyWcEventViewCart" value="<?php echo isset($options['events']['view_cart']) ? $options['events']['view_cart'] : '' ?>"/>
<input type="hidden" id="platfromlyWcEventCheckout" value="<?php echo isset($options['events']['checkout']) ? $options['events']['checkout'] : '' ?>"/>
<input type="hidden" id="platfromlyWcEventPlaceOrder" value="<?php echo isset($options['events']['place_order']) ? $options['events']['place_order'] : '' ?>"/>
<input type="hidden" id="platfromlyWcEventAbandonedCart" value="<?php echo isset($options['events']['abandoned_cart']) ? $options['events']['abandoned_cart'] : '' ?>"/>
<input type="hidden" id="platfromlyWcEventAbandonedCartRecovered" value="<?php echo isset($options['events']['abandoned_cart_recovered']) ? $options['events']['abandoned_cart_recovered'] : '' ?>"/>
<div class="platformly-wc-events-settings">
    <h2></h2>
    <div>
        <label>View product</label>
        <select data-placeholder="Select a Event" data-event="ViewProduct" data-allow-clear="true" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-events-list" name="<?php echo $this->plugin_name; ?>[events][view_product]"></select>
    </div>
    <br/>
    <div>
        <label>Add to cart</label>
        <select data-placeholder="Select a Event" data-event="AddToCart" data-allow-clear="true" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-events-list" name="<?php echo $this->plugin_name; ?>[events][add_to_cart]"></select>
    </div>
    <br/>
    <div>
        <label>View cart</label>
        <select data-placeholder="Select a Event" data-event="ViewCart" data-allow-clear="true" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-events-list" name="<?php echo $this->plugin_name; ?>[events][view_cart]"></select>
    </div>
    <br/>
    <div>
        <label>Checkout</label>
        <select data-placeholder="Select a Event" data-event="Checkout" data-allow-clear="true" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-events-list" name="<?php echo $this->plugin_name; ?>[events][checkout]"></select>
    </div>
    <br/>
    <div>
        <label>Place order</label>
        <select data-placeholder="Select a Event" data-event="PlaceOrder" data-allow-clear="true" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-events-list" name="<?php echo $this->plugin_name; ?>[events][place_order]"></select>
    </div>
    <br/>
    <div>
        <label>Abandoned Cart</label>
        <select data-placeholder="Select a Event" data-event="AbandonedCart" data-allow-clear="true" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-events-list" name="<?php echo $this->plugin_name; ?>[events][abandoned_cart]" id="event_abandoned_cart"></select>
    </div>
    <br/>
    <div id="abandoned_cart_settings">
        <div>
            <div><label>Cart abandoned cut-off time (min)</label></div>
            <input type="text" name="<?php echo $this->plugin_name; ?>[abandoned_cart][time]" value="<?php echo $abandonedCartTime; ?>"/>
        </div>
        <br/>
        <div>
            <div><label>Automatically Delete Abandoned Orders after X days</label></div>
            <input type="text" name="<?php echo $this->plugin_name; ?>[abandoned_cart][remove_after_days]" value="<?php echo $abandonedCartRemoveAfterDays; ?>"/>
        </div>
        <br/>
    </div>
    <div>
        <label>Abandoned Cart Recovered</label>
        <div>When a user places an order from Abandoned Cart link</div>
        <select data-placeholder="Select a Event" data-event="AbandonedCartRecovered" data-allow-clear="true" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-events-list" name="<?php echo $this->plugin_name; ?>[events][abandoned_cart_recovered]"></select>
    </div>
</div>
<br/>
<br/>
