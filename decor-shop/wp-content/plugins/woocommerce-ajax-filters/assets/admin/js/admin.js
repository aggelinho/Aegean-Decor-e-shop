var berocket_admin_filter_types = {
    tag:['checkbox','radio','select','color','image','tag_cloud'],
    product_cat:['checkbox','radio','select','color','image'],
    sale:['checkbox','radio','select'],
    custom_taxonomy:['checkbox','radio','select','color','image'],
    attribute:['checkbox','radio','select','color','image'],
    price:['slider'],
    filter_by:['checkbox','radio','select','color','image']
};
var berocket_admin_filter_types_by_attr = {
    checkbox:'<option value="checkbox">'+aapf_admin_text.checkbox_text+'</option>',
    radio:'<option value="radio">'+aapf_admin_text.radio_text+'</option>',
    select:'<option value="select">'+aapf_admin_text.select_text+'</option>',
    color:'<option value="color">'+aapf_admin_text.color_text+'</option>',
    image:'<option value="image">'+aapf_admin_text.image_text+'</option>',
    slider:'<option value="slider">'+aapf_admin_text.slider_text+'</option>',
    tag_cloud:'<option value="tag_cloud">'+aapf_admin_text.tag_cloud_text+'</option>'
};

(function ($) {
    $(document).ready(function () {
        $(document).on('click', '.br_aapf_settings_fa .berocket_upload_image', function(e) {
            e.preventDefault();
            $p = $(this);
            var custom_uploader = wp.media({
                title: 'Select custom Icon',
                button: {
                    text: 'Set Icon'
                },
                multiple: false 
            }).on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                $p.prevAll(".berocket_selected_image").html('<image src="'+attachment.url+'" alt="">').show();
                $p.prevAll(".berocket_image_value").val(attachment.url);
				$p.parent().find('.berocket_remove_image').show();
            }).open();
        });
        $(document).on('click', '.br_aapf_settings_fa .berocket_remove_image',function(event) {
            event.preventDefault();
            $(this).prevAll(".berocket_image_value").val("");
            $(this).prevAll(".berocket_selected_image").html("").hide();
			$(this).hide();
        });
        var berocket_fa_select_for = $('.berocket_fa_dark');
        $(document).on('click', '.br_aapf_settings_fa .berocket_select_fontawesome .berocket_select_fa',function(event) {
            event.preventDefault();
            berocket_fa_select_for = $(this);
            $('.berocket_fa_dark').not(':first').remove();
            var $html = $('<div class="berocket_select_fontawesome"></div>');
            $html.append($('.berocket_fa_dark'));
            var $html2 = $('<div class="br_aapf_settings_fa"></div>');
            $html2.append($html);
            $('body').children('.br_aapf_settings_fa').remove();
            $('body').append($html2);
            $('.berocket_fa_dark').show();
        });
        $(document).on('mouseenter', '.br_aapf_settings_fa .berocket_select_fontawesome .berocket_fa_hover', function() {
            var window_width = $(window).width();
            window_width = window_width / 2;
            var $this = $(this).parents('.berocket_fa_icon');
            if( $this.offset().left < window_width ) {
                $this.find('.berocket_fa_preview').css({left: '0', right: 'initial'});
                $this.find('.berocket_fa_preview span').appendTo($this.find('.berocket_fa_preview'));
            } else {
                $this.find('.berocket_fa_preview').css({left: 'initial', right: '0'});
                $this.find('.berocket_fa_preview .fa').appendTo($this.find('.berocket_fa_preview'));
            }
        });
        $(document).on('click', '.br_aapf_settings_fa .berocket_select_fontawesome .berocket_fa_hover',function(event) {
            event.preventDefault();
            var value = $(this).parents('.berocket_fa_icon').first().find('.berocket_fa_preview span').text();
            $(berocket_fa_select_for).parents('.berocket_select_fontawesome').find('.berocket_fa_value').val(value);
            $(berocket_fa_select_for).parents('.berocket_select_fontawesome').find('.berocket_selected_fa').html('<i class="fa '+value+'"></i>');
            $('.berocket_fa_dark').hide();
        });
        $(document).on('click', '.br_aapf_settings_fa .berocket_select_fontawesome .berocket_remove_fa',function(event) {
            event.preventDefault();
            $(this).parents('.berocket_select_fontawesome').find('.berocket_fa_value').val('');
            $(this).parents('.berocket_select_fontawesome').find('.berocket_selected_fa').html('');
        });
        $(document).on('keyup', '.br_aapf_settings_fa .berocket_select_fontawesome .berocket_fa_search', function() {
            var $parent = $(this).parents('.berocket_select_fontawesome').first();
            var value = $(this).val();
            value = value.replace(/\s+/g, '');
            value = value.toLowerCase();
            if( value.length >=1 ) {
                $parent.find('.berocket_fa_icon').hide();
                $parent.find('.berocket_fa_preview span:contains("'+value+'")').parents('.berocket_fa_icon').show();
            } else {
                $parent.find('.berocket_fa_icon').show();
            }
        });
        $(document).on('click', '.br_aapf_settings_fa .berocket_select_fontawesome .berocket_fa_dark',function(event) {
            event.preventDefault();
            $(this).hide();
        });
        $(document).on('click', '.br_aapf_settings_fa .berocket_select_fontawesome .berocket_fa_dark .berocket_fa_close',function(event) {
            event.preventDefault();
            $(this).parents('.berocket_fa_dark').hide();
        });
        $(document).on('click', '.br_aapf_settings_fa .berocket_select_fontawesome .berocket_fa_popup',function(event) {
            event.preventDefault();
            event.stopPropagation();
        });
        $(document).on('change', '.berocket_aapf_widget_sc, .berocket_aapf_style_sb_sc, .berocket_aapf_sb_attributes_sc, .berocket_aapf_childs_sc, .berocket_aapf_include_list_sc', function() {
            $(this).data('sc_change', '1');
        });
        $('.br_colorpicker_field').each(function (i,o){
            if( typeof($(o).colpick) != 'undefined' ) {
                $(o).css('backgroundColor', '#'+$(o).data('color'));
                $(o).colpick({
                    layout: 'hex',
                    submit: 0,
                    color: '#'+$(o).data('color'),
                    onChange: function(hsb,hex,rgb,el,bySetColor) {
                        $(el).removeClass('colorpicker_removed');
                        $(el).css('backgroundColor', '#'+hex).next().val(hex).trigger('change');
                    }
                })
            }
        });

        $(document).on('click', '.theme_default', function (event) {
            event.preventDefault();
            $(this).prev().prev().css('backgroundColor', '#000000').colpickSetColor('#000000');
            $(this).prev().val('');
        });

        $(document).on('click', '.all_theme_default', function (event) {
            event.preventDefault();
            $table = $(this).parents('table');
            $table.find('.br_colorpicker_field').css('backgroundColor', '#000000').colpickSetColor('#000000');
            $table.find('.br_colorpicker_field').next().val('');
            $table.find('select').val("");
            $table.find('input[type=text]').val("");
        });
        br_widget_set();
        $(document).on('click', '.berocket_remove_ranges',function(event) {
            event.preventDefault();
            $(this).parents('.berocket_ranges').remove();
        });
        $(document).on('change', '.berocket_search_link_select', function() {
            var $parent = $(this).parents('.berocket_aapf_admin_search_box');
            $parent.find('.berocket_search_link').hide();
            $parent.find('.berocket_search_link_'+$(this).val()).show();
        });
        $(document).on('change', '.berocket_seo_friendly_urls', berocket_change_seo_friendly_urls);
        $(document).on('change', '.berocket_nice_url', berocket_change_seo_friendly_urls);
        $(document).on('change', '.berocket_seo_meta_title', berocket_change_seo_meta_title);
        $(document).on('change', '.berocket_use_links_filters', berocket_change_use_links_filters);
        berocket_change_seo_friendly_urls();
        berocket_change_seo_meta_title();
        berocket_change_use_links_filters();
        var bapf_show_hide_chevron = function($element, check) {
            var data = $element.data();
            if( typeof(data.opened) == 'undefined' ) {
                data.opened = 'fa-chevron-up';
            }
            if( typeof(data.closed) == 'undefined' ) {
                data.closed = 'fa-chevron-down';
            }
            $element.removeClass(data.opened).removeClass(data.closed);
            if( check ) {
                $element.addClass(data.opened);
            } else {
                $element.addClass(data.closed);
            }
            return check;
        }
        $(document).on('click', '.bapf_incompatibility_fixes_header', function() {
            if( bapf_show_hide_chevron( $(this).find('.fa'), $('.bapf_incompatibility_fixes_hide').length ) ) {
                $('.bapf_incompatibility_fixes_hide').removeClass('bapf_incompatibility_fixes_hide');
            } else {
                $('.bapf_incompatibility_fixes').addClass('bapf_incompatibility_fixes_hide');
            }
        });
        $(document).on('click', '.bapf_tools_header', function() {
            if( bapf_show_hide_chevron( $(this).find('.fa'), $('.bapf_tools_fields_hide').length ) ) {
                $('.bapf_tools_fields').removeClass('bapf_tools_fields_hide');
            } else {
                $('.bapf_tools_fields').addClass('bapf_tools_fields_hide');
            }
        });
        $(document).on('click', '.bapf_javascript_header', function() {
            if( bapf_show_hide_chevron( $(this).find('.fa'), $('.bapf_javascript_fields_hide').length ) ) {
                $('.bapf_javascript_fields').removeClass('bapf_javascript_fields_hide');
            } else {
                $('.bapf_javascript_fields').addClass('bapf_javascript_fields_hide');
            }
        });
        $(document).on('click', 'a.turn_on_advanced', function(event) {
            event.preventDefault();
            $parent = $(this).parents('.berocket_sbs_step');
            $parent.find('.show_on_advanced').slideDown(300);
            //$("html, body").stop().animate({scrollTop:$parent.offset().top-20}, 300);
            $(this).hide(300);
        });
        function berocket_hide_seo_per_page_addon() {
            if( jQuery('.berocket_addon_label .seo_per_page').parents('.berocket_addon_label').find('.berocket_addon_is_active').length 
            && ! jQuery('.berocket_addon_label .seo_per_page').parents('.berocket_addon_label').find('.berocket_addon_is_active').prop('checked') ) {
                jQuery('.berocket_addon_label .seo_per_page').parents('.berocket_addon_label').hide();
            }
        }
        berocket_hide_seo_per_page_addon();
    })
})(jQuery);
function berocket_change_seo_friendly_urls() {
    if( jQuery('.berocket_seo_friendly_urls').prop('checked') ) {
        jQuery('.berocket_use_slug_in_url').parents('tr').first().show();
        jQuery('.berocket_use_links_filters').parents('tr').first().show();
        jQuery('.berocket_nice_url').parents('tr').first().show();
        jQuery('.berocket_uri_decode').parents('tr').first().show();
    } else {
        jQuery('.berocket_use_slug_in_url').prop('checked', false);
        jQuery('.berocket_nice_url').prop('checked', false);
        jQuery('.berocket_use_links_filters').prop('checked', false);
        jQuery('.berocket_use_slug_in_url').parents('tr').first().hide();
        jQuery('.berocket_use_links_filters').parents('tr').first().hide();
        jQuery('.berocket_nice_url').parents('tr').first().hide();
        jQuery('.berocket_uri_decode').parents('tr').first().hide();
    }
    if( jQuery('.berocket_seo_friendly_urls').prop('checked') && jQuery('.berocket_nice_url').prop('checked') ) {
        jQuery('.berocket_canonicalization').parents('tr').first().show();
    } else {
        jQuery('.berocket_canonicalization').prop('checked', false);
        jQuery('.berocket_canonicalization').parents('tr').first().hide();
    }
}
function  berocket_change_seo_meta_title() {
    if( jQuery('.berocket_seo_meta_title').prop('checked') ) {
        jQuery('.berocket_seo_meta_title_elements').show();
    } else {
        jQuery('.berocket_seo_meta_title_elements').hide();
    }
}
function  berocket_change_use_links_filters() {
    if( jQuery('.berocket_use_links_filters').prop('checked') ) {
        jQuery('.berocket_use_noindex').show();
        jQuery('.berocket_use_nofollow').show();
    } else {
        jQuery('.berocket_use_noindex').hide();
        jQuery('.berocket_use_nofollow').hide();
    }
}
var br_widget_setted = false;
function br_widget_set() {
    if ( br_widget_setted !== false ) {
        clearTimeout( br_widget_setted );
    }
    br_widget_setted = setTimeout( function () {
        if( typeof(brjsf) != 'undefined' && jQuery.isFunction(brjsf) && jQuery.isFunction(brjsf_accordion) ) {
            brjsf(jQuery( ".br_select_menu_left" ));
            brjsf(jQuery( ".br_select_menu_right" ));
            brjsf_accordion(jQuery( ".br_accordion" ));
            jQuery('.berocket_aapf_widget_admin_widget_type_select').parents('.editwidget').first().css('width', 'initial');
        } else {
            br_widget_set();
        }
        br_widget_setted = false;
    }, 400);
}
(function ($){
    $(document).ready( function () {
        $(document).on('click', '.berocket_aapf_font_awesome_icon_select',function(event) {
            event.preventDefault();
            $(this).next('.berocket_aapf_select_icon').show();
        });
        $(document).on('click', '.berocket_aapf_select_icon',function(event) {
            event.preventDefault();
            $(this).hide();
        });
        $(document).on('click', '.berocket_aapf_select_icon div p i.fa',function(event) {
            event.preventDefault();
            $(this).parents('.berocket_aapf_select_icon').hide();
        });
        $(document).on('click', '.berocket_aapf_select_icon div',function(event) {
            event.preventDefault();
            event.stopPropagation()
        });
        $(document).on('click', '.berocket_aapf_select_icon label',function(event) {
            event.preventDefault();
            $(this).parents('.berocket_aapf_select_icon').prevAll(".berocket_aapf_icon_text_value").val($(this).find('span').data('value'));
            $(this).parents('.berocket_aapf_select_icon').prevAll(".berocket_aapf_selected_icon_show").html('<i class="fa '+$(this).find('span').data('value')+'"></i>');
            $(this).parents('.berocket_aapf_select_icon').hide();
        });
        $(document).on('click', '.berocket_aapf_upload_icon', function(e) {
            e.preventDefault();
            $p = $(this);
            var custom_uploader = wp.media({
                title: 'Select custom Icon',
                button: {
                    text: 'Set Icon'
                },
                multiple: false 
            }).on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                $p.prevAll(".berocket_aapf_selected_icon_show").html('<i class="fa"><image src="'+attachment.url+'" alt=""></i>');
                $p.prevAll(".berocket_aapf_icon_text_value").val(attachment.url);
            }).open();
        });
        $(document).on('click', '.berocket_aapf_remove_icon',function(event) {
            event.preventDefault();
            $(this).prevAll(".berocket_aapf_icon_text_value").val("");
            $(this).prevAll(".berocket_aapf_selected_icon_show").html("");
        });
        $(document).on('change', '.berocket_new_widget_selectbox', function() {
            var edit = $(this).find('option:selected').data('edit');
            if( typeof(edit) != 'undefined' && edit ) {
                $(this).next('.berocket_aapf_edit_post_link').attr('href', edit).show();
            } else {
                $(this).next('.berocket_aapf_edit_post_link').hide();
            }
        });
//Filters Group
        jQuery(document).on('click', '.berocket_add_filter_to_group', function(event) {
            event.preventDefault();
            if( ! jQuery('.berocket_filter_added_'+jQuery('.berocket_filter_list').val()).length ) {
                var html = ''+
                '<li class="berocket_filter_added_'+jQuery('.berocket_filter_list').val()+'">'+
                    '<fa class="fa fa-bars"></fa>'+
                    '<input type="hidden" name="'+jQuery('.berocket_filter_added_list').data('name')+'" '+
                            'value="'+jQuery('.berocket_filter_list').val()+'">'+
                        '<div class="filter_name">'+
                            jQuery('.berocket_filter_list').find(':selected').data('name')+
                            ' <small>ID:'+jQuery('.berocket_filter_list').val()+'</small>'+
                        '</div>'+
                        '<div class="berocket_filter_added_list_actions">'+
                            '<div class="berocket_hidden_clickable_options">'+
                                '<label for="filters_data_'+jQuery('.berocket_filter_list').val()+'_width">Width</label> '+
                                '<input id="filters_data_'+jQuery('.berocket_filter_list').val()+'_width" type="text"'+
                                    ' name="br_filters_group[filters_data]['+jQuery('.berocket_filter_list').val()+'][width]"'+
                                    ' value="" placeholder="100%"/>'+
                            '</div>'+
                        '<a class="berocket_edit_filter fas fa-pencil-alt" target="_blank"'+
                            ' href="'+jQuery('.berocket_filter_added_list').data('url')+'?post='+
                                    jQuery('.berocket_filter_list').val()+'&action=edit"></a>'+
                            '<i class="fa fa-times"></i>'+
                    '</div>'+
                '</li>';
                jQuery('.berocket_filter_added_list').append(jQuery(html));
            } else {
                jQuery('.berocket_filter_added_'+jQuery('.berocket_filter_list').val()).css('background-color', '#ee3333').clearQueue().animate({backgroundColor:'#f7f7f7'}, 1000);
            }
        });
        jQuery(document).on('click', '.berocket_filter_added_list .fa-times', function(event) {
            jQuery(this).parents('li').first().remove();
        });
        jQuery('#post').on('submit', function(e) {
            var copy_val = $('[name="berocket_copy_from_custom_post"]').val();
            if( ! copy_val && jQuery('.berocket_add_filter_to_group').length && jQuery('.berocket_filter_added_list input[name="br_filters_group[filters][]"]').length == 0 ) {
                e.preventDefault();
                jQuery(document).trigger('braapf_group_required_filters');
            }
        });
        if(jQuery( ".berocket_filter_added_list" ).length && typeof(jQuery( ".berocket_filter_added_list" ).sortable) == 'function') {
            jQuery( ".berocket_filter_added_list" ).sortable({axis:"y", handle:".fa-bars", placeholder: "berocket_sortable_space"});
        }
    });

    jQuery(document).on('click', '#settings .berocket_sbs_step div.device:not(.active) ul', function () {
        jQuery(this).css({'left': '-2px'});
        jQuery(this).parent().addClass('active');
    });
    jQuery(document).on('click', '#settings .berocket_sbs_step div.device.active li', function () {
        jQuery(this).parent().find('.active').removeClass('active');
        jQuery(this).addClass('active');

        jQuery(this).parent().animate({'left': -2-parseInt(jQuery(this).position().left)}, 250);

        $device_selection = jQuery(this).closest('label').next();
        $top = jQuery(this).data('device') == 'desktop' ? 0 : jQuery(this).data('device') == 'tablet' ? 28 : 56;
        $device_selection.find('> div').animate({'top': -$top}, 250);
        jQuery(this).parent().parent().removeClass('active');
    });
    jQuery(document).on('click', '#settings .berocket_sbs_step .braapf_style_customization h4.dynamic', function () {
        jQuery(this).parent().find('h4.active').removeClass('active');
        jQuery(this).addClass('active');
    });
    jQuery(document).on('click', '#settings .berocket_sbs_step .braapf_style_customization_status .braapf_form_group_elements', function (e) {
        e.preventDefault();
    });
    jQuery(document).on('click', '#settings .berocket_sbs_step .braapf_style_customization_status .braapf_form_group_elements:not(.selected)', function () {
        jQuery(this).parent().find('.braapf_form_group_elements').removeClass('selected');
        jQuery(this).addClass('selected');

        if ( jQuery(this).parent().next().is('.braapf_style_customization_status_container') )
            $braapf_style_customization_status_container = jQuery(this).parent().next();
        else
            $braapf_style_customization_status_container = jQuery(this).parent().next().find('.braapf_style_customization_status_container');
        $braapf_style_customization_status_container.find('> div').hide().end().find('.braapf_style_customization_status_container_'+jQuery(this).data('status')).show();
    });
})(jQuery);