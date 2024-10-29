<?php
/*
Plugin Name: Backlog Ticket Viewer
Description: Display backlog ticket on dashboard.
Version: 1.0
Author: PRESSMAN
Author URI: https://www.pressman.ne.jp/
Copyright: Copyright (c) 2018, PRESSMAN
License: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, v2 or higher
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 **/
class Backlog_Ticket_Viewer {
	/**
	 * Initialization
	 **/
	function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_stylesheet' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'widgets' ) );
		add_action( 'admin_init', array( $this, 'text_domain' ) );
	}

	/**
	 * Languages registry
	 **/
	public function text_domain() {
		load_plugin_textdomain( 'backlog-ticket-viewer', false, plugin_basename( plugin_dir_path( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Stylesheet registry
	 **/
	public function add_stylesheet() {
		if ( get_current_screen()->id == 'dashboard' ) {
			wp_register_style( 'btv_css', plugins_url( '/css/style.css', __FILE__ ), array(), null );
			wp_enqueue_style( 'btv_css' );
		}
	}

	/**
	 * Widgets registry
	 **/
	public function widgets() {

		if ( is_admin() ) {

			wp_add_dashboard_widget(
				'backlog_ticket_viewer',
				__( 'Backlog Ticket List', 'backlog-ticket-viewer' ),
				array( $this, 'view' ),
				array( $this, 'option' )
			);

		} else {

			wp_add_dashboard_widget(
				'backlog_ticket_viewer',
				__( 'Backlog Ticket List', 'backlog-ticket-viewer' ),
				array( $this, 'view' )
			);

		}
	}

	private $option_names = array(
		'backlogDomain',
		'backlogTeamName',
		'backlogApikey',
		'backlogProjectId',
		'statusId',
		'count'
	);

	/**
	 * Process of view
	 **/
	public function view() {

		if ( ! $widget_options = get_option( 'backlog_ticket_viewer_widget_options' ) ) {
			$widget_options = array();
		}

		foreach ( $this->option_names as $name ) {
			$widget_options[ $name ] = isset( $widget_options[ $name ] ) ? $widget_options[ $name ] : '';
			$widget_options[ $name ] = apply_filters( 'btv_' . $name, $widget_options[ $name ] );
		}

		if ( ( ! isset( $widget_options['backlogDomain'] ) ) || ( $widget_options['backlogDomain'] == '' ) ) {
			$widget_options['backlogDomain'] = 'backlog.jp';
		}

		$status     = '';
		$status_txt = __( 'All', 'backlog-ticket-viewer' );
		$hrefUrl    = '<a href="' . admin_url() . 'index.php?edit=backlog_ticket_viewer#backlog_ticket_viewer">' . __( 'setting', 'backlog-ticket-viewer' ) . '</a>';

		if ( ( $widget_options['backlogTeamName'] == '' ) || ( $widget_options['backlogApikey'] == '' ) || ( $widget_options['backlogProjectId'] == '' ) ) {
			?>
			<p class="btv_no_settings">
				<?php
				echo __( 'Please set required items setting.', 'backlog-ticket-viewer' );
				echo esc_url( $hrefUrl );
				?>
			</p>
			<?php
			return;
		}

		if ( $widget_options['statusId'] == '1' ) {
			$status     = '&statusId[]=1';
			$status_txt = __( 'Not compatible', 'backlog-ticket-viewer' );
		} elseif ( $widget_options['statusId'] == '2' ) {
			$status     = '&statusId[]=2';
			$status_txt = __( 'Processing', 'backlog-ticket-viewer' );
		} elseif ( $widget_options['statusId'] == '3' ) {
			$status     = '&statusId[]=3';
			$status_txt = __( 'Processed', 'backlog-ticket-viewer' );
		} elseif ( $widget_options['statusId'] == '4' ) {
			$status     = '&statusId[]=4';
			$status_txt = __( 'Done', 'backlog-ticket-viewer' );
		} elseif ( $widget_options['statusId'] == '1,2,3' ) {
			$status     = '&statusId[]=1&statusId[]=2&statusId[]=3';
			$status_txt = __( 'Other than completion', 'backlog-ticket-viewer' );
		}

		$url            = "https://{$widget_options['backlogTeamName']}.{$widget_options['backlogDomain']}/api/v2/projects/{$widget_options['backlogProjectId']}";
		$params['body'] = array(
			'apiKey' => $widget_options['backlogApikey']
		);

		$contents      = wp_remote_get( $url, $params );
		$response_code = wp_kses( $contents["response"]["code"], array() );

		if ( is_wp_error( $contents ) || $response_code !== '200' ) {
			?>
			<p class="btv_no_settings">
				<?php
				echo __( 'No response data. Please review the setting.', 'backlog-ticket-viewer' );
				echo esc_url( $hrefUrl );
				?>
			</p>
			<?php
			return;
		}

		$body        = wp_kses( $contents["body"], array() );
		$data        = json_decode( $body, true );
		$projectName = isset( $data['name'] ) ? $data['name'] : '';

		$url            = "https://{$widget_options['backlogTeamName']}.{$widget_options['backlogDomain']}/api/v2/issues?projectId[]={$widget_options['backlogProjectId']}" . $status;
		$params['body'] = array(
			'apiKey' => $widget_options['backlogApikey'],
			'sort' => 'created',
			'order' => 'desc',
			'count' => $widget_options['count']
		);

		$contents      = wp_remote_get( $url, $params );
		$response_code = wp_kses( $contents["response"]["code"], array() );

		if ( is_wp_error( $contents ) || $response_code !== '200' ) {
			?>
			<p class="btv_no_settings">
				<?php
				echo __( 'No response data. Please review the setting.', 'backlog-ticket-viewer' );
				echo esc_url( $hrefUrl );
				?>
			</p>
			<?php
			return;
		}

		$body = wp_kses( $contents["body"], array() );
		$data = json_decode( $body, true );

		if ( is_null( $data ) ) {
			?>
			<p class="btv_no_settings"><?php echo __( 'Invalid response data format.', 'backlog-ticket-viewer' ); ?>
				<a href="<?php echo admin_url(); ?>index.php?edit=backlog_ticket_viewer#backlog_ticket_viewer"><?php echo __( 'setting', 'backlog-ticket-viewer' ); ?></a>
			</p>
			<?php
			return;
		}

		$list_count = count( $data );
		if ( $list_count == 0 ) {
			?>
			<b><?php echo __( 'No ticket', 'backlog-ticket-viewer' ); ?></b>
			<?php
			return;
		}

		?>
		<div class="btv_info">
			<p><span class="dashicons dashicons-clipboard"></span><span
						class="content btv_pjtname"><?php echo $projectName; ?></span>
			</p>
			<p><span class="dashicons dashicons-filter"></span><span
						class="content btv_listfilter"><?php echo __( 'status', 'backlog-ticket-viewer' ); ?>
					<strong><?php echo esc_html( $status_txt ); ?></strong> | <?php echo __( 'Display Number', 'backlog-ticket-viewer' ); ?>
					<strong><?php echo esc_html( $list_count ); ?></strong></span>
			</p>
		</div>
		<div id="btv_list">
			<ul>
				<li class="th">
					<span class="btv_id"><?php echo __( 'ticket id', 'backlog-ticket-viewer' ); ?></span>
					<span class="btv_title"><?php echo __( 'ticket title', 'backlog-ticket-viewer' ); ?></span>
					<span class="btv_assign"><?php echo __( 'assign　name', 'backlog-ticket-viewer' ); ?></span>
					<span class="btv_status"><?php echo __( 'status', 'backlog-ticket-viewer' ); ?></span>
				</li>

				<?php

				$image = array();

				foreach ( $data as $ticket ) {
					$issueKey     = $ticket['issueKey'];
					$ticket_title = $ticket['summary'];
					$assign       = $ticket['assignee']['name'];
					$status_name  = $ticket['status']['name'];

					if ( ! $assign ) {
						$assign = __( 'no assign', 'backlog-ticket-viewer' );
					}
					$href = "https://" . esc_html( $widget_options['backlogTeamName'] ) . "." . esc_html( $widget_options['backlogDomain'] ) . "/view/" . esc_html( $issueKey );
					?>
					<li>
						<span class="btv_link"><a
									href="<?php echo esc_url( $href ); ?>"
									target="_blank"><?php echo esc_html( $issueKey ); ?></a></span>
						<span class="btv_title"><?php echo esc_html( $ticket_title ); ?></span>
						<span class="btv_assign">
									<?php

									$icon_data = '';
									if ( array_key_exists( $ticket['assignee']['id'], $image ) ) {
										$id        = $ticket['assignee']['id'];
										$icon_data = $image[ $id ];
									} else {
										if ( $ticket['assignee']['id'] ) {
											$url            = "https://{$widget_options['backlogTeamName']}.{$widget_options['backlogDomain']}/api/v2/users/{$ticket['assignee']['id']}/icon";
											$params['body'] = array(
												'apiKey' => $widget_options['backlogApikey']
											);

											$contents = wp_remote_get( $url, $params );

											$content_type = wp_kses( $contents['headers']['content-type'], array() );
											if ( $content_type == 'image/gif' ) {
												if ( is_array( $contents ) && $contents["body"] ) {
													$icon_data                          = $contents["body"];
													$image[ $ticket['assignee']['id'] ] = $icon_data;
												}
											}
										}
									}

									if ( $icon_data ) {
										$imgLink = '<img width="20" height="20" src="data:images/jpeg;base64,' . esc_attr( base64_encode( $icon_data ) ) . '" />';
										echo $imgLink;
									}
									echo esc_html( $assign );
									?></span>
						<span class="btv_status"><?php echo esc_html( $status_name ); ?></span>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Processing when clicking the setting button in the handle of widget
	 */
	function option() {

		$count_options = array(
			'10',
			'20',
			'50',
			'100'
		);

		$statusId_options = array(
			'' => __( 'All', 'backlog-ticket-viewer' )
		);

		if ( ! $widget_options_for_update = get_option( 'backlog_ticket_viewer_widget_options' ) ) {
			$widget_options_for_update = array();
		}

		$all_option_data = array();
		foreach ( $this->option_names as $name ) {
			$value = array();
			$data  = null;
			$data  = apply_filters( 'btv_' . $name, $data );
			if ( is_null( $data ) ) {
				$value[] = isset( $widget_options_for_update[ $name ] ) ? $widget_options_for_update[ $name ] : '';
				$value[] = '';
			} else {
				$value[] = $data;
				$value[] = ' disabled="disabled" ';
			}
			$all_option_data[ $name ] = $value;
		}

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && wp_verify_nonce( $_POST['btv_nonce_field'], 'btv_update_action' ) ) {


			foreach ( $this->option_names as $name ) {
				$sanitize_text = sanitize_text_field( $_POST[ $name ] );
				if ( ( $sanitize_text != '' ) && ( $all_option_data[ $name ][1] == '' ) ) {
					$widget_options_for_update[ $name ]     = $sanitize_text;
					$all_option_data[ $name ][0] = $sanitize_text;
				}
			}

			//for all status selected
			$sanitized_statusId = sanitize_text_field( $_POST['statusId'] );
			if ( $sanitized_statusId == '' ) {
				$widget_options_for_update['statusId']     = $sanitized_statusId;
				$all_option_data['statusId'][0] = $sanitized_statusId;
			}

			if ( current_user_can( 'activate_plugins' ) && current_user_can( 'edit_plugins' ) ) {
				update_option( 'backlog_ticket_viewer_widget_options', $widget_options_for_update );
			}
		}

		if ( $all_option_data['backlogTeamName'][0] != '' && $all_option_data['backlogDomain'][0] != ''){
			//get status params
			$url            = "https://{$all_option_data['backlogTeamName'][0]}.{$all_option_data['backlogDomain'][0]}/api/v2/statuses";
			$params['body'] = array(
				'apiKey' => $all_option_data['backlogApikey'][0]
			);

			$contents      = wp_remote_get( $url, $params );
			$response_code = wp_kses( $contents["response"]["code"], array() );

			if ( ! is_wp_error( $contents ) && $response_code === '200' && is_array( $contents ) ) {
				$body             = wp_kses( $contents["body"], array() );
				$status_list_data = json_decode( $body, true );
				foreach ( $status_list_data as $data ) {
					$statusId_options[ $data['id'] ] = $data['name'];
				}
				$statusId_options['1,2,3'] = __( 'Other than completion', 'backlog-ticket-viewer' );
			}
		}


		if ( $all_option_data['backlogDomain'][0] == '' ) {
			$all_option_data['backlogDomain'][0] = 'backlog.jp';
		}

		if ( $all_option_data['count'][0] == '' ) {
			$all_option_data['count'][0] = '20';
		}

		?>
		<span class="btv_setting_title"><?php echo __( 'Setting', 'backlog-ticket-viewer' ); ?></span>
		<span class="btv_must">*</span><span
				class="btv_must"><?php echo __( 'is must setting', 'backlog-ticket-viewer' ); ?></span>
		<ul>
			<li>
				<span class="btv_feildname btv_team_name"><?php echo __( 'Team Name', 'backlog-ticket-viewer' ); ?><span
							class="btv_must">*</span></span>
				<input type="text" name="backlogTeamName" class="btv_input_team_name"
						value="<?php echo esc_html( $all_option_data['backlogTeamName'][0] ); ?>" <?php echo esc_html( $all_option_data['backlogTeamName'][1] ); ?>>
			</li>
			<li>
				<span class="btv_feildname btv_project_id"><?php echo __( 'Project Id', 'backlog-ticket-viewer' ); ?>
					<span class="btv_must">*</span></span>
				<input type="text" name="backlogProjectId" class="btv_input_project_id"
						value="<?php echo esc_html( $all_option_data['backlogProjectId'][0] ); ?>" <?php echo esc_html( $all_option_data['backlogProjectId'][1] ); ?>>
			</li>
			<li>
				<span class="btv_feildname btv_apikey"><?php echo __( 'Api key', 'backlog-ticket-viewer' ); ?><span
							class="btv_must">*</span></span>
				<input type="text" name="backlogApikey" class="btv_input_apikey"
						value="<?php echo esc_html( $all_option_data['backlogApikey'][0] ); ?>" <?php echo esc_html( $all_option_data['backlogApikey'][1] ); ?>>
			</li>
			<li>
				<span class="btv_feildname btv_domain"><?php echo __( 'Backlog Domain', 'backlog-ticket-viewer' ); ?>
					<span
							class="btv_must">*</span></span>
				<input type="text" name="backlogDomain" class="btv_input_domain"
						value="<?php echo esc_html( $all_option_data['backlogDomain'][0] ); ?>" <?php echo esc_html( $all_option_data['backlogDomain'][1] ); ?>>
			</li>
			<li>
				<span class="btv_feildname btv_count"><?php echo __( 'Maximum Display Number', 'backlog-ticket-viewer' ); ?></span>
				<select class="btv_input_count" name="count" <?php echo esc_html( $all_option_data['count'][1] ); ?>>
					<?php
					foreach ( $count_options as $option ) {
						?>
						<option value="<?php echo esc_html( $option ); ?>"
							<?php if ( $all_option_data['count'][0] == $option ) {
								echo 'selected';
							} ?>><?php echo esc_html( $option ); ?>
						</option>

						<?php
					}
					?>
				</select>
			</li>
			<li>
				<span class="btv_feildname btv_status_id"><?php echo __( 'Ticket Status', 'backlog-ticket-viewer' ); ?></span>
				<select class="btv_input_status_id" name="statusId"
					<?php echo esc_html( $all_option_data['statusId'][1] ); ?>>
					<?php
					foreach ( $statusId_options as $key => $value ) {
						?>
						<option value="<?php echo esc_html( $key ); ?>"
							<?php if ( $all_option_data['statusId'][0] == $key ) {
								echo 'selected';
							} ?>><?php echo esc_html( $value ); ?>
						</option>

						<?php
					}
					?>
				</select>
			</li>
			<li>
				<?php wp_nonce_field( 'btv_update_action', 'btv_nonce_field' ); ?>
			</li>
		</ul>
		<?php
	}

}

new Backlog_Ticket_Viewer();


