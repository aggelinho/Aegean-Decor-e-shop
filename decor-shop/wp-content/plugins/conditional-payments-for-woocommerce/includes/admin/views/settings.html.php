<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 class="woo-conditional-payments-heading">
	<?php _e( 'Conditions', 'woo-conditional-payments' ); ?>
</h2>

<table class="form-table">
	<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Rulesets', 'woo-conditional-payments' ); ?>
				</label>
			</th>
			<td class="forminp">
				<table class="wcp-rulesets widefat">
					<thead>
						<tr>
							<?php if ( ! $disable_sortable ) { ?>
								<th class="wcp-ruleset-order">
									<?php echo wc_help_tip( __( 'Drag and drop to re-order your rulesets. This is the order in which they will be evaluated.', 'woo-conditional-payments' ) ); ?>
								</th>
							<?php } ?>
							<th class="wcp-ruleset-name"><?php esc_html_e( 'Ruleset', 'woo-conditional-payments' ); ?></th>
							<th class="wcp-ruleset-status"><?php esc_html_e( 'Enabled', 'woo-conditional-payments' ); ?></th>
						</tr>
					</thead>
					<tbody class="wcp-ruleset-rows">
						<?php foreach ( $rulesets as $ruleset ) { ?>
							<tr>
								<?php if ( ! $disable_sortable ) { ?>
									<td width="1%" class="wcp-ruleset-sort">
										<input type="hidden" name="wcp_ruleset_order[]" value="<?php echo esc_attr( $ruleset->get_id() ); ?>">
									</td>
								<?php } ?>
								<td class="wcp-ruleset-name">
									<a href="<?php echo esc_attr( $ruleset->get_admin_edit_url() ); ?>">
										<?php echo esc_html( $ruleset->get_title() ); ?>
									</a>
									<div class="wcp-row-actions">
										<?php foreach ( $ruleset->get_row_actions() as $key => $action ) { ?>
											<span>
												<a href="<?php echo esc_attr( $action['url'] ); ?>" class="<?php echo esc_attr( $action['class'] ); ?>">
													<?php echo esc_html( $action['title'] ); ?>
												</a>
											</span>

											<?php if ( $key !== 'clone' ) { ?>
												<span>|</span>
											<?php } ?>
										<?php } ?>
									</div>
								</td>
								<td class="wcp-ruleset-status">
									<?php $class = $ruleset->get_enabled() ? 'enabled' : 'disabled'; ?>
									<span class="woocommerce-input-toggle woocommerce-input-toggle--<?php echo $class; ?>" data-id="<?php echo esc_attr( $ruleset->get_id() ); ?>"></span>
								</td>
							</tr>
						<?php } ?>
						<?php if ( empty( $rulesets ) ) { ?>
							<tr>
								<td width="1%"></td>
								<td colspan="2" class="wcp-ruleset-name">
									<?php esc_html_e( 'No rulesets defined yet.', 'woo-conditional-payments' ); ?> <a href="<?php echo esc_attr( $add_ruleset_url ); ?>"><?php esc_html_e( 'Add new', 'woo-conditional-payments' ); ?> &raquo;</a>
								</td>
							</tr>
						<?php } ?>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="3">
								<a href="<?php echo esc_attr( $add_ruleset_url ); ?>" class="button"><?php esc_html_e( 'Add ruleset', 'woo-conditional-payments' ); ?></a>
							</td>
						</tr>
					</tfoot>
				</table>
			</td>
		</tr>

		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Disable all', 'woo-conditional-payments' ); ?>
				</label>
			</th>
			<td class="forminp">
				<label for="wcp_disable_all">
					<input type="checkbox" name="wcp_disable_all" id="wcp_disable_all" value="1" <?php checked( get_option( 'wcp_disable_all', false ) ); ?> />
					<?php esc_html_e( 'Disable all rulesets', 'woo-conditional-payments' ); ?>
				</label>

				<p class="description"><?php esc_html_e( 'Helpful for finding out if Conditional Payments or another plugin is affecting payments methods for troubleshooting.', 'woo-conditional-payments' ); ?></p>
			</td>
		</tr>

		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Debug mode', 'woo-conditional-payments' ); ?>
				</label>
			</th>
			<td class="forminp">
				<select name="wcp_debug_mode">
					<?php foreach ( wcp_debug_mode_options() as $key => $label ) { ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( get_option( 'wcp_debug_mode', '' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
				</select>

				<p class="description"><?php esc_html_e( 'Debug mode shows passed conditions and which actions were run in the checkout.', 'woo-conditional-payments' ); ?></p>
			</td>
		</tr>

		<?php do_action( 'woo_conditional_payments_after_settings' ); ?>
	</tbody>
</table>

<p class="submit">
	<button type="submit" name="submit" id="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Save changes', 'woo-conditional-payments' ); ?>"><?php esc_html_e( 'Save changes', 'woo-conditional-payments' ); ?></button>

	<input type="hidden" value="1" name="save" />
	<input type="hidden" value="1" name="wcp_settings" />

	<?php wp_nonce_field( 'woocommerce-settings' ); ?>
</p>

<?php if ( ! empty( $health['enables'] ) || ! empty( $health['disables'] ) ) { ?>
	<div class="woo-conditional-payments-health-check">
		<h3><?php esc_html_e( 'Health check', 'woo-conditional-payments' ); ?></h3>

		<?php foreach ( $health['enables'] as $gateway_id => $ruleset_ids ) { ?>
			<div class="issue-container">
				<div class="title">
					<?php echo wp_kses_post( sprintf(
						__( '<code>Enable payment methods - %1$s</code> in multiple rulesets', 'woo-conditional-payments' ),
						woo_conditional_payments_get_method_title( $gateway_id )
					) ); ?>

					<span class="toggle-indicator"></span>
				</div>

				<div class="details">
					<div class="issue">
						<?php echo wp_kses_post( sprintf( __( 'You have <code>Enable payment methods - %1$s</code> in multiple rulesets (%2$s). <code>Enable payment methods</code> will disable the methods if conditions do not pass. It can cause unexpected behaviour when used in multiple rulesets for the same payment method (<code>%1$s</code>).', 'woo-conditional-payments' ), woo_conditional_payments_get_method_title( $gateway_id ), woo_conditional_payments_format_ruleset_ids( $ruleset_ids ) ) ); ?>
					</div>

					<div class="fix">
						<div><strong><?php esc_html_e( 'How to fix', 'woo-conditional-payments' ); ?></strong></div>
						<div>
							<ul>
								<li><?php echo wp_kses_post( __( 'Check if you can use <code>Disable payment methods</code> instead. It\'s usually easier to work with.', 'woo-conditional-payments' ) ); ?></li>
								<li><?php echo wp_kses_post( sprintf( __( 'Remove <code>Enable payment methods - %s</code> from all but one ruleset.', 'woo-conditional-payments' ), woo_conditional_payments_get_method_title( $gateway_id ) ) ); ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>

		<?php foreach ( $health['disables'] as $data ) { ?>
			<div class="issue-container">
				<div class="title">
					<?php echo wp_kses_post( sprintf(
						__( '<code>%s</code> disabled', 'woo-conditional-payments' ),
						$data['gateway']->get_method_title()
					) ); ?>

					<span class="toggle-indicator"></span>
				</div>

				<div class="details">
					<div class="issue">
						<?php echo wp_kses_post( sprintf(
							__( 'You have <code>%1$s - %2$s</code> in %3$s but <code>%2$s</code> is disabled in <a href="%4$s" target="_blank">the settings</a>. Conditional Payments can only process payment methods which are enabled.', 'woo-conditional-payments' ),
							woo_conditional_payments_action_title( $data['action']['type'] ),
							$data['gateway']->get_method_title(),
							woo_conditional_payments_format_ruleset_ids( array( $data['ruleset']->get_id() ) ),
							woo_conditional_payments_get_gateway_url( $data['gateway'] )
						) ); ?>
					</div>

					<div class="fix">
						<div><strong><?php esc_html_e( 'How to fix', 'woo-conditional-payments' ); ?></strong></div>
						<div>
							<ul>
								<li>
									<?php echo wp_kses_post( sprintf( __( 'Enable <code>%s</code> in <a href="%s" target="_blank">the settings</a>', 'woo-conditional-payments' ),
											$data['gateway']->get_method_title(),
											woo_conditional_payments_get_gateway_url( $data['gateway'] )
									) ); ?>
								</li>
								<li>
									<?php echo wp_kses_post( sprintf(
										__( 'Remove <code>%1$s - %2$s</code> from %3$s.', 'woo-conditional-payments' ),
										woo_conditional_payments_action_title( $data['action']['type'] ),
										$data['gateway']->get_method_title(),
										woo_conditional_payments_format_ruleset_ids( array( $data['ruleset']->get_id() ) )
									) ); ?>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
<?php } ?>

