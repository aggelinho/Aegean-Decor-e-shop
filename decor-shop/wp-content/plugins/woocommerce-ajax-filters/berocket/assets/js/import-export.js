(function($) {
    var brfrImportState = {
        token: '',
        links: [],
        customPostTypes: [],
        hasCustomPosts: false,
        customPosts: [],
        customPostGroups: []
    };

    function config() {
        return window.berocket_import_export || {};
    }

    function textValue(key, fallback) {
        if (config().text && typeof config().text[key] !== 'undefined') {
            return config().text[key];
        }
        return fallback;
    }

    function brfrGetExportData() {
        var pluginSlug = $('.brfr_import_export_form select[name="plugin"]').val();
        $('.brapf_export_loading').show();
        $.get(ajaxurl, { action: 'brfr_get_export_settings', plugin: pluginSlug, nonce: config().nonce }, function(data) {
            $('.brfr_import_export_block .brfr_export').text(data);
            $('.brapf_export_loading').hide();
        });
    }

    function brfrGetImportBackup() {
        var pluginSlug = $('.brfr_import_export_form select[name="plugin"]').val();
        $('.brfr_backup_form').hide();
        $('.brfr_backup_form .brfr_backup_form_select select').remove();
        $.get(ajaxurl, { action: 'brfr_get_import_backups', plugin: pluginSlug, nonce: config().nonce }, function(data) {
            if (data) {
                $('.brfr_backup_form .brfr_backup_form_select').append($(data));
                $('.brfr_backup_form').show();
            }
        });
    }

    function brfrEscapeHtml(text) {
        return $('<div>').text(text).html();
    }

    function brfrEscapeAttr(text) {
        return brfrEscapeHtml(text).replace(/"/g, '&quot;');
    }

    function brfrCollectLinkActions() {
        var actions = {};
        $('.brfr-import-link-item').each(function() {
            var linkUrl = $(this).data('url');
            var action = $(this).find('input[type="radio"]:checked').val();
            actions[linkUrl] = { action: action };
            if (action === 'media') {
                actions[linkUrl].replacement = $(this).find('.brfr-import-link-media-url').val();
            } else if (action === 'custom') {
                actions[linkUrl].replacement = $(this).find('.brfr-import-link-custom-url').val();
            }
        });
        return actions;
    }

    function brfrCollectCustomPostSlugStrategies() {
        var strategies = {};
        $.each(brfrImportState.customPosts, function(index, postItem) {
            var group = $('.brfr-import-custom-post-group[data-post-type="' + postItem.post_type_key + '"]');
            if (group.find('.brfr-delete-custom-post-type').is(':checked')) {
                return;
            }
            var key = postItem.map_key;
            strategies[key] = $('input[name="brfr_custom_post_slug_strategy_' + key + '"]:checked').val() || 'update';
        });
        return strategies;
    }

    function brfrCollectDeleteCustomPostTypes() {
        var flags = {};
        $('.brfr-delete-custom-post-type:checked').each(function() {
            flags[$(this).data('post-type')] = '1';
        });
        return flags;
    }

    function brfrResetImportLinks() {
        brfrImportState = {
            token: '',
            links: [],
            customPostTypes: [],
            hasCustomPosts: false,
            customPosts: [],
            customPostGroups: []
        };
        $('.brfr_import_links_list').html('');
        $('.brfr_import_links_notice').text('');
        $('.brfr_import_links_step').hide();
        $('.brfr_import_all').show();
        $('.brfr_export_all').show();
        $('.brfr_backup_all').show();
        $('.brfr_import_export_form_plugin').show();
    }

    function brfrGetImportDataEnd() {
        $('.brfr_import_export_form .brfr_import').text('');
        $('.brfr_import_export_form .brfr_import').val('');
        $('.brfr_import_export_form .brfr_import').prop('disabled', false);
        $('.brfr_import_export_form .brfr_import_send').prop('disabled', false);
        $('.brfr_import_confirm').prop('disabled', false);
    }

    function brfrRenderImportLinks() {
        var html = '';
        if (brfrImportState.links.length) {
            html += '<table class="widefat striped brfr-import-links-table">';
            html += '<thead><tr>';
            html += '<th>' + brfrEscapeHtml(textValue('link', 'Link')) + '</th>';
            html += '<th>' + brfrEscapeHtml(textValue('leave_as_is', 'Leave as is')) + '</th>';
            html += '<th>' + brfrEscapeHtml(textValue('replace_site', 'Use same path on this site')) + '</th>';
            html += '<th>' + brfrEscapeHtml(textValue('custom', 'Custom')) + '</th>';
            html += '</tr></thead><tbody>';
            $.each(brfrImportState.links, function(index, link) {
                var rowClass = 'brfr-import-link-item';
                if (link.type === 'image') {
                    rowClass += ' brfr-import-link-image';
                }
                html += '<tr class="' + rowClass + '" data-index="' + index + '" data-url="' + brfrEscapeAttr(link.url) + '" data-type="' + brfrEscapeAttr(link.type) + '">';
                html += '<td class="brfr-import-link-url">' + brfrEscapeHtml(link.url) + '</td>';
                html += '<td><label><input type="radio" name="brfr_link_action_' + index + '" value="keep" checked></label></td>';
                html += '<td><label><input type="radio" name="brfr_link_action_' + index + '" value="replace_site"></label></td>';
                html += '<td class="brfr-import-link-custom-cell">';
                if (link.type === 'image') {
                    html += '<label><input type="radio" name="brfr_link_action_' + index + '" value="media"></label>';
                    html += '<div class="brfr-import-link-media" style="display:none;">';
                    html += '<input type="hidden" class="brfr-import-link-media-url" value="">';
                    html += '<button type="button" class="button brfr-import-link-media-select">' + brfrEscapeHtml(textValue('select_image', 'Select image')) + '</button>';
                    html += '<span class="brfr-import-link-media-value"></span>';
                    html += '</div>';
                } else {
                    html += '<label><input type="radio" name="brfr_link_action_' + index + '" value="custom"></label>';
                    html += '<div class="brfr-import-link-custom" style="display:none;">';
                    html += '<input type="text" class="regular-text brfr-import-link-custom-url" value="" placeholder="' + brfrEscapeAttr(textValue('enter_url', 'Enter URL')) + '">';
                    html += '</div>';
                }
                html += '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
        }
        $('.brfr_import_links_notice').text(brfrImportState.links.length ? textValue('review_links_notice', 'Choose how to handle found links before the final import.') : '');
        $('.brfr_import_links_list').html(html);

        if (brfrImportState.hasCustomPosts) {
            var customPostsHtml = '<div class="brfr-import-custom-posts-option">';
            customPostsHtml += '<div class="brfr-import-custom-posts-title">' + brfrEscapeHtml(textValue('imported_custom_posts', 'Imported custom posts')) + '</div>';
            $.each(brfrImportState.customPostGroups, function(index, group) {
                var typeKey = brfrEscapeAttr(group.post_type_key);
                customPostsHtml += '<div class="brfr-import-custom-post-group" data-post-type="' + typeKey + '">';
                customPostsHtml += '<div class="brfr-import-custom-post-group-title">' + brfrEscapeHtml(group.post_type_label) + '</div>';
                customPostsHtml += '<label><input type="checkbox" class="brfr-delete-custom-post-type" data-post-type="' + typeKey + '" value="1"> ' + brfrEscapeHtml(textValue('delete_existing_posts', 'Delete existing custom posts of this type before import')) + '</label>';
                customPostsHtml += '<div class="brfr-import-custom-post-group-items">';
                if (group.posts.length) {
                    customPostsHtml += '<table class="widefat striped brfr-import-custom-posts-table">';
                    customPostsHtml += '<thead><tr>';
                    customPostsHtml += '<th>' + brfrEscapeHtml(textValue('name', 'Name')) + '</th>';
                    customPostsHtml += '<th>' + brfrEscapeHtml(textValue('update', 'Update')) + '</th>';
                    customPostsHtml += '<th>' + brfrEscapeHtml(textValue('create_new', 'Create New')) + '</th>';
                    customPostsHtml += '</tr></thead><tbody>';
                    $.each(group.posts, function(postIndex, postItem) {
                        var postKey = brfrEscapeAttr(postItem.map_key);
                        customPostsHtml += '<tr class="brfr-import-custom-post-item">';
                        customPostsHtml += '<td class="brfr-import-custom-post-name">' + brfrEscapeHtml(postItem.title_with_id) + '</td>';
                        customPostsHtml += '<td><label><input type="radio" name="brfr_custom_post_slug_strategy_' + postKey + '" value="update" checked></label></td>';
                        customPostsHtml += '<td><label><input type="radio" name="brfr_custom_post_slug_strategy_' + postKey + '" value="create_new"></label></td>';
                        customPostsHtml += '</tr>';
                    });
                    customPostsHtml += '</tbody></table>';
                }
                customPostsHtml += '</div>';
                customPostsHtml += '</div>';
            });
            customPostsHtml += '</div>';
            $('.brfr_import_links_list').append(customPostsHtml);
        }

        $('.brfr_import_links_step').show();
        $('.brfr_import_all').hide();
        $('.brfr_export_all').hide();
        $('.brfr_backup_all').hide();
        $('.brfr_import_export_form_plugin').hide();
    }

    function brfrConfirmImport() {
        var pluginSlug = $('.brfr_import_export_form select[name="plugin"]').val();
        var data = {
            action: 'brfr_confirm_import_settings',
            nonce: config().nonce,
            plugin: pluginSlug,
            import_token: brfrImportState.token,
            link_actions: JSON.stringify(brfrCollectLinkActions()),
            delete_existing_custom_posts: JSON.stringify(brfrCollectDeleteCustomPostTypes()),
            custom_post_slug_strategies: JSON.stringify(brfrCollectCustomPostSlugStrategies())
        };
        $('.brapf_import_loading').show();
        $('.brfr_import_export_form .brfr_import_send').prop('disabled', true);
        $('.brfr_import_confirm').prop('disabled', true);
        $.post(ajaxurl, data, function(response) {
            $('.brapf_import_loading').hide();
            if (response && response.success) {
                $('.brfr_import_export_form .brfr_import').val(response.data.message);
                brfrResetImportLinks();
                brfrGetExportData();
                brfrGetImportBackup();
            } else {
                var message = response && response.data && response.data.message ? response.data.message : textValue('import_failed', 'Import failed');
                $('.brfr_import_export_form .brfr_import').val(message);
            }
            setTimeout(brfrGetImportDataEnd, 5000);
        }, 'json').fail(function() {
            $('.brapf_import_loading').hide();
            $('.brfr_import_export_form .brfr_import').val(textValue('import_failed', 'Import failed'));
            setTimeout(brfrGetImportDataEnd, 5000);
        });
    }

    function brfrGetImportData() {
        var data = $('.brfr_import_export_form').serialize();
        $('.brapf_import_loading').show();
        $('.brfr_import_export_form .brfr_import').prop('disabled', true);
        $('.brfr_import_export_form .brfr_import_send').prop('disabled', true);
        $.post(ajaxurl, data, function(response) {
            $('.brapf_import_loading').hide();
            if (response && response.success) {
                brfrImportState.token = response.data.token || '';
                brfrImportState.links = response.data.links || [];
                brfrImportState.customPostTypes = response.data.custom_post_types || [];
                brfrImportState.hasCustomPosts = !!response.data.has_custom_posts;
                brfrImportState.customPosts = response.data.custom_posts || [];
                brfrImportState.customPostGroups = response.data.custom_post_groups || [];
                if (brfrImportState.links.length || brfrImportState.hasCustomPosts) {
                    brfrRenderImportLinks();
                } else {
                    brfrConfirmImport();
                }
            } else {
                var message = response && response.data && response.data.message ? response.data.message : textValue('incorrect_data', 'Incorrect data');
                $('.brfr_import_export_form .brfr_import').val(message);
                setTimeout(brfrGetImportDataEnd, 5000);
            }
        }, 'json').fail(function() {
            $('.brapf_import_loading').hide();
            $('.brfr_import_export_form .brfr_import').val(textValue('import_failed', 'Import failed'));
            setTimeout(brfrGetImportDataEnd, 5000);
        });
    }

    function brfrRestoreBackup() {
        var pluginSlug = $('.brfr_import_export_form select[name="plugin"]').val();
        var data = 'plugin=' + pluginSlug + '&' + $('.brfr_backup_form').serialize();
        $('.brfr_backup_form .fa-spin').show();
        $('.brfr_backup_form .brfr_backup_form_send').prop('disabled', true);
        $('.brfr_backup_form .brfr_backup_form_select select').prop('disabled', true);
        $.get(ajaxurl, data, function(data) {
            if (data === 'OK') {
                $('.brfr_backup_form .fa-check').show();
            } else {
                var html = '<span class="brfr_backup_form_error">' + data + '</span>';
                $('.brfr_backup_form_send').after($(html));
            }
            $('.brfr_backup_form .fa-spin').hide();
            brfrGetExportData();
            setTimeout(brfrRestoreBackupEnd, 5000);
        });
    }

    function brfrRestoreBackupEnd() {
        $('.brfr_backup_form_send .brfr_backup_form_error').remove();
        $('.brfr_backup_form .fa-check').hide();
        $('.brfr_backup_form .brfr_backup_form_send').prop('disabled', false);
        $('.brfr_backup_form .brfr_backup_form_select select').prop('disabled', false);
    }

    $(document).on('click', '.brfr_import_export_form .brfr_export', function() {
        var textarea = $(this)[0];
        textarea.focus();
        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);
    });

    $(document).on('change', '.brfr_import_export_form select[name="plugin"]', function() {
        brfrGetExportData();
        brfrGetImportBackup();
    });

    $(document).on('click', '.brfr_import_export_open', function() {
        $('.brfr_import_export_block').show();
        $(this).parent().hide();
        brfrGetExportData();
        brfrGetImportBackup();
    });

    $(document).on('submit', '.brfr_import_export_form', function(event) {
        event.preventDefault();
        if (!$('.brfr_import_export_form .brfr_import').is(':disabled')) {
            brfrResetImportLinks();
            brfrGetImportData();
        }
    });

    $(document).on('click', '.brfr_import_confirm', function() {
        brfrConfirmImport();
    });

    $(document).on('click', '.brfr_import_cancel', function() {
        brfrResetImportLinks();
        brfrGetImportDataEnd();
    });

    $(document).on('change', '.brfr-import-link-item input[type="radio"]', function() {
        var item = $(this).closest('.brfr-import-link-item');
        item.find('.brfr-import-link-media').toggle($(this).val() === 'media');
        item.find('.brfr-import-link-custom').toggle($(this).val() === 'custom');
    });

    $(document).on('change', '.brfr-delete-custom-post-type', function() {
        var group = $(this).closest('.brfr-import-custom-post-group');
        group.find('.brfr-import-custom-post-group-items').toggle(!$(this).is(':checked'));
    });

    $(document).on('click', '.brfr-import-link-media-select', function(event) {
        event.preventDefault();
        var wrapper = $(this).closest('.brfr-import-link-item');
        var customUploader = wp.media({
            title: textValue('select_image', 'Select image'),
            button: {
                text: textValue('use_this_image', 'Use this image')
            },
            multiple: false
        }).on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            wrapper.find('.brfr-import-link-media-url').val(attachment.url);
            wrapper.find('.brfr-import-link-media-value').text(attachment.url);
        }).open();
    });

    $(document).on('submit', '.brfr_backup_form', function(event) {
        event.preventDefault();
        if (!$('.brfr_backup_form .brfr_backup_form_send').is(':disabled')) {
            brfrRestoreBackup();
        }
    });
})(jQuery);
