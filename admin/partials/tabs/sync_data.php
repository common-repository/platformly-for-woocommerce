<?php
    if(!defined('ABSPATH')){
        exit; // Exit if accessed directly
    }
    
    $projectId = isset($options['platformly-wc-project-id']) ? $options['platformly-wc-project-id'] : '';
    $paymentProcessorProject = '['.$projectId.']';
    $syncContacts = isset($options['sync_contacts']) ? $options['sync_contacts'] : true;
    $syncSales = isset($options['sync_sales']) ? $options['sync_sales'] : true;

    $segmentsSyncContacts = isset($options['segment_sync_contacts']) ? json_encode($options['segment_sync_contacts']) : '[]';
    $tagsSyncContacts = isset($options['tags_sync_contacts']) ? json_encode($options['tags_sync_contacts']) : '[]';
    $syncProgress = isset($options['sync_progress']) ? $options['sync_progress'] : '';
    $disabledSyncContacts = isset($options['sync_contacts']) && $options['sync_contacts'] === true ? 'disabled' : '';
    $disabledSyncSales = isset($options['sync_sales']) && $options['sync_sales'] === true ? 'disabled' : '';
    $submitBtnOptions = array('id' => 'platformlyWcSubmitBtn');
    if(!empty($syncProgress) && $syncProgress != 'completed'){
        $submitBtnOptions['disabled'] = 'disabled';
    }
?>

<input type="hidden" id="platformlyIpnProjectId" value="<?php echo $projectId ?>"/>
<input type="hidden" id="projectsSyncContactsVal" value='<?php echo $paymentProcessorProject ?>'/>
<input type="hidden" id="segmentsSyncContactsVal" value='<?php echo $segmentsSyncContacts ?>'/>
<input type="hidden" id="tagsSyncContactsVal" value='<?php echo $tagsSyncContacts ?>'/>

<?php if($syncProgress == 'completed' || $syncProgress == 'disabled'): ?>
    <div class="notice notice-success is-dismissible">
        <p>Your import has been completed. Your existing data have been synced in your account.</p>
    </div>
<?php endif; ?>

<div id="syncDataPageContent" class="platformly-wc-sync-data">
    <br/>
    <div>
        <label>
            <input type="checkbox" name="<?php echo $this->plugin_name ?>[sync_sales]" value="1" <?php echo $syncSales ? 'checked="checked"' : '' ?> <?php echo $disabledSyncSales ?>/> <b>Sync all sales data</b>
        </label>
    </div>
    <div>
        <label>
            <input id="platformlyWcSetingSyncContacts" type="checkbox" name="<?php echo $this->plugin_name ?>[sync_contacts]" value="1" <?php echo $syncContacts ? 'checked="checked"' : '' ?> <?php echo $disabledSyncContacts ?>/> <b>Sync all contacts</b>
        </label>
    </div>
    <div id="platformlyWcSettingsBlockSyncContacts" class="platformly_wc_contact_settings_block" data-typesettings="SyncContacts" <?php echo $syncContacts ? 'style="display: block;"' : 'style="display: none;"' ?>>
        <h4>Choose a Tag or Segment for imported contacts</h4>
        <div>
            <select data-typesettings="SyncContacts" id="projectsSyncContacts" data-placeholder="Select a Project" class="platformly-wc-select2 platformly-wc-projects-list" name="<?php echo $this->plugin_name; ?>[projects_sync_contacts][]" disabled></select>
        </div>
        <br/>
        <div>
            <select id="segmentsSyncContacts" multiple="multiple" data-placeholder="Select one or more Segments" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-segments-list" name="<?php echo $this->plugin_name; ?>[segment_sync_contacts][]" <?php echo $disabledSyncContacts ?>></select>
        </div>
        <br/>
        <div>
            <select id="tagsSyncContacts" multiple="multiple" data-placeholder="Select one or more Tags" class="platformly-wc-not-selected platformly-wc-select2 platformly-wc-tags-list" name="<?php echo $this->plugin_name; ?>[tags_sync_contacts][]" <?php echo $disabledSyncContacts ?>></select>
        </div>
    </div>
</div>
<?php if($syncProgress == 'in_the_process'): ?>
    <div>
        <p><b>Your import is in progress, we are importing your existing data. The time depends on the amount of data.</b></p>
    </div>
<?php endif; ?>
<?php submit_button('Start sync', 'primary','submit', TRUE, $submitBtnOptions); ?>