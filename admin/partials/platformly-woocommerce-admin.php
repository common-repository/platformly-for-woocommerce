<?php
    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly
    }

    $options = get_option($this->plugin_name, array());
    $isEnablePLY = !empty($options['API_key']) && !empty($options['is_enable_PLY']) ? true : false;
    $active_tab = isset($_GET['tab']) && $isEnablePLY && $apiKeyCorrect === true ? $_GET['tab'] : 'connect';

    $platformlyOfficialProjectCodeId = empty($this->platformlyOfficialProjectCodeId) ? '' : $this->platformlyOfficialProjectCodeId;
    $projectId = isset($options['platformly-wc-project-id']) ? $options['platformly-wc-project-id'] : '';
    $ipnUrl = isset($options['platformly-wc-ipn-url']) ? $options['platformly-wc-ipn-url'] : '';
?>
<?php settings_errors(); ?>
<div class="wrap">
    <h2>Platform.ly for WooCommerce Settings</h2>
    <h2 class="nav-tab-wrapper">
        <a href="?page=platformly-woocommerce&tab=connect" class="nav-tab <?php echo $active_tab == 'connect' ? 'nav-tab-active' : ''; ?>">Connect</a>
        <?php if(isset($options['API_key']) && $apiKeyCorrect === true && $projectId && $ipnUrl): ?>
            <?php if($isEnablePLY): ?>
                <a href="?page=platformly-woocommerce&tab=contacts_settings" class="nav-tab <?php echo $active_tab == 'contacts_settings' ? 'nav-tab-active' : ''; ?> ply-wc-contacts-settings-tab">Contacts Settings</a>
                <a href="?page=platformly-woocommerce&tab=events_settings" class="nav-tab <?php echo $active_tab == 'events_settings' ? 'nav-tab-active' : ''; ?> ply-wc-events-settings-tab">Events Settings</a>
            <?php endif; ?>
            <a href="?page=platformly-woocommerce&tab=sync_data" class="nav-tab <?php echo $active_tab == 'sync_data' ? 'nav-tab-active' : ''; ?> ply-wc-sync-data-tab">Sync Data</a>
        <?php endif; ?>
    </h2>
    <form method="post" name="cleanup_options" action="options.php">
        <?php 
            settings_fields($this->plugin_name);
            do_settings_sections($this->plugin_name); 
        ?>

        <input type="hidden" name="<?php echo $this->plugin_name; ?>[palatformly_wc_active_tab]" value="<?php echo esc_attr($active_tab); ?>"/>

        <?php if ($active_tab == 'connect' ){
            include_once 'tabs/connect.php';
        }elseif($active_tab == 'contacts_settings' && $isEnablePLY && $apiKeyCorrect === true){
            include_once 'tabs/contact_settings.php';
        }elseif($active_tab == 'events_settings' && $isEnablePLY && $apiKeyCorrect === true){
            include_once 'tabs/events_settings.php';
        }elseif($active_tab == 'sync_data' && $apiKeyCorrect === true){
            include_once 'tabs/sync_data.php';
        }
        if($active_tab != 'sync_data'){
            submit_button('Save all changes', 'primary','submit', TRUE, array('id' => 'platformlyWcSubmitBtn'));
        } ?>
    </form>
</div>
