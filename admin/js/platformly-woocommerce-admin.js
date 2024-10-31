var projectId = '';
(function($){
    $(document).ready(function(){
        // Automatically check saved API-key while opening Connect tab
        if($('#platformlyWcApiKey').length > 0 && $('#platformlyWcApiKey').val().length > 0){
            $('#platformlyWcCheckApiKey').click();
        }

        $('.platformly-wc-select2').select2({
            width: '100%'
        });

        if($('.platformly-wc-projects-list').length > 0 && $('#platformlyWcApiKey').length === 0){
            loadProjects();
        }
        if($('.platformly-wc-events-list').length > 0){
            loadEvents();
        }

        if ($('#platformly-wc-project-id').length > 0) {
            projectId = $('#platformly-wc-project-id').val();
        } else if ($('#platformlyIpnProjectId').length > 0) {
            projectId = $('#platformlyIpnProjectId').val();
        }

        loadSegments();
        loadTags();

        $('.platformly-wc-projects-list').change(function(){
            var projectId = $(this).val();

            if($('#platformlyPaymentProcessorsList').length > 0 && projectId !== ''){
                var apiKey = typeof $(this).attr('data-api') !== 'undefined' ? $(this).attr('data-api') : '';
                $('#platformlyPaymentProcessorsList').prop("disabled", true);

                $.post(
                    ajaxurl,
                    {action: 'platformly_wc_get_payment_processors', platformly_wc_project_id: projectId, apiKey: apiKey},
                    function(response){
                        if(response.success){
                            var paymentProcessorList = '<option></option>';
                            for(var key in response.data) {
                                paymentProcessorList += '<option value="'+response.data[key]['ipn_url']+'">'+response.data[key]['name']+'</option>';
                            }
                            $('#platformlyPaymentProcessorsList').html(paymentProcessorList);
                            $('#platformlyPaymentProcessorsList.platformly-wc-not-selected').val($('#platformlyIpnUrl').val()).trigger('change').removeClass('platformly-wc-not-selected');
                            $('#platformlyPaymentProcessorsList').prop("disabled", false);
                        }
                    }
                );
            }

            $.post(
                ajaxurl,
                {action: 'platformly_wc_get_project_code', platformly_wc_project_id: projectId, apiKey: apiKey},
                function(response){
                    if(response.success){
                        $('#platformlyWcProjectCode').val(response.data);
                    }
                }
            );
        });

        // Hide API key error message when 
        $( "#platformlyWcApiKey" ).keyup(function(){
            $('.platform-wc-api-key-error').addClass('hidden');
        });

        $('#platformlyWcSetingSyncContacts').change(function(){
            if($(this).prop('checked')){
                $('#platformlyWcSettingsBlockSyncContacts').show();
            }else{
                $('#platformlyWcSettingsBlockSyncContacts').hide();
            }
        });
    });

    $('#platformlyWcCheckApiKey').click(function(){
        $.post(
            ajaxurl,
            {action: 'platformly_wc_check_api_key', api_key: $('#platformlyWcApiKey').val()},
            function(response){
                if(response.success){
                    if($('#platformlyProjectsListPP').val() === null){
                        $('#platformlyProjectsListPP').attr('data-api', $('#platformlyWcApiKey').val());
                        loadProjects($('#platformlyWcApiKey').val());
                    }
                    $('#platformlyWcPlatformAvatar').attr('title', response.data.first_name).attr('src', response.data.profile_image);
                    $('#platformlyWcPlatformFullName').text(response.data.first_name+' '+response.data.last_name);
                    $('#platformlyWcPlatformEmail').text(response.data.email);
                    $('#platformlyWcPlatformVisitAccountBtn').attr('data-link', response.data.main_url+'?page=settings.personal_information');
                    $('#platformlyWcVisitPaymentProcesorPage').attr('href', response.data.main_url+'?page=settings.setup&p=setup.sales');
                    $('#platformlyWcPlatformBlock').removeClass('hidden');
                    $('#platformlyPaymentProcessorBlock').removeClass('hidden');
                    $('.platform-wc-api-key-error').addClass('hidden');
                }else{
                    $('#platformlyWcPlatformBlock').addClass('hidden');
                    $('#platformlyPaymentProcessorBlock').addClass('hidden');
                    //$('.ply-wc-contacts-settings-tab').remove();
                    //$('.ply-wc-events-settings-tab').remove();
                    //platformlyWcAddError('The API key you added is not correct.');
                    var errorMsg = 'The API key is not correct.'
                    if(typeof response.data.msg !== 'undefined'){
                        errorMsg = response.data.msg;
                    }
                    $('.platform-wc-api-key-error').text(errorMsg);
                    $('.platform-wc-api-key-error').removeClass('hidden');
                }
            }
        );
    });
    $('#platformlyWcPlatformVisitAccountBtn').click(function(){
        window.open($('#platformlyWcPlatformVisitAccountBtn').data('link'), '_blank');
    });
    function platformlyWcAddError(msg){
        $('.settings-error').remove();
        $('.nav-tab-wrapper').before('<div id="setting-error-settings_updated" class="error settings-error notice is-dismissible"><p><strong>'+msg+'</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
    }
    function loadProjects(apiKey){
        var apiKey = typeof apiKey !== 'undefined' ? apiKey : '';
        $.post(
            ajaxurl, 
            {'action': 'platformly_wc_get_projects', apiKey: apiKey}, 
            function(response){
                if(response.success){
                    // Set projects list
                    var projectList = '<option></option>';
                    for(var key in response.data) {
                        projectList += '<option value="'+key+'">'+response.data[key]+'</option>';
                    }
                    $('.platformly-wc-projects-list').html(projectList);

                    // For Connect page
                    if($('#platformlyPaymentProcessorsList').length > 0){
                        $('#platformlyProjectsListPP').val($('#platformlyIpnProjectId').val()).trigger('change');
                    }

                    // Set active project on the Contacts settings tab
                    if($('#projectsPurchasedVal').length > 0){
                        if($('#projectsCartVal').length > 0){
                            $('#projectsCart').val(JSON.parse($('#projectsCartVal').val())).trigger('change');
                        }
                        if($('#projectGdpr').length > 0){
                            $('#projectGdpr').val(JSON.parse($('#projectsGdprVal').val())).trigger('change');
                        }

                        $('#projectsPurchased').val(JSON.parse($('#projectsPurchasedVal').val())).trigger('change');
                        $('#projectsRefunded').val(JSON.parse($('#projectsRefundedVal').val())).trigger('change');
                        $('#projectsFailed').val(JSON.parse($('#projectsFailedVal').val())).trigger('change');
                        $('#projectsCancelled').val(JSON.parse($('#projectsCancelledVal').val())).trigger('change');
                        $('#projectsRegistered').val(JSON.parse($('#projectsRegisteredVal').val())).trigger('change');
                    }

                    // Set active project on the product's page
                    if($('#platformly-wc-project-id').length > 0){
                        var projectId = JSON.parse($('#platformly-wc-project-id').val());
                        $('#projectsCart').val(projectId).trigger('change');
                        $('#projectsPurchased').val(projectId).trigger('change');
                        $('#projectsRefunded').val(projectId).trigger('change');
                        $('#projectsFailed').val(projectId).trigger('change');
                        $('#projectsCancelled').val(projectId).trigger('change');
                    }

                    // Set active project on the Sync Data tab
                    if($('#syncDataPageContent').length > 0){
                        var projectId = $('#platformlyIpnProjectId').val();
                        $('#projectsSyncContacts').val(projectId).trigger('change');
                    }
                }
            }
        );
    }
    function loadEvents(){
        $.post(
            ajaxurl, 
            {'action': 'platformly_wc_get_events'}, 
            function(response){
                if(response.success){
                    var eventsList = '<option></option>';
                    for(var key in response.data) {
                        eventsList += '<option value="'+response.data[key]['id']+'">['+response.data[key]['action']+'] '+response.data[key]['description']+'</option>';
                    }
                    $('.platformly-wc-events-list').html(eventsList);
                    $('.platformly-wc-events-list').each(function(i){
                        var event = $(this).data('event');
                        $(this).val($('#platfromlyWcEvent'+event).val()).trigger('change');
                    });

                    if ($('#event_abandoned_cart').val() !== '') {
                        $('#abandoned_cart_settings').css('display', 'block');
                    }
                }
            }
        );
    }

    function loadSegments() {
        if($('.platformly-wc-segments-list').length > 0){
            $.post(
                ajaxurl, {
                    action: 'platformly_wc_get_segments',
                    platformly_wc_project_id: projectId
                }, function(response){
                    if(response.success){
                        var segmentsList = '<option></option>';
                        for(var key in response.data) {
                            segmentsList += '<option value="'+key+'">'+response.data[key]+'</option>';
                        }
                        $('.platformly-wc-segments-list').html(segmentsList);

                        $.each($('.platformly-wc-segments-list'), function(i, el) {
                            var parentDiv = $(el).parents('.platformly_wc_contact_settings_block'),
                                type = $(parentDiv).data('typesettings');
                            if($('#segments'+type+'Val').length > 0){
                                var segmentsVal = $('#segments'+type+'Val').val();
                                $(parentDiv).find('.platformly-wc-segments-list.platformly-wc-not-selected').val(JSON.parse(segmentsVal)).trigger('change').removeClass('platformly-wc-not-selected');
                            }
                        });
                    }
                }
            );
        }
    }
    
    function loadTags() {
        if($('.platformly-wc-tags-list').length > 0){
            $.post(
                ajaxurl, {
                    action: 'platformly_wc_get_tags',
                    platformly_wc_project_id: projectId
                }, function(response){
                    if(response.success){
                        var tagsList = '<option></option>';
                        for(var key in response.data) {
                            tagsList += '<option value="'+key+'">'+response.data[key]+'</option>';
                        }
                        $('.platformly-wc-tags-list').html(tagsList);

                        $.each($('.platformly-wc-tags-list'), function(i, el) {
                            var parentDiv = $(el).parents('.platformly_wc_contact_settings_block'),
                                type = $(parentDiv).data('typesettings');
                            if($('#tags'+type+'Val').length > 0){
                                var segmentsVal = $('#tags'+type+'Val').val();
                                $(parentDiv).find('.platformly-wc-tags-list.platformly-wc-not-selected').val(JSON.parse(segmentsVal)).trigger('change').removeClass('platformly-wc-not-selected');
                            }
                        });
                    }
                }
            );
        }
    }

    // Show/hide block with abandoned_cart_settings
    jQuery('#event_abandoned_cart').on('change', function(){
        if (jQuery(this).val() !== '') {
            $('#abandoned_cart_settings').css('display', 'block');
        } else {
            $('#abandoned_cart_settings').css('display', 'none');
        }
    });

    $(document).on('select2:select', '.filter-select2-multiple-with-all', function(e){
        var selectedAll = $(this).val();
        var allResIndex = $.inArray('all', selectedAll);
        if(e.params.data.id == 'all'){
            $(this).val('all').trigger("change");
        }else if(allResIndex >= 0){
            selectedAll.splice(allResIndex ,1 );
            $(this).val(selectedAll).trigger("change");
        }
    });
    $(document).on('select2:unselect', '.filter-select2-multiple-with-all', function(e){
        var selectedAll = $(this).val();
        var allResIndex = $.inArray('all', selectedAll);
        if(e.params.data.id == 'all'){
            $(this).val('all').trigger("change");
        }else if(allResIndex >= 0){
            selectedAll.splice(allResIndex ,1 );
            $(this).val(selectedAll).trigger("change");
        }else if(selectedAll === null){
            $(this).val('all').trigger("change");
        }
    });

    $('#platformlyProjectsListPP').on('select2:open', function(){
        if($('#platformlyProjectsListPP').hasClass('platformly-wc-ply-official-code-set')){
            if(!confirm("If you change the project Project code will be also changed in your 'Platform.ly Official' plugin, continue?")){
                $('#platformlyProjectsListPP').select2('close');
            }
        }
    });
})(jQuery);
