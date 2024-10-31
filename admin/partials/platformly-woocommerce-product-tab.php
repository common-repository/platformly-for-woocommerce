<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$productSettings = array(
    array('title' => 'Apply the following Segment(s) and/or Tag(s) when a product is added to cart', 'typeSettings' => 'Cart', 'valueP' => '', 'valueS' => '', 'valueT' => '', 'action' => 'add_to_cart'),
    array('title' => 'Apply the following Segment(s) and/or Tag(s) when a product is purchased', 'typeSettings' => 'Purchased', 'action' => 'completed'),
    array('title' => 'Apply the following Segment(s) and/or Tag(s) when an order is refunded', 'typeSettings' => 'Refunded', 'action' => 'refunded'),
    array('title' => 'Apply the following Segment(s) and/or Tag(s) when an order is failed', 'typeSettings' => 'Failed', 'action' => 'failed'),
    array('title' => 'Apply the following Segment(s) and/or Tag(s) when an order is cancelled', 'typeSettings' => 'Cancelled', 'action' => 'cancelled')
);
$data = get_post_meta(get_the_ID(), 'platformly-woocommerce', true);
?>
<div id="platformly_wc_tab_content" class="panel woocommerce_options_panel hidden">
    <h4 style="padding: 0 9px">Product</h4>
    <input type="hidden" id="platformly-wc-project-id" value="<?php echo $this->getOption('platformly-wc-project-id', ''); ?>">
    <?php foreach($productSettings as $key => $val): ?>
        <?php if(platform_wc_check_forward($val['action'])): ?>
            <div class="options_group platformly_wc_contact_settings_block" data-typesettings="<?php echo $val['typeSettings'] ?>">
                <input type="hidden" id="segments<?php echo $val['typeSettings'] ?>Val" value='<?php echo isset($data["segments{$val['typeSettings']}"]) ? json_encode($data["segments{$val['typeSettings']}"]) : '[]' ?>'/>
                <input type="hidden" id="tags<?php echo $val['typeSettings'] ?>Val" value='<?php echo isset($data["tags{$val['typeSettings']}"]) ? json_encode($data["tags{$val['typeSettings']}"]) : '[]' ?>'/>
                <p><?php echo $val['title'] ?></p>
                <p class="form-field">
                    <label>Project</label>
                    <select id='projects<?php echo $val['typeSettings'] ?>' name="<?php echo $this->plugin_name; ?>[projects<?php echo $val['typeSettings'] ?>][]" data-typesettings="<?php echo $val['typeSettings'] ?>" class="platformly-wc-select2 platformly-wc-projects-list" data-placeholder="Select a Project" disabled></select>
                </p>
                <p class="form-field">
                    <label>Segments</label>
                    <select name="<?php echo $this->plugin_name; ?>[segments<?php echo $val['typeSettings'] ?>][]" class="platformly-wc-select2 platformly-wc-segments-list platformly-wc-not-selected" data-placeholder="Select Segments" multiple=""></select>
                </p>
                <p class="form-field">
                    <label>Tags</label>
                    <select name="<?php echo $this->plugin_name; ?>[tags<?php echo $val['typeSettings'] ?>][]" class="platformly-wc-select2 platformly-wc-tags-list platformly-wc-not-selected" data-placeholder="Select Tags" multiple=""></select>
                </p>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
