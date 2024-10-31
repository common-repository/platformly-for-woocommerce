<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

    $projectId = isset($options['platformly-wc-project-id']) ? $options['platformly-wc-project-id'] : '';
    $paymentProcessorProject = '['.$projectId.']';

    $segmentsPurchased = isset($options['segments_purchased']) ? json_encode($options['segments_purchased']) : '[]';
    $segmentsRefunded = isset($options['segments_refunded']) ? json_encode($options['segments_refunded']) : '[]';
    $segmentsFailed = isset($options['segments_failed']) ? json_encode($options['segments_failed']) : '[]';
    $segmentsCancelled = isset($options['segments_cancelled']) ? json_encode($options['segments_cancelled']) : '[]';
    $segmentsRegistered = isset($options['segments_registered']) ? json_encode($options['segments_registered']) : '[]';
    $segmentGdpr = isset($options['gdpr_segment']) ? json_encode($options['gdpr_segment']) : '[]';

    $tagsPurchased = isset($options['tags_purchased']) ? json_encode($options['tags_purchased']) : '[]';
    $tagsRefunded = isset($options['tags_refunded']) ? json_encode($options['tags_refunded']) : '[]';
    $tagsFailed = isset($options['tags_failed']) ? json_encode($options['tags_failed']) : '[]';
    $tagsCancelled = isset($options['tags_cancelled']) ? json_encode($options['tags_cancelled']) : '[]';
    $tagsRegistered = isset($options['tags_registered']) ? json_encode($options['tags_registered']) : '[]';
    $tagGdpr = isset($options['gdpr_tag']) ? json_encode($options['gdpr_tag']) : '[]';
?>
<input type="hidden" id="platformlyIpnProjectId" value="<?php echo $projectId ?>"/>

<input type="hidden" id="projectsRegisteredVal" value='<?php echo $paymentProcessorProject ?>'/>
<input type="hidden" id="projectsPurchasedVal" value='<?php echo $paymentProcessorProject ?>'/>
<input type="hidden" id="projectsRefundedVal" value='<?php echo $paymentProcessorProject ?>'/>
<input type="hidden" id="projectsFailedVal" value='<?php echo $paymentProcessorProject ?>'/>
<input type="hidden" id="projectsCancelledVal" value='<?php echo $paymentProcessorProject ?>'/>
<input type="hidden" id="projectsGdprVal" value='<?php echo $paymentProcessorProject ?>'/>

<input type="hidden" id="segmentsRegisteredVal" value='<?php echo $segmentsRegistered ?>'/>
<input type="hidden" id="segmentsPurchasedVal" value='<?php echo $segmentsPurchased ?>'/>
<input type="hidden" id="segmentsRefundedVal" value='<?php echo $segmentsRefunded ?>'/>
<input type="hidden" id="segmentsFailedVal" value='<?php echo $segmentsFailed ?>'/>
<input type="hidden" id="segmentsCancelledVal" value='<?php echo $segmentsCancelled ?>'/>
<input type="hidden" id="segmentsGdprVal" value='<?php echo $segmentGdpr ?>'/>

<input type="hidden" id="tagsRegisteredVal" value='<?php echo $tagsRegistered ?>'/>
<input type="hidden" id="tagsPurchasedVal" value='<?php echo $tagsPurchased ?>'/>
<input type="hidden" id="tagsRefundedVal" value='<?php echo $tagsRefunded ?>'/>
<input type="hidden" id="tagsFailedVal" value='<?php echo $tagsFailed ?>'/>
<input type="hidden" id="tagsCancelledVal" value='<?php echo $tagsCancelled ?>'/>
<input type="hidden" id="tagsGdprVal" value='<?php echo $tagGdpr ?>'/>

