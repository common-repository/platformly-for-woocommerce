<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

    $classForProjectMsg = isset($options['ply_official_project_code_active']) && !empty($options['ply_official_project_code_active']) ? '' : 'hidden';
    $classProjectsListPP = $this->platformlyOfficialActive ? 'platformly-wc-ply-official-code-set' : '';
?>
<input type="hidden" id="platformlyIpnUrl" value="<?php echo $ipnUrl ?>"/>
<input type="hidden" id="platformlyIpnProjectId" value="<?php echo $projectId ?>"/>
<div id="platformlty_wc_api_key_val">
    <br/>
    <label>
        <div>Please Enter Your API Key:</div>
        <input id="platformlyWcApiKey" type="text" name="<?php echo $this->plugin_name ?>[API_key]" value="<?php echo isset($options['API_key']) && !empty($options['API_key']) ? $options['API_key'] : '' ?>"/>
        <input type="button" id="platformlyWcCheckApiKey" class="button" value="Connect" />
    </label>
    <div class="platform-wc-api-key-error hidden">The API key is not correct.</div>
    <div style="margin-top: 8px;" class="platform-wc-text-bold">You will need to add your API Key from your Platform.ly account.</div>
    <div>You can find the API section if you click on your name in the upper right corner on Platform.ly and then on 'Api Keys'.</div>
</div>
<br/>
<div id="platformlyWcPlatformBlock" class="hidden">
    <table width="100%" border="0" cellspacing="0" cellpadding="3">
        <tr>
            <td width="90" valign="top">
                <img id="platformlyWcPlatformAvatar" src="" width="80" height="80" class="round" style="border:3px #f4f4f4 solid;" title="" />
            </td>
            <td valign="top">
                <h2 style="margin-bottom:0; display: inline-block;margin-top: 5px;">Welcome <span id="platformlyWcPlatformFullName"></span></h2>
                <div><strong>Email: <span id="platformlyWcPlatformEmail"></span></strong></div>
                <input id="platformlyWcPlatformVisitAccountBtn" type="button" data-link="" class="button" value="Visit your Account"/>
            </td>
        </tr>
    </table>
</div>
<br/>
<div id="platformlyPaymentProcessorBlock" class="<?php echo isset($options['API_key']) && !empty($options['API_key']) && $apiKeyCorrect === true ? '' : 'hidden' ?>">
    <div>
        <select id="platformlyProjectsListPP" data-placeholder="select a Project" name="<?php echo $this->plugin_name ?>[platformly_wc_project_id]" class="platformly-wc-select2 platformly-wc-projects-list <?php echo $classProjectsListPP ?>"></select>
    </div>
    <div id="platformlyWcProjectMsg" class="<?php echo $classForProjectMsg ?>">
        <b>This project corresponds to the project in the Platform.ly Official plugin</b>
    </div>
    <br/>
    <div>
        <select id="platformlyPaymentProcessorsList" name="<?php echo $this->plugin_name ?>[platformly-wc-ipn-url]" data-placeholder="select a Payment Processors" class="platformly-wc-not-selected platformly-wc-select2"></select>
        <div style="margin-top: 5px;"><a id='platformlyWcVisitPaymentProcesorPage' target="_blank" href="#">Click here</a> to add a new WooCommerce Payment Processor</div>
    </div>
    <br/>
    <div id="platformlyWcListT">
        <div style="margin-bottom: 5px;">Choose your Transactions</div>
        <select class="platformly-wc-select2 filter-select2-multiple-with-all" multiple="" name="<?php echo $this->plugin_name ?>[platformly_wc_forward_transactions][]">
            <option value="all" <?php echo !isset($options['forward_transactions']) || empty($options['forward_transactions']) ? 'selected' : '' ?> >All</option>
            <option value="completed" <?php echo isset($options['forward_transactions']) && is_array($options['forward_transactions']) && in_array('completed', $options['forward_transactions']) ? 'selected' : '' ?>>Sale</option>
            <option value="refunded" <?php echo isset($options['forward_transactions']) && is_array($options['forward_transactions']) && in_array('refunded', $options['forward_transactions']) ? 'selected' : '' ?>>Refund</option>
            <option value="cancelled" <?php echo isset($options['forward_transactions']) && is_array($options['forward_transactions']) && in_array('cancelled', $options['forward_transactions']) ? 'selected' : '' ?>>Cancelled</option>
            <?php /*
            <option value='subscription_payment_complete' <?php echo isset($options['forward_transactions']) && is_array($options['forward_transactions']) && in_array('subscription_payment_complete', $options['forward_transactions']) ? 'selected' : '' ?>>Re-Bill</option>
            <option value='subscription_cancelled' <?php echo isset($options['forward_transactions']) && is_array($options['forward_transactions']) && in_array('subscription_cancelled', $options['forward_transactions']) ? 'selected' : '' ?>>Subscription Cancelled</option>
            */ ?> 
        </select>
        <div style="margin-top: 5px;">Select what transactions you want to send to Platform.ly Sales Reporting. For best reporting practices we recommend sending them all (as it is by default).</div>
    </div>
    <br/>
    <label>
        <input id="platformly_wc_enable_ply" type="checkbox" name="<?php echo $this->plugin_name ?>[is_enable_PLY]" value="1" <?php echo $isEnablePLY ? 'checked="checked"' : '' ?>/> <b>Enable Platform.ly</b>
    </label>
    <div>Adds the ability to configure synchronization with the platform, configure the addition of tags and segments during actions on Woocommerce.</div>
    <input id="platformlyWcProjectCode" name="<?php echo $this->plugin_name ?>[platformly_wc_project_code]" type="hidden" value=""/>
</div>

