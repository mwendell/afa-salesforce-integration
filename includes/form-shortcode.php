<?php
/**
 * Form Shortcode and Rendering
 *
 * @package AFA_Salesforce
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Render Salesforce form with AJAX support
 */
function afa_salesforce_render_form() {
	ob_start();
	?>
	<div class="afa-salesforce-form-wrapper">
		<!-- Registration form (default visible state) -->
		<form id="afa-salesforce-form" method="post" action="" class="afa-salesforce-form">

			<?php
			$join_form_html = get_option( 'afa_salesforce_join_form_html', "<h5>Enjoying This?</h5>/n<p><b>Become an AFA member for free</b> to unlock exclusive content, receive breaking Air Force & Space Force news, and join the community that's moving our mission forward.</p>/n<p>There's no cost to join. Just fill out the form below, and you're in.</p>" );

			echo $join_form_html;

			/*
			<h5>Enjoying This?</h5>
			<p><b>Become an AFA member for free</b> to unlock exclusive content, receive breaking Air Force & Space Force news, and join the community that's moving our mission forward.</p>
			<p>There's no cost to join. Just fill out the form below, and you're in.</p>
			*/

			wp_nonce_field( 'afa_salesforce_form_action', 'afa_salesforce_nonce' );
			?>

			<input type="hidden" id="afa_source" name="afa_source" value="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" />
			<input type="hidden" id="afa_intent" name="afa_intent" value="" />

			<div class="afa-form-messages" style="display: none;">
				<div class="afa-form-message"></div>
			</div>

			<div class="afa-form-field">
				<!--<label for="afa_first_name"><?php _e( 'First Name', 'afa-salesforce' ); ?> <span class="required">*</span></label>-->
				<input type="text" id="afa_first_name" name="first_name" placeholder="First Name *" required />
			</div>

			<div class="afa-form-field">
				<!--<label for="afa_last_name"><?php _e( 'Last Name', 'afa-salesforce' ); ?> <span class="required">*</span></label>-->
				<input type="text" id="afa_last_name" name="last_name" placeholder="Last Name *" required />
			</div>

			<div class="afa-form-field">
				<!--<label for="afa_email"><?php _e( 'Email', 'afa-salesforce' ); ?> <span class="required">*</span></label>-->
				<input type="email" id="afa_email" name="email" placeholder="Email *" required />
			</div>

			<div class="afa-form-field">
				<!--<label for="afa_affiliation"><?php _e( 'Service Affiliation', 'afa-salesforce' ); ?></label>-->
				<select id="afa_affiliation" name="affiliation">
					<option value=""><?php _e( 'Select Service Affiliation', 'afa-salesforce' ); ?></option>
					<option value="Active Duty"><?php _e( 'Active Duty', 'afa-salesforce' ); ?></option>
					<option value="National Guard"><?php _e( 'Guard', 'afa-salesforce' ); ?></option>
					<option value="Reserve"><?php _e( 'Reserve', 'afa-salesforce' ); ?></option>
					<option value="Academy Cadet"><?php _e( 'Cadet', 'afa-salesforce' ); ?></option>
					<option value="Veteran or Retired"><?php _e( 'Veteran', 'afa-salesforce' ); ?></option>
					<option value="Spouse/Partner"><?php _e( 'Military Spouse', 'afa-salesforce' ); ?></option>
					<option value="Family"><?php _e( 'Parents & Loved Ones', 'afa-salesforce' ); ?></option>
					<option value="Civilian"><?php _e( 'Government Civilian', 'afa-salesforce' ); ?></option>
					<option value="Industry/Corporate"><?php _e( 'Industry', 'afa-salesforce' ); ?></option>
					<option value="Attache/Foreign Military"><?php _e( 'Attache/Foreign Military', 'afa-salesforce' ); ?></option>
					<option value="Other"><?php _e( 'Other', 'afa-salesforce' ); ?></option>
				</select>
			</div>

			<div class="afa-form-field">
				<!--<label for="afa_branch"><?php _e( 'Branch', 'afa-salesforce' ); ?></label>-->
				<select id="afa_branch" name="branch">
					<option value=""><?php _e( 'Select Branch', 'afa-salesforce' ); ?></option>
					<option value="United States Air Force"><?php _e( 'Air Force', 'afa-salesforce' ); ?></option>
					<option value="United States Space Force"><?php _e( 'Space Force', 'afa-salesforce' ); ?></option>
					<option value="United States Navy"><?php _e( 'Navy', 'afa-salesforce' ); ?></option>
					<option value="United States Army"><?php _e( 'Army', 'afa-salesforce' ); ?></option>
					<option value="United States Marine Corps"><?php _e( 'Marines', 'afa-salesforce' ); ?></option>
					<option value="United States Coast Guard"><?php _e( 'Coast Guard', 'afa-salesforce' ); ?></option>
					<option value="Air Force Supporter/Other"><?php _e( 'Other/Not Applicable', 'afa-salesforce' ); ?></option>
				</select>
			</div>

			<div class="afa-form-field">
				<!--<label for="afa_zip_code"><?php _e( 'Zip Code', 'afa-salesforce' ); ?> <span class="required">*</span></label>-->
				<input type="text" id="afa_zip_code" name="zip_code" placeholder="Zip Code *" required />
			</div>

			<div class="afa-form-field">
				<label for="afa_chapter_recruited">
					<input type="checkbox" id="afa_chapter_recruited" name="chapter_recruited" value="1" />
					&nbsp;<?php _e( 'Click here if you were recruited by an AFA chapter.', 'afa-salesforce' ); ?>
				</label>
			</div>

			<div class="afa-form-field">
				<button type="submit" name="afa_salesforce_form_submit" class="afa-submit-button">
					<span class="afa-button-text"><?php _e( 'Join Now', 'afa-salesforce' ); ?></span>
					<span class="afa-button-spinner" style="display: none;"><?php _e( 'Processing...', 'afa-salesforce' ); ?></span>
				</button>
			</div>

			<div class="afa-form-field" style="text-align: center; font-size: 15px;">
				<a href="/login" style="color:#4169e1; cursor: pointer;">
					<?php _e( 'Already an AFA member? Click to log in.', 'afa-salesforce' ); ?>
				</a>
		</div>
		</form>

		<!-- Thank-you panel (shown on 200/201) -->
		<div id="afa-salesforce-thankyou" class="afa-salesforce-thankyou" style="display: none;">
			<div class="afa-thankyou-icon">&#10003;</div>

			<?php
			$success_html = get_option( 'afa_salesforce_success_html', "<h2>Thank You!</h2>/n<p><b>Check your inbox to verify your email address and finish setting up your AFA account.</b></p>" );

			echo $success_html;

			/*
			<h2>Thank You!</h2>
			<p><b>Check your inbox to verify your email address and finish setting up your AFA account.</b></p>
			*/

			?>

			<div class="afa-form-field">
				<button type="button" class="afa-submit-button mm-popup-close-internal">
					<span class="afa-button-text"><?php _e( 'Close', 'afa-salesforce' ); ?></span>
				</button>
			</div>
		</div>

		<!-- Login prompt (shown on 409) -->
		<div id="afa-salesforce-login-prompt" class="afa-salesforce-login-prompt" style="display: none;">
			<div class="afa-login-icon">&starf;</div>

			<?php
			$existing_user_html = get_option( 'afa_salesforce_existing_user_html', "<h2>Welcome Back!</h2>/n<p><b>An account with this email already exists. Please log in to continue.</b></p>" );

			echo $existing_user_html;

			/*
			<h2><?php _e( 'Welcome Back!', 'afa-salesforce' ); ?></h2>
			<p><b><?php _e( 'An account with this email already exists. Please log in to continue.</b>', 'afa-salesforce' ); ?></p>
			*/
			?>

			<div class="afa-form-field">
				<a href="/login">
					<button type="button" class="afa-submit-button">
						<span class="afa-button-text"><?php _e( 'Log In Now', 'afa-salesforce' ); ?></span>
					</button>
				</a>
			</div>

			<!--
			<div class="afa-form-messages afa-login-error-messages" style="display: none;">
				<div class="afa-form-message"></div>
			</div>
			-->
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Shortcode to display the form
 * Usage: [afa_salesforce_form]
 */
function afa_salesforce_form_shortcode( $atts ) {
	// Parse shortcode attributes
	$atts = shortcode_atts( array(
		// Future attributes can be added here
	), $atts, 'afa_salesforce_form' );

	return afa_salesforce_render_form();
}
add_shortcode( 'afa_salesforce_form', 'afa_salesforce_form_shortcode' );
