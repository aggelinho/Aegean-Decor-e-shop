<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 class="woo-conditional-payments-heading">
	<a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_conditional_payments' ); ?>"><?php _e( 'Conditions', 'woo-conditional-payments' ); ?></a>
	 &gt; 
	<?php echo $ruleset->get_title(); ?>
</h2>

<table class="form-table woo-conditional-payments-ruleset-settings">
	<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Enable / Disable', 'woo-conditional-payments' ); ?>
				</label>
			</th>
			<td class="forminp">
				<input type="checkbox" name="ruleset_enabled" id="ruleset_enabled" value="1" <?php checked( $ruleset->get_enabled() ); ?> />
				<label for="ruleset_enabled"><?php esc_html_e( 'Enable ruleset', 'woo-conditional-payments' ); ?></label>
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Title', 'woo-conditional-payments' ); ?>
					<?php echo wc_help_tip( __( 'This is the name of the ruleset for your reference.', 'woo-conditional-payments' ) ); ?>
				</label>
			</th>
			<td class="forminp">
				<input type="text" name="ruleset_name" id="ruleset_name" value="<?php echo esc_attr( $ruleset->get_title( 'edit' ) ); ?>" placeholder="<?php esc_attr_e( 'Title', 'woo-conditional-payments' ); ?>" />
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Conditions', 'woo-conditional-payments' ); ?>
					<?php echo wc_help_tip( __( 'The following conditions define whether or not actions are run.', 'woo-conditional-payments' ) ); ?>
				</label>
			</th>
			<td class="">
				<table
					class="woo-conditional-payments-conditions wcp-table widefat"
					data-operators="<?php echo htmlspecialchars( json_encode( woo_conditional_payments_operators() ), ENT_QUOTES, 'UTF-8' ); ?>"
					data-selected-products="<?php echo htmlspecialchars( json_encode( $ruleset->get_products() ), ENT_QUOTES, 'UTF-8' ); ?>"
					data-selected-coupons="<?php echo htmlspecialchars( json_encode( $ruleset->get_coupons() ), ENT_QUOTES, 'UTF-8' ); ?>"
					data-selected-tags="<?php echo htmlspecialchars( json_encode( $ruleset->get_tags() ), ENT_QUOTES, 'UTF-8' ); ?>"
					data-selected-brands="<?php echo htmlspecialchars( json_encode( $ruleset->get_brands() ), ENT_QUOTES, 'UTF-8' ); ?>"
					data-conditions="<?php echo htmlspecialchars( json_encode( $ruleset->get_conditions() ), ENT_QUOTES, 'UTF-8' ); ?>"
				>
					<tbody class="woo-conditional-payments-condition-rows">
					</tbody>
					<tfoot>
						<tr>
							<td colspan="4">
								<button type="button" class="button" id="wcp-add-condition"><?php _e( 'Add Condition', 'woo-conditional-payments' ); ?></button>
								<select name="wcp_operator">
									<option value="and" <?php selected( 'and', $ruleset->get_conditions_operator() ); ?>><?php _e( 'All conditions have to pass (AND)', 'woo-conditional-payments' ); ?></option>
									<option value="or" <?php selected( 'or', $ruleset->get_conditions_operator() ); ?>><?php _e( 'One condition has to pass (OR)', 'woo-conditional-payments' ); ?></option>
								</select>
							</td>
						</tr>
					</tfoot>
				</table>
				<?php if ( ! class_exists( 'Woo_Conditional_Payments_Pro' ) ) { ?>
					<p class="description conditions-desc">
						<?php printf( __( 'More conditions and actions available in the Pro version. <a href="%s" target="_blank">Check out all the differences &raquo;</a>', 'woo-conditional-payments' ), 'https://wptrio.com/guide/woocommerce-conditional-payments-free-vs-pro/' ); ?>
					</p>
				<?php } ?>
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Actions', 'woo-conditional-payments' ); ?>
					<?php echo wc_help_tip( __( 'Actions which are run if all conditions pass.', 'woo-conditional-payments' ) ); ?>
				</label>
			</th>
			<td class="">
				<table
					class="woo-conditional-payments-actions wcp-table widefat"
					data-actions="<?php echo htmlspecialchars( json_encode( $ruleset->get_actions() ), ENT_QUOTES, 'UTF-8' ); ?>"
				>
					<tbody class="woo-conditional-payments-action-rows">
					</tbody>
					<tfoot>
						<tr>
							<td colspan="4">
								<button type="button" class="button" id="wcp-add-action"><?php _e( 'Add Action', 'woo-conditional-payments' ); ?></button>
							</td>
						</tr>
					</tfoot>
				</table>

				<?php if ( wcp_wpml_has_strings() ) { ?>
					<p><small><a href="<?php echo admin_url( 'admin.php?page=wpml-string-translation%2Fmenu%2Fstring-translation.php&context=Conditional+Payments+for+WooCommerce' ); ?>" target="_blank"><?php esc_html_e( 'Translate strings with WPML', 'woo-conditional-payments' ); ?> &raquo;</a></small></p>
				<?php } ?>
			</td>
		</tr>
		<?php if ( ! class_exists( 'Woo_Conditional_Payments_Pro' ) ) { ?>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label>
						<?php esc_html_e( 'Pro features', 'woo-conditional-payments' ); ?>
					</label>
				</th>
				<td class="forminp">
					<input type="checkbox" name="wcp_pro_features" id="wcp_pro_features" value="1" <?php checked( get_option( 'wcp_pro_features', true ) ); ?> />
					<label for="wcp_pro_features"><?php echo sprintf( __( 'Display features available in <a href="%s" target="_blank">Pro</a>', 'woo-conditional-payments' ), 'https://wptrio.com/products/conditional-payments/' ); ?></label>
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>

<p class="submit">
	<button type="submit" name="submit" id="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Save changes', 'woo-conditional-payments' ); ?>"><?php esc_html_e( 'Save changes', 'woo-conditional-payments' ); ?></button>

	<input type="hidden" value="<?php echo $ruleset->get_id(); ?>" name="ruleset_id" />
	<input type="hidden" value="1" name="save" />

	<?php wp_nonce_field( 'woocommerce-settings' ); ?>
</p>

<script type="text/html" id="tmpl-wcp_row_template">
	<tr valign="top" class="condition_row">
		<td class="wcp-condition">
			<div class="wcp-condition-inputs">
				<div>
					<select name="wcp_conditions[{{data.index}}][type]" class="wcp_condition_type_select">
						<option value=""><?php echo wcp_esc_html( __( '- Select condition - ', 'woo-conditional-payments' ) ); ?></option>

						<?php foreach ( woo_conditional_payments_filter_groups() as $filter_group ) { ?>
							<optgroup label="<?php echo esc_attr( $filter_group['title'] ); ?>">
								<?php foreach ( $filter_group['filters'] as $key => $filter ) { ?>
									<option
										value="<?php echo esc_attr( $key ); ?>"
										<?php echo ( isset( $filter['pro'] ) && $filter['pro'] ) ? 'disabled' : ''; ?>
										data-operators="<?php echo htmlspecialchars( json_encode( $filter['operators'] ), ENT_QUOTES, 'UTF-8'); ?>"
										<# if ( data.type == '<?php echo esc_js( $key ); ?>' ) { #>selected<# } #>
									>
										<?php echo wcp_esc_html( wcp_get_control_title( $filter ) ); ?>
									</option>
								<?php } ?>
							</optgroup>
						<?php } ?>
					</select>
				</div>

				<div class="value_input wcp_product_meta_key_input">
					<select class="wcp-product-meta-field-search" name="wcp_conditions[{{data.index}}][meta_key]" data-placeholder="<?php esc_attr_e( 'Meta key', 'woo-conditional-payments' ); ?>">
						<# if ( data.meta_key ) { #>
							<option selected value="{{data.meta_key}}">{{data.meta_key}}</option>
						<# } #>
					</select>
				</div>
			</div>
		</td>
		<td class="wcp-operator">
			<div class="wcp-operator-inputs">
				<div class="value_input wcp_product_measurement_mode_input">
					<select name="wcp_conditions[{{data.index}}][product_measurement_mode]" class="">
						<option value="highest" <# if ( data.product_measurement_mode && data.product_measurement_mode == 'highest' ) { #>selected<# } #>><?php esc_html_e( 'highest', 'woo-conditional-payments' ); ?></option>
						<option value="lowest" <# if ( data.product_measurement_mode && data.product_measurement_mode == 'lowest' ) { #>selected<# } #>><?php esc_html_e( 'lowest', 'woo-conditional-payments' ); ?></option>
						<option value="sum" <# if ( data.product_measurement_mode && data.product_measurement_mode == 'sum' ) { #>selected<# } #>><?php esc_html_e( 'total sum', 'woo-conditional-payments' ); ?></option>
					</select>
				</div>

				<div>
					<select class="wcp_operator_select" name="wcp_conditions[{{data.index}}][operator]">
						<?php foreach ( woo_conditional_payments_operators() as $key => $operator ) { ?>
							<option
								value="<?php echo esc_attr( $key ); ?>"
								class="wcp-operator wcp-operator-<?php echo esc_attr( $key ); ?>"
								<# if ( data.operator == '<?php echo esc_js( $key ); ?>' ) { #>selected<# } #>
							>
								<?php echo wcp_esc_html( $operator ); ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
		</td>
		<td class="wcp-values">
			<input class="input-text value_input regular-input wcp_text_value_input" type="text" name="wcp_conditions[{{data.index}}][value]" value="{{data.value}}" />

			<div class="value_input wcp_postcode_value_input">
				<textarea name="wcp_conditions[{{data.index}}][postcodes]" class="" placeholder="<?php esc_attr_e( 'List 1 postcode per line', 'woocommerce' ); ?>">{{ data.postcodes }}</textarea>

				<div class="description"><?php esc_html_e( 'Postcodes containing wildcards (e.g. CB23*) or fully numeric ranges (e.g. <code>90210...99000</code>) are also supported.', 'woo-conditional-payments' ); ?></div>
			</div>

			<div class="value_input wcp_textarea_value_input">
				<textarea name="wcp_conditions[{{data.index}}][textarea]" class="" placeholder="<?php esc_attr_e( 'List 1 value per line', 'woocommerce' ); ?>">{{ data.textarea }}</textarea>
			</div>

			<div class="value_input wcp_ip_address_value_input">
				<textarea name="wcp_conditions[{{data.index}}][ip_addresses]" class="" placeholder="<?php esc_attr_e( 'List 1 IP address per line', 'woocommerce' ); ?>">{{ data.ip_addresses }}</textarea>

				<div class="description"><?php esc_html_e( 'IP addresses containing wildcards (e.g. 127.0.0.*) or ranges (e.g. 127.0.0.0-127.255.255.255) are also supported.', 'woo-conditional-payments' ); ?></div>
			</div>

			<div class="value_input wcp_billing_email_value_input">
				<textarea name="wcp_conditions[{{data.index}}][emails]" class="" placeholder="<?php esc_attr_e( 'List 1 email address per line', 'woocommerce' ); ?>">{{ data.emails }}</textarea>
			</div>

			<div class="value_input wcp_billing_phone_value_input">
				<textarea name="wcp_conditions[{{data.index}}][phones]" class="" placeholder="<?php esc_attr_e( 'List 1 phone number per line', 'woo-conditional-payments' ); ?>">{{ data.phones }}</textarea>
			</div>

			<div class="value_input wcp_city_value_input">
				<textarea name="wcp_conditions[{{data.index}}][cities]" class="" placeholder="<?php esc_attr_e( 'List 1 city per line', 'woo-conditional-payments' ); ?>">{{ data.cities }}</textarea>
			</div>

			<div class="value_input wcp_subtotal_value_input">
				<input type="checkbox" id="wcp-subtotal-includes-coupons-{{data.index}}" value="1" name="wcp_conditions[{{data.index}}][subtotal_includes_coupons]" <# if ( data.subtotal_includes_coupons ) { #>checked<# } #> />
				<label for="wcp-subtotal-includes-coupons-{{data.index}}"><?php esc_html_e( 'Subtotal includes coupons', 'woo-conditional-payments' ); ?></label>
			</div>

			<div class="value_input wcp_orders_value_input">
				<div class="wcp_orders_status_input">
					<select name="wcp_conditions[{{data.index}}][orders_status][]" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Order statuses', 'woo-conditional-payments' ); ?>">
						<?php foreach( wcp_order_status_options() as $value => $label ) { ?>
							<option
								value="<?php echo esc_attr( $value ); ?>"
								<# if ( data.orders_status && jQuery.inArray( '<?php echo esc_js( $value ); ?>', data.orders_status ) !== -1 ) { #>
									selected
								<# } #>
							>
								<?php echo wcp_esc_html( $label ); ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div>
					<input type="checkbox" id="wcp-orders-match-guests-by-email-{{data.index}}" value="1" name="wcp_conditions[{{data.index}}][orders_match_guests_by_email]" <# if ( data.orders_match_guests_by_email ) { #>checked<# } #> />
					<label for="wcp-orders-match-guests-by-email-{{data.index}}"><?php esc_html_e( 'Match guests by email', 'woo-conditional-payments' ); ?></label>
				</div>
			</div>

			<div class="value_input wcp_product_value_input">
				<select class="wc-product-search" multiple="multiple" name="wcp_conditions[{{data.index}}][product_ids][]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="wcp_json_search_products">
					<# if ( data.selected_products && data.selected_products.length > 0 ) { #>
						<# _.each(data.selected_products, function(product) { #>
							<option value="{{ product['id'] }}" selected>{{ product['title'] }}</option>
						<# }) #>
					<# } #>
				</select>
			</div>

			<div class="value_input wcp_product_tag_value_input">
				<select class="wcp-tag-search" multiple="multiple" name="wcp_conditions[{{data.index}}][product_tags][]" data-placeholder="<?php esc_attr_e( 'Search for tags', 'woo-conditional-payments' ); ?>">
					<# if ( data.selected_tags && data.selected_tags.length > 0 ) { #>
						<# _.each(data.selected_tags, function(tag) { #>
							<option value="{{ tag['id'] }}" selected>{{ tag['title'] }}</option>
						<# }) #>
					<# } #>
				</select>
			</div>

			<div class="value_input wcp_product_brand_value_input">
				<select class="wcp-brand-search" multiple="multiple" name="wcp_conditions[{{data.index}}][product_brands][]" data-placeholder="<?php esc_attr_e( 'Search for brands', 'woo-conditional-payments' ); ?>">
					<# if ( data.selected_brands && data.selected_brands.length > 0 ) { #>
						<# _.each(data.selected_brands, function(brand) { #>
							<option value="{{ brand['id'] }}" selected>{{ brand['title'] }}</option>
						<# }) #>
					<# } #>
				</select>
			</div>

			<div class="value_input wcp_shipping_method_value_input">
				<select class="wc-enhanced-select" name="wcp_conditions[{{data.index}}][shipping_method_ids][]" class="select" multiple>
					<?php foreach ( woo_conditional_payments_get_shipping_method_options() as $zone ) { ?>
						<optgroup label="<?php echo esc_attr( $zone['title'] ); ?>">
							<?php foreach ( $zone['methods'] as $method ) { ?>
								<option
									value="<?php echo $method['combined_id']; ?>"
									<# if ( data.shipping_method_ids && jQuery.inArray( '<?php echo $method['combined_id']; ?>', data.shipping_method_ids ) !== -1 ) { #>
										selected
									<# } #>
								>
									<?php echo wcp_esc_html( $method['title'] ); ?>
								</option>
							<?php } ?>
						</optgroup>
					<?php } ?>
				</select>

				<div class="wcp-match-by-name">
					<textarea name="wcp_conditions[{{data.index}}][shipping_method_name_match]">{{ data.shipping_method_name_match }}</textarea>
					<div class="wcp-desc"><?php esc_html_e( 'Match shipping methods by name. Wildcards (e.g. DHL Express*) are also supported. Enter one name per line.', 'woo-conditional-payments' ); ?></div>
				</div>
			</div>

			<div class="value_input wcp_category_value_input">
				<select name="wcp_conditions[{{data.index}}][product_cat_ids][]" multiple class="select wc-enhanced-select">
					<?php foreach ( woo_conditional_payments_get_category_options() as $key => $label) { ?>
						<option value="<?php echo esc_attr( $key ); ?>" <# if ( data.product_cat_ids && data.product_cat_ids.indexOf("<?php echo esc_js( $key ); ?>") !== -1 ) { #>selected<# } #>><?php echo wcp_esc_html( $label ); ?></option>
					<?php } ?>
				</select>
			</div>

			<div class="value_input wcp_product_type_value_input">
				<select name="wcp_conditions[{{data.index}}][product_types][]" multiple class="select wc-enhanced-select">
					<?php foreach ( wcp_get_product_type_options() as $key => $label) { ?>
						<option value="<?php echo esc_attr( $key ); ?>" <# if ( data.product_types && data.product_types.indexOf("<?php echo esc_js( $key ); ?>") !== -1 ) { #>selected<# } #>><?php echo wcp_esc_html( $label ); ?></option>
					<?php } ?>
				</select>
			</div>

			<div class="value_input wcp_stock_status_value_input">
				<select name="wcp_conditions[{{data.index}}][stock_status][]" multiple class="select wc-enhanced-select">
					<?php foreach ( wcp_get_stock_status_options() as $key => $label) { ?>
						<option value="<?php echo esc_attr( $key ); ?>" <# if ( data.stock_status && data.stock_status.indexOf("<?php echo esc_js( $key ); ?>") !== -1 ) { #>selected<# } #>><?php echo wcp_esc_html( $label ); ?></option>
					<?php } ?>
				</select>
			</div>

			<div class="value_input wcp_shipping_class_value_input">
				<select name="wcp_conditions[{{data.index}}][shipping_class_ids][]" multiple class="select wc-enhanced-select">
					<?php foreach ( woo_conditional_payments_get_shipping_class_options() as $key => $label ) { ?>
						<option value="<?php echo esc_attr( $key ); ?>" <# if ( data.shipping_class_ids && data.shipping_class_ids.indexOf("<?php echo esc_js( $key ); ?>") !== -1 ) { #>selected<# } #>><?php echo wcp_esc_html( $label ); ?></option>
					<?php } ?>
				</select>
			</div>

			<div class="value_input wcp_coupon_value_input">
				<select class="wcp-coupon-search" multiple="multiple" name="wcp_conditions[{{data.index}}][coupon_ids][]" data-placeholder="<?php esc_attr_e( 'Search for coupons', 'woo-conditional-payments' ); ?>">
					<# if ( data.selected_coupons && data.selected_coupons.length > 0 ) { #>
						<# _.each(data.selected_coupons, function(coupon) { #>
							<option value="{{ coupon['id'] }}" selected>{{ coupon['title'] }}</option>
						<# }) #>
					<# } #>
				</select>
			</div>

			<div class="value_input wcp_user_role_value_input">
				<select class="wc-enhanced-select" name="wcp_conditions[{{data.index}}][user_roles][]" class="select" multiple>
					<?php foreach ( woo_conditional_payments_role_options() as $role_id => $name ) { ?>
						<option
							value="<?php echo esc_attr( $role_id ); ?>"
							<# if ( data.user_roles && jQuery.inArray( '<?php echo esc_js( $role_id ); ?>', data.user_roles ) !== -1 ) { #>
								selected
							<# } #>
						>
							<?php echo wcp_esc_html( $name ); ?>
						</option>
					<?php } ?>
				</select>
			</div>

			<?php if ( defined( 'GROUPS_CORE_VERSION' ) ) { ?>
				<div class="value_input wcp_groups_value_input">
					<select class="wc-enhanced-select" name="wcp_conditions[{{data.index}}][user_groups][]" class="select" multiple>
						<?php foreach ( woo_conditional_payments_groups_options() as $group_id => $name ) { ?>
							<option
								value="<?php echo esc_attr( $group_id ); ?>"
								<# if ( data.user_groups && jQuery.inArray( '<?php echo esc_js( $group_id ); ?>', data.user_groups ) !== -1 ) { #>
									selected
								<# } #>
							>
								<?php echo wcp_esc_html( $name ); ?>
							</option>
						<?php } ?>
					</select>
				</div>
			<?php } ?>

			<?php if ( function_exists( 'pll_the_languages' ) ) { ?>
				<div class="value_input wcp_lang_polylang_value_input">
					<select class="wc-enhanced-select" name="wcp_conditions[{{data.index}}][lang_polylang][]" class="select" multiple>
						<?php foreach ( woo_conditional_payments_polylang_options() as $lang_id => $lang ) { ?>
							<option
								value="<?php echo esc_attr( $lang_id ); ?>"
								<# if ( data.lang_polylang && jQuery.inArray( '<?php echo esc_js( $lang_id ); ?>', data.lang_polylang ) !== -1 ) { #>
									selected
								<# } #>
							>
								<?php echo wcp_esc_html( $lang ); ?>
							</option>
						<?php } ?>
					</select>
				</div>
			<?php } ?>

			<?php if ( function_exists( 'icl_object_id' ) ) { ?>
				<div class="value_input wcp_lang_wpml_value_input">
					<select class="wc-enhanced-select" name="wcp_conditions[{{data.index}}][lang_wpml][]" class="select" multiple>
						<?php foreach ( woo_conditional_payments_wpml_options() as $lang_id => $lang ) { ?>
							<option
								value="<?php echo esc_attr( $lang_id ); ?>"
								<# if ( data.lang_wpml && jQuery.inArray( '<?php echo esc_js( $lang_id ); ?>', data.lang_wpml ) !== -1 ) { #>
									selected
								<# } #>
							>
								<?php echo wcp_esc_html( $lang ); ?>
							</option>
						<?php } ?>
					</select>
				</div>
			<?php } ?>

			<div class="value_input wcp_state_value_input">
				<select class="wc-enhanced-select" name="wcp_conditions[{{data.index}}][states][]" class="select" multiple>
					<?php foreach ( woo_conditional_payments_state_options() as $country_id => $states ) { ?>
						<optgroup label="<?php echo esc_attr( $states['country'] ); ?>">
							<?php foreach ( $states['states'] as $state_id => $state ) { ?>
								<option
									value="<?php echo esc_attr( "{$country_id}:{$state_id}" ); ?>"
									<# if ( data.states && jQuery.inArray( '<?php echo esc_js( "{$country_id}:{$state_id}" ); ?>', data.states ) !== -1 ) { #>
										selected
									<# } #>
								>
									<?php echo wcp_esc_html( $state ); ?>
								</option>
							<?php } ?>
						</optgroup>
					<?php } ?>
				</select>
			</div>

			<div class="value_input wcp_country_value_input">
				<select class="wc-enhanced-select" name="wcp_conditions[{{data.index}}][countries][]" class="select" multiple>
					<?php foreach ( woo_conditional_payments_country_options() as $code => $country ) { ?>
						<option
							value="<?php echo esc_attr( $code ); ?>"
							<# if ( data.countries && jQuery.inArray( '<?php echo esc_js( $code ); ?>', data.countries ) !== -1 ) { #>
								selected
							<# } #>
						>
							<?php echo wcp_esc_html( $country ); ?>
						</option>
					<?php } ?>
				</select>
			</div>

			<div class="value_input wcp_currency_value_input">
				<select class="wc-enhanced-select" name="wcp_conditions[{{data.index}}][currencies][]" class="select" multiple>
					<?php foreach ( wcp_currency_options() as $code => $currency ) { ?>
						<option
							value="<?php echo esc_attr( $code ); ?>"
							<# if ( data.currencies && jQuery.inArray( '<?php echo esc_js( $code ); ?>', data.currencies ) !== -1 ) { #>
								selected
							<# } #>
						>
							<?php echo wcp_esc_html( $currency ); ?>
						</option>
					<?php } ?>
				</select>
			</div>

			<div class="value_input wcp_date_value_input">
				<input type="text" name="wcp_conditions[{{data.index}}][date]" class="wcp-datepicker" value="{{data.date}}" />
			</div>

			<div class="value_input wcp_time_value_input">
				<select name="wcp_conditions[{{data.index}}][time_hours]" class="select">
					<?php foreach ( wcp_time_hours_options() as $hours => $label ) { ?>
						<option
							value="<?php echo esc_attr( $hours ); ?>"
							<# if ( data.time_hours && '<?php echo esc_js( $hours ); ?>' == data.time_hours ) { #>
								selected
							<# } #>
						>
							<?php echo wcp_esc_html( $label ); ?>
						</option>
					<?php } ?>
				</select>
				<span>&nbsp;:&nbsp;</span>
				<select name="wcp_conditions[{{data.index}}][time_mins]" class="select">
					<?php foreach ( wcp_time_mins_options() as $mins => $label ) { ?>
						<option
							value="<?php echo $mins; ?>"
							<# if ( data.time_mins && '<?php echo esc_js( $mins ); ?>' == data.time_mins ) { #>
								selected
							<# } #>
						>
							<?php echo wcp_esc_html( $label ); ?>
						</option>
					<?php } ?>
				</select>
			</div>

			<?php do_action( 'woo_conditional_payments_ruleset_value_inputs', $ruleset ); ?>
		</td>

		<td class="wcp-remove">
			<a href="#" class="wcp-remove-condition wcp-remove-row">
				<span class="dashicons dashicons-trash"></span>
			</a>
		</td>
	</tr>
</script>

<script type="text/html" id="tmpl-wcp_action_row_template">
	<tr valign="top" class="action_row">
		<td class="wcp-action">
			<div class="wcp-action-container">
				<select name="wcp_actions[{{data.index}}][type]" class="wcp_action_type_select">
					<option value=""><?php echo wcp_esc_html( __( '- Select action - ', 'woo-conditional-payments' ) ); ?></option>

					<?php foreach ( wcp_get_grouped_actions() as $group_id => $group ) { ?>
						<optgroup label="<?php echo esc_attr( $group['title'] ); ?>">
							<?php foreach ( $group['actions'] as $key => $action ) { ?>
								<option
									value="<?php echo esc_attr( $key ); ?>"
									<?php echo ( isset( $action['pro'] ) && $action['pro'] ) ? 'disabled' : ''; ?>
									<# if ( data.type == '<?php echo esc_js( $key ); ?>' ) { #>selected<# } #>
								>
									<?php echo wcp_esc_html( wcp_get_control_title( $action ) ); ?>
								</option>
							<?php } ?>
						</optgroup>
					<?php } ?>
				</select>

				<span class="woocommerce-help-tip wcp-action-help-tip"></span>
			</div>

			<input type="hidden" name="wcp_actions[{{data.index}}][guid]" value="{{ data.guid }}" />
		</td>
		<td class="wcp-methods">
			<select name="wcp_actions[{{data.index}}][payment_method_ids][]" multiple class="select wc-enhanced-select" data-placeholder="<?php echo esc_attr( __( 'Select payment methods', 'woo-conditional-payments' ) ); ?>">
				<?php foreach ( woo_conditional_payments_get_payment_method_options() as $id => $method_title ) { ?>
					<option value="<?php echo esc_attr( $id ); ?>" <# if ( data.payment_method_ids && data.payment_method_ids.indexOf("<?php echo esc_js( $id ); ?>") !== -1 ) { #>selected<# } #>><?php echo wcp_esc_html( $method_title ); ?></option>
				<?php } ?>
			</select>

			<div class="value_input wcp_error_msg_input">
				<textarea name="wcp_actions[{{data.index}}][error_msg]" rows="4" cols="40" placeholder="<?php esc_attr_e( __( 'Custom "no payment methods available" message', 'woo-conditional-payments' ) ); ?>">{{ data.error_msg }}</textarea>
			</div>
		</td>
		<td class="wcp-values">
			<div class="value_input wcp_price_value_input">
				<input name="wcp_actions[{{data.index}}][price]" type="number" step="0.01" value="{{ data.price }}" />
			</div>

			<div class="value_input wcp_fee_value_input">
				<div class="wcp-fee-amount-inputs">
					<input name="wcp_actions[{{data.index}}][fee_amount]" type="number" step="any" value="{{ data.fee_amount }}" placeholder="<?php echo esc_attr_e( 'Amount', 'woo-conditional-payments' ); ?>" />
					<select name="wcp_actions[{{data.index}}][fee_mode]">
						<?php foreach ( wcp_get_fee_modes() as $fee_mode => $fee_mode_title ) { ?>
							<option value="<?php echo esc_attr( $fee_mode ); ?>" <# if ( data.fee_mode === "<?php echo esc_attr( $fee_mode ); ?>" ) { #>selected<# } #>><?php echo wcp_esc_html( $fee_mode_title ); ?></option>
						<?php } ?>
					</select>
				</div>
				<input name="wcp_actions[{{data.index}}][fee_title]" type="text" value="{{ data.fee_title }}" placeholder="<?php esc_attr_e( 'Fee description', 'woo-conditional-payments' ); ?>" />
				<select name="wcp_actions[{{data.index}}][fee_tax]">
					<?php foreach ( woo_conditional_payments_fee_tax_options() as $id => $label ) { ?>
						<option value="<?php echo esc_attr( $id ); ?>" <# if ( data.fee_tax === "<?php echo esc_js( $id ); ?>" ) { #>selected<# } #>>
							<?php echo wcp_esc_html( $label ); ?>
						</option>
					<?php } ?>
				</select>
			</div>
		</td>

		<td class="wcp-remove">
			<a href="#" class="wcp-remove-action wcp-remove-row">
				<span class="dashicons dashicons-trash"></span>
			</a>
		</td>
	</tr>
</script>

<div id="wcp-actions-help-modal" style="display:none" title="<?php esc_attr_e( 'Actions', 'woo-conditional-payments' ); ?>">
    <table class="widefat">
		<tr>
            <th><?php esc_html_e( 'Disable payment methods', 'woo-conditional-payments' ); ?></th>
            <td><?php esc_html_e( 'Disables payment methods when conditions pass', 'woo-conditional-payments' ); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Enable payment methods', 'woo-conditional-payments' ); ?></th>
            <td>
				<?php esc_html_e( 'Enables payment methods when conditions pass', 'woo-conditional-payments' ); ?>
				<div class="wcp-action-help">
					<span class="dashicons dashicons-lightbulb"></span>
					<?php esc_html_e( 'Use this to re-enable methods that were disabled by an earlier ruleset.', 'woo-conditional-payments' ); ?>
				</div>
			</td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Allow payment methods', 'woo-conditional-payments' ); ?></th>
            <td>
				<?php esc_html_e( 'Enables payment methods when conditions pass, disables them when conditions fail.', 'woo-conditional-payments' ); ?>
				<div class="wcp-action-help">
					<span class="dashicons dashicons-warning"></span>
					<?php esc_html_e( 'Avoid using this action in multiple rulesets for the same payment method. Each ruleset will independently toggle availability, which can cause unexpected behavior.', 'woo-conditional-payments' ); ?>
				</div>
			</td>
        </tr>
    </table>
</div>