<div class="platformly-wc-contacts-settings">
    <div id="settingsRegistered" class="platformly_wc_contact_settings_block" data-typesettings="Registered">
        <h2>New users</h2>
        <h4>Choose a Tag or Segment for new registered users</h4>
        <div>
            <select data-typesettings="Registered" id="projectsRegistered" data-placeholder="Select a Project" class="platformly-wc-select2 platformly-wc-projects-list" name="<?php echo $this->plugin_name; ?>[projects_registered][]" disabled></select>
        </div>
        <br/>
        <div>
            <select id="segmentsRegistered" multiple="multiple" data-placeholder="Select one or more Segments" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-segments-list" name="<?php echo $this->plugin_name; ?>[segments_registered][]"></select>
        </div>
        <br/>
        <div>
            <select id="tagsRegistered" multiple="multiple" data-placeholder="Select one or more Tags" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-tags-list" name="<?php echo $this->plugin_name; ?>[tags_registered][]"></select>
        </div>
    </div>
    <br>
    <h2>GDPR</h2>
    <label for="<?php echo $this->plugin_name; ?>-gdpr-checkbox-label">
        <div>Enter text for the opt-in checkbox</div>
        <textarea  rows="3" id="<?php echo $this->plugin_name; ?>-gdpr-checkbox-label" name="<?php echo $this->plugin_name; ?>[gdpr_label]"><?php echo isset($options['gdpr_label']) ? esc_html($options['gdpr_label']) : 'Subscribe to our newsletter'; ?></textarea>
    </label>
    <p class="description"><?= esc_html('HTML tags allowed: <a href="" target="" title=""></a> and <br>', 'mc-woocommerce'); ?></p>
    <h4 style="padding-top: 1em;">Checkbox Display Options</h4>
    <?php $checkbox_default_settings = (array_key_exists('paltformly_wc_checkbox_defaults', $options) && !is_null($options['paltformly_wc_checkbox_defaults'])) ? $options['paltformly_wc_checkbox_defaults'] : 'check'; ?>
    <label>
        <input type="radio" name="<?php echo $this->plugin_name; ?>[paltformly_wc_checkbox_defaults]" value="check"<?php if($checkbox_default_settings === 'check') echo ' checked="checked" '; ?>>Visible, checked by default<br>
    </label>
    <br/>
    <label>
        <input type="radio" name="<?php echo $this->plugin_name; ?>[paltformly_wc_checkbox_defaults]" value="uncheck"<?php if($checkbox_default_settings === 'uncheck') echo ' checked="checked" '; ?>>Visible, unchecked by default<br/>
    </label>
    <br/>
    <label>
        <input type="radio" name="<?php echo $this->plugin_name; ?>[paltformly_wc_checkbox_defaults]" value="hide"<?php if($checkbox_default_settings === 'hide') echo ' checked="checked" '; ?>>Do not show checkbox<br/>
    </label>
    <br/>
    <div class="platformly_wc_contact_settings_block" data-typesettings="Gdpr">
        <h4>Choose a Tag or a Segment for your subscribers</h4>
        <div>
            <select data-typesettings="Gdpr" name="<?php echo $this->plugin_name; ?>[gdpr_project]" id='projectGdpr' data-placeholder="Select a Project" class="platformly-wc-select2 platformly-wc-projects-list" disabled></select>
        </div>
        <br/>
        <div>
            <select id="segmentGdpr" multiple="multiple" data-placeholder="Select Segment" data-allow-clear="true" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-segments-list" name="<?php echo $this->plugin_name; ?>[gdpr_segment][]"></select>
        </div>
        <br/>
        <div>
            <select id="tagGdpr" multiple="multiple" data-placeholder="Select Tag" data-allow-clear="true" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-tags-list" name="<?php echo $this->plugin_name; ?>[gdpr_tag][]"></select>
        </div>
    </div>
    <br/>
    <h2>Purchase Actions</h2>
    <?php if(platform_wc_check_forward('completed')): ?>
        <div id="settingsPurchased" class="platformly_wc_contact_settings_block" data-typesettings="Purchased">
            <h4>When purchase is made<br/>
                Segment your customers or add a Tag when a purchase is made</h4>
            <div>
                <select data-typesettings="Purchased" id="projectsPurchased" data-placeholder="Select a Project" class="platformly-wc-select2 platformly-wc-projects-list" name="<?php echo $this->plugin_name; ?>[projects_purchased][]" disabled></select>
            </div>
            <br/>
            <div>
                <select id="segmentsPurchased" multiple="multiple" data-placeholder="Select one or more Segments" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-segments-list" name="<?php echo $this->plugin_name; ?>[segments_purchased][]"></select>
            </div>
            <br/>
            <div>
                <select id="tagsPurchased" multiple="multiple" data-placeholder="Select one or more Tags" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-tags-list" name="<?php echo $this->plugin_name; ?>[tags_purchased][]"></select>
            </div>
        </div>
        <br/>
    <?php endif; ?>
    <?php if(platform_wc_check_forward('refunded')): ?>
        <div id="settingsRefunded" class="platformly_wc_contact_settings_block" data-typesettings="Refunded">
            <h4>When an order is refunded<br/>
                Segment your customers or add a Tag when an order is refunded</h4>
            <div>
                <select data-typesettings="Refunded" id="projectsRefunded" data-placeholder="Select a Project" class="platformly-wc-select2 platformly-wc-projects-list" name="<?php echo $this->plugin_name; ?>[projects_refunded][]" disabled></select>
            </div>
            <br/>
            <div>
                <select id="segmentsRefunded" multiple="multiple" data-placeholder="Select one or more Segments" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-segments-list" name="<?php echo $this->plugin_name; ?>[segments_refunded][]"></select>
            </div>
            <br/>
            <div>
                <select id="tagsRefunded" multiple="multiple" data-placeholder="Select one or more Tags" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-tags-list" name="<?php echo $this->plugin_name; ?>[tags_refunded][]"></select>
            </div>
        </div>
        <br/>
    <?php endif; ?>
    <?php if(platform_wc_check_forward('failed')): ?>
        <div id="settingsFailed" class="platformly_wc_contact_settings_block" data-typesettings="Failed">
            <h4>When a transaction fails<br/>
                Segment your customers or add a Tag when a failed transaction happens</h4>
            <div>
                <select data-typesettings="Failed" id="projectsFailed" data-placeholder="Select a Project" class="platformly-wc-select2 platformly-wc-projects-list" name="<?php echo $this->plugin_name; ?>[projects_failed][]" disabled></select>
            </div>
            <br/>
            <div>
                <select id="segmentsFailed" multiple="multiple" data-placeholder="Select one or more Segments" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-segments-list" name="<?php echo $this->plugin_name; ?>[segments_failed][]"></select>
            </div>
            <br/>
            <div>
                <select id="tagsFailed" multiple="multiple" data-placeholder="Select one or more Tags" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-tags-list" name="<?php echo $this->plugin_name; ?>[tags_failed][]"></select>
            </div>
        </div>
    <?php endif; ?>
    <?php if(platform_wc_check_forward('cancelled')): ?>
        <div id="settingsCancelled" class="platformly_wc_contact_settings_block" data-typesettings="Cancelled">
            <h4>When an order is cancelled<br/>
                Segment your customers or add a Tag when an order is cancelled</h4>
            <div>
                <select data-typesettings="Cancelled" id="projectsCancelled" data-placeholder="Select a Project" class="platformly-wc-select2 platformly-wc-projects-list" name="<?php echo $this->plugin_name; ?>[projects_cancelled][]" disabled></select>
            </div>
            <br/>
            <div>
                <select id="segmentsCancelled" multiple="multiple" data-placeholder="Select one or more Segments" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-segments-list" name="<?php echo $this->plugin_name; ?>[segments_cancelled][]"></select>
            </div>
            <br/>
            <div>
                <select id="tagsCancelled" multiple="multiple" data-placeholder="Select one or more Tags" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-tags-list" name="<?php echo $this->plugin_name; ?>[tags_cancelled][]"></select>
            </div>
        </div>
    <?php endif; ?>
</div>
<br/>
<br/>
