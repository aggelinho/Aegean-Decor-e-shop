<div id="wcp-debug">
	<div id="wcp-debug-header">
		<div class="wcp-debug-title"><?php esc_html_e( 'Conditional Payments Debug', 'woo-conditional-payments' ); ?></div>
		<div class="wcp-debug-toggle"></div>
	</div>

	<div id="wcp-debug-contents">
		<h3><?php esc_html_e( 'Payment methods', 'woo-conditional-payments' ); ?></h3>

		<table class="wcp-debug-table wcp-debug-table-fixed">
			<thead>
				<tr>
					<th>
						<?php esc_html_e( 'Before filtering', 'woo-conditional-payments' ); ?>
					</th>
					<th>
						<?php esc_html_e( 'After filtering', 'woo-conditional-payments' ); ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<?php if ( ! isset( $debug['payment_methods']['before'] ) || empty( $debug['payment_methods']['before'] ) ) { ?>
							<em><?php esc_html_e( 'No payment methods', 'woo-conditional-payments' ); ?></em>
						<?php } else { ?>
							<?php echo implode( '<br>', $debug['payment_methods']['before'] ); ?>
						<?php } ?>
					</td>
					<td>
						<?php if ( ! isset( $debug['payment_methods']['before'] ) || empty( $debug['payment_methods']['after'] ) ) { ?>
							<em><?php esc_html_e( 'No payment methods', 'woo-conditional-payments' ); ?></em>
						<?php } else { ?>
							<?php echo implode( '<br>', $debug['payment_methods']['after'] ); ?>
						<?php } ?>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="wcp-debug-tip"><?php esc_html_e( "If payment method is not listed above or is not available as expected, another plugin might be affecting its visibility or its settings do not allow it to be available for the cart or customer address.", 'woo-conditional-payments' ); ?></p>

		<h3><?php esc_html_e( 'Rulesets', 'woo-conditional-payments' ); ?></h3>

		<?php if ( empty( $debug['rulesets'] ) ) { ?>
			<p><?php esc_html_e( 'No rulesets were run.', 'woo-conditional-payments' ); ?></p>
		<?php } ?>

		<?php foreach ( $debug['rulesets'] as $ruleset_id => $data ) { ?>
			<div class="wcp-debug-<?php echo esc_attr( $ruleset_id ); ?>">
				<h3 class="ruleset-title">
					<a href="<?php echo esc_attr( wcp_get_ruleset_admin_url( $data['ruleset_id'] ) ); ?>" target="_blank">
						<?php echo esc_html( $data['ruleset_title'] ); ?>
					</a>
				</h3>

				<table class="wcp-debug-table wcp-debug-conditions">
					<thead>
						<tr>
							<th colspan="2"><?php esc_html_e( 'Conditions', 'woo-conditional-payments' ); ?> - <?php echo esc_html( wcp_get_ruleset_operator_label( $data['ruleset_id'] ) ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $data['conditions'] as $condition ) { ?>
							<tr>
								<td><?php echo esc_html( $condition['desc'] ); ?></td>
								<td class="align-right">
									<span class="wcp-debug-result-label wcp-debug-result-label-<?php echo ( $condition['result'] ? 'fail' : 'pass' ); ?>">
										<?php echo esc_html( ( $condition['result'] ? __( 'Fail', 'woo-conditional-payments' ) : __( 'Pass', 'woo-conditional-payments' ) ) ); ?>
									</span>
								</td>
							</tr>
						<?php } ?>
					</tbody>
					<tfoot>
						<tr>
							<th><?php esc_html_e( 'Result', 'woo-conditional-payments' ); ?></th>
							<th class="align-right">
								<span class="wcp-debug-result-label wcp-debug-result-label-<?php echo ( $data['result'] ? 'pass' : 'fail' ); ?>">
									<?php echo esc_html( ( $data['result'] ? __( 'Pass', 'woo-conditional-payments' ) : __( 'Fail', 'woo-conditional-payments' ) ) ); ?>
								</span>
							</th>
						</tr>
					</tfoot>
				</table>

				<table class="wcp-debug-table wcp-debug-actions">
					<thead>
						<tr>
							<th colspan="2"><?php esc_html_e( 'Actions', 'woo-conditional-payments' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $data['actions'] as $action ) { ?>
							<tr class="status-<?php echo esc_attr( $action['status'] ); ?>">
								<td>
									<?php echo esc_html( implode( ' - ', $action['cols'] ) ); ?>

									<?php if ( $action['note'] ) { ?>
										<br><span class="wcp-debug-alert"></span><small><?php echo esc_html( $action['note'] ); ?></small>
									<?php } ?>
								</td>
								<td class="align-right">
									<?php if ( $action['note'] ) { ?>
										<span class="wcp-debug-alert"></span>
									<?php } ?>

									<span class="wcp-debug-result-label wcp-debug-result-label-<?php echo esc_attr( $action['status'] ); ?>">
										<?php echo esc_html( $action['label'] ); ?>
									</span>
								</td>
							</tr>
						<?php } ?>
						<?php if ( empty( $data['actions'] ) ) { ?>
							<tr>
								<td colspan="2"><?php esc_html_e( 'No actions were run for this ruleset', 'woo-conditional-payments' ); ?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		<?php } ?>
	</div>
</div>
