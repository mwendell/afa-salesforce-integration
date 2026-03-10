/**
 * AFA Salesforce Form AJAX Handler
 *
 * Handles registration form submission and response routing:
 *   200/201 – hide form, show thank-you panel
 *   400    – show server message, leave form editable for resubmission
 *   409    – swap registration form for login form (pre-filled email)
 *   500    – show error, prompt user to try again
 *
 * Also handles the login form that appears after a 409.
 *
 * @package AFA_Salesforce
 * @since 1.0.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		var $wrapper = $('.afa-salesforce-form-wrapper');

		if ( $wrapper.length === 0 ) {
			return;
		}

		// ─── Registration form elements ─────────────────────────────────
		var $reg_form = $('#afa-salesforce-form');
		var $reg_submit = $reg_form.find('button[type="submit"]');
		var $reg_button_text = $reg_submit.find('.afa-button-text');
		var $reg_button_spinner = $reg_submit.find('.afa-button-spinner');
		var $reg_messages = $reg_form.find('.afa-form-messages');
		var $reg_message_box = $reg_form.find('.afa-form-message');

		// ─── Thank-you panel ────────────────────────────────────────────
		var $thankyou = $('#afa-salesforce-thankyou');

		// ─── Login prompt elements ──────────────────────────────────────
		var $login_prompt = $('#afa-salesforce-login-prompt');
		var $login_button = $('.afa-salesforce-oauth-login');
		var $login_error_messages = $login_prompt.find('.afa-login-error-messages');
		var $login_error_box = $login_error_messages.find('.afa-form-message');

		// ─────────────────────────────────────────────────────────────────
		// Registration form submission
		// ─────────────────────────────────────────────────────────────────
		$reg_form.on('submit', function(e) {
			e.preventDefault();

			hide_reg_message();
			set_reg_button_loading(true);

			var form_data = {
				action: 'afa_salesforce_submit',
				nonce: afaSalesforceAjax.nonce,
				first_name: $reg_form.find('#afa_first_name').val(),
				last_name: $reg_form.find('#afa_last_name').val(),
				email: $reg_form.find('#afa_email').val(),
				affiliation: $reg_form.find('#afa_affiliation').val(),
				branch: $reg_form.find('#afa_branch').val(),
				zip_code: $reg_form.find('#afa_zip_code').val(),
				chapter_recruited: $reg_form.find('#afa_chapter_recruited').val(),
				source: $reg_form.find('#afa_source').val(),
				intent: $reg_form.find('#afa_intent').val()
			};

			console.log( form_data );

			$.ajax({
				url: afaSalesforceAjax.ajax_url,
				type: 'POST',
				data: form_data,
				dataType: 'json',
				success: function(response) {
					// wp_send_json_success always sets response.success === true
					// and the server sends 201 for a new contact
					handle_reg_success(response);
				},
				error: function(xhr) {
					// wp_send_json_error routes here; status code is on xhr
					handle_reg_error(xhr);
				},
				complete: function() {
					set_reg_button_loading(false);
				}
			});
		});

		/**
		 * 200 / 201 – Account created. Replace form with thank-you panel.
		 */
		function handle_reg_success(response) {
			console.log(response);

			var expires = new Date();
			// expires ten days from now
			expires.setTime( expires.getTime() + ( 10 * 24 * 60 * 60 * 1000 ) );

			document.cookie = "afa_sf_created=" + response.data.contact_id
				+ ";expires=" + expires.toUTCString()
				+ ";path=/";
			$reg_form.hide();

			document.cookie = "afa_mm_status=completed"
				+ ";expires=" + expires.toUTCString()
				+ ";path=/";
			$thankyou.slideDown(400);
		}

		/**
		 * Route error responses by HTTP status code.
		 */
		function handle_reg_error(xhr) {
			var status = xhr.status;
			var json = xhr.responseJSON;
			var message = (json && json.data && json.data.message)
				? json.data.message
				: 'An error occurred. Please try again.';
			var code = (json && json.data && json.data.code) ? json.data.code : '';

			switch ( status ) {
				case 400:
					// Validation error – show message, form stays editable
					show_reg_message('error', message);
					break;

				case 409:
					// Duplicate email – swap to login form
					swap_to_login_form();
					break;

				case 500:
				default:
					// Server error – show message with retry guidance
					show_reg_message('error', message + ' ' + 'Please try again.');
					break;
			}

			console.error('Salesforce registration error:', {
				status: status,
				code: code,
				message: message
			});
		}

		// ─────────────────────────────────────────────────────────────────
		// Swap registration form out, login form in
		// ─────────────────────────────────────────────────────────────────
		function swap_to_login_form() {
			$reg_form.hide();
			$login_prompt.slideDown(400);
		}

		// ─────────────────────────────────────────────────────────────────
		// OAuth login button click
		// ─────────────────────────────────────────────────────────────────
		/*
		// DISABLED WHILE WE STICK WITH EXISTING SAML PLUGIN for 2026
		$login_button.on('click', function(e) {
			e.preventDefault();

			hide_login_error();

			// Disable button and show loading state
			var $button_text = $(this).find('.afa-button-text');
			$button_text.text('Redirecting to Salesforce...');
			$(this).prop('disabled', true);

			var form_data = {
				action: 'afa_salesforce_oauth_login',
				nonce: afaSalesforceAjax.nonce,
				return_url: window.location.href  // Current page URL
			};

			$.ajax({
				url: afaSalesforceAjax.ajax_url,
				type: 'POST',
				data: form_data,
				dataType: 'json',
				success: function(response) {
					if (response.success && response.data.auth_url) {
						// Redirect to Salesforce OAuth login
						window.location.href = response.data.auth_url;
					} else {
						show_login_error('Failed to initiate login. Please try again.');
						$button_text.text('Log In with Salesforce');
						$login_button.prop('disabled', false);
					}
				},
				error: function(xhr) {
					var json = xhr.responseJSON;
					var message = (json && json.data && json.data.message)
						? json.data.message
						: 'Failed to initiate login. Please try again.';

					show_login_error(message);
					$button_text.text('Log In with Salesforce');
					$login_button.prop('disabled', false);

					console.error('Salesforce OAuth initiation error:', {
						status: xhr.status,
						message: message
					});
				}
			});
		});
		*/

		// ─────────────────────────────────────────────────────────────────
		// Registration form helpers
		// ─────────────────────────────────────────────────────────────────
		function show_reg_message(type, message) {
			$reg_message_box
				.removeClass('success error info')
				.addClass(type)
				.html(message);
			$reg_messages.slideDown(300);
		}

		function hide_reg_message() {
			$reg_messages.slideUp(300, function() {
				$reg_message_box.removeClass('success error info').html('');
			});
		}

		function set_reg_button_loading(is_loading) {
			$reg_submit.prop('disabled', is_loading);
			$reg_button_text.toggle(!is_loading);
			$reg_button_spinner.toggle(is_loading);
		}

		// ─────────────────────────────────────────────────────────────────
		// Login form helpers
		// ─────────────────────────────────────────────────────────────────
		function show_login_error(message) {
			$login_error_box
				.removeClass('success error info')
				.addClass('error')
				.html(message);
			$login_error_messages.slideDown(300);

			var expires = new Date();
			// expires three days from now
			expires.setTime( expires.getTime() + ( 3 * 24 * 60 * 60 * 1000 ) );

			document.cookie = "afa_mm_status=sf_error"
				+ ";expires=" + expires.toUTCString()
				+ ";path=/";


		}

		function hide_login_error() {
			$login_error_messages.slideUp(300, function() {
				$login_error_box.removeClass('success error info').html('');
			});
		}

		// ─────────────────────────────────────────────────────────────────
		// Real-time validation (registration form only)
		// ─────────────────────────────────────────────────────────────────
		$reg_form.find('#afa_email').on('blur', function() {
			var email = $(this).val();
			var email_pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

			if ( email && !email_pattern.test(email) ) {
				$(this).addClass('invalid');
				show_inline_error($(this), 'Please enter a valid email address');
			} else {
				$(this).removeClass('invalid');
				hide_inline_error($(this));
			}
		});

		$reg_form.find('#afa_zip_code').on('blur', function() {
			var zip = $(this).val();
			var zip_pattern = /^(\d{5}(-\d{4})?|[A-Za-z]\d[A-Za-z] ?\d[A-Za-z]\d)$/;

			if ( zip && !zip_pattern.test(zip) ) {
				$(this).addClass('invalid');
				show_inline_error($(this), 'Please enter a valid zip/postal code');
			} else {
				$(this).removeClass('invalid');
				hide_inline_error($(this));
			}
		});

		// Clear inline errors on input
		$reg_form.find('input, select').on('input change', function() {
			$(this).removeClass('invalid');
			hide_inline_error($(this));
		});

		function show_inline_error($field, message) {
			var $error = $field.siblings('.afa-field-error');

			if ( $error.length === 0 ) {
				$error = $('<span class="afa-field-error"></span>');
				$field.after($error);
			}

			$error.text(message).show();
		}

		function hide_inline_error($field) {
			$field.siblings('.afa-field-error').hide();
		}

		// Manual "already a member" login link
		$wrapper.on('click', '.afa-login-link', function(e) {
			e.preventDefault();
			swap_to_login_form();
		});

	});

})(jQuery);

// TRIGGERS AND COOKIES

jQuery( document ).ready(function() {

	// activate or close mm form
	// ===========================
	jQuery('.mission-membership-trigger').on("click", function(event) {
		event.preventDefault();
		jQuery("html, body").animate({ scrollTop: 0 }, "slow");
		jQuery(".browsing").hide();
		jQuery(".gated").show();
		jQuery('.mm-popup').slideDown('fast');
	});
	jQuery('#logo-main, .afa-homepage-image-link, .logo-container > a').on("click", function(event) {
		if (event.altKey) {
			event.preventDefault();
			jQuery(".browsing").show();
			jQuery(".gated").hide();
			jQuery('.mm-popup').slideDown('fast');
		}
	});
	jQuery('.mm-popup .mm-popup-close').on("click", function(event) {
		event.preventDefault();
		reset_mm_cookie();
		reset_mm_status('dismissed');
		jQuery('.mm-popup').slideUp('fast');
	});
	jQuery('.mm-popup .mm-popup-close-internal').on("click", function(event) {
		event.preventDefault();
		reset_mm_cookie();
		reset_mm_status('dismissed');
		jQuery('.mm-popup').slideUp('fast');
	});

});

// display mm form
// ===========================
function show_mm_form() {

	if (document.body.classList.contains('logged-in')) {
		reset_mm_status('saml_login')
		reset_mm_cookie();
		return;
	}

	var form_status = get_cookie('afa_mm_status');

	if (form_status == 'completed' || form_status == 'sf_error' || form_status == 'saml_login') {
		return;
	}

	var page_count = parseInt(get_cookie('afa_mm_trigger'));
	var trigger_threshold = window.location.href.includes('airandspaceforces.com') ? 15 : 3;

	if (page_count > trigger_threshold) {
		jQuery( document ).ready(function() {
			jQuery("html, body").animate({ scrollTop: 0 }, "slow");
			jQuery('.mm-popup').slideDown('fast');
		});
	}

}

// update mm cookies
// cookie is created by php
// ===========================
function increment_mm_cookie() {

	var page_count = parseInt(get_cookie('afa_mm_trigger'));

	if ( page_count === null || page_count !== page_count ) {
		reset_mm_cookie();
		reset_mm_status();
	} else {
		page_count++;
	}

	if (page_count) {
		var expires = new Date();
		expires.setTime(expires.getTime() + (3600 * 1000));

		document.cookie = "afa_mm_trigger=" + page_count
			+ ";expires=" + expires.toUTCString()
			+ ";path=/";
	}

}

// reset mm status cookie
// ===========================
function reset_mm_status(new_status) {

	var current_status = get_cookie('afa_mm_status');

	if ( current_status == 'saml_login' || current_status == 'completed' ) {
		return;
	}

	if ( ! new_status ) {
		new_status = 'none';
	}

	//console.log("reset status from " + current_status + " to " + new_status);

	var duration = 3600 * 1000;
	if ( new_status == 'saml_login' || new_status == 'completed' ) {
		duration = duration * 24 * 5
	}

	var expires = new Date();
	expires.setTime(expires.getTime() + duration);

	document.cookie = "afa_mm_status=" + new_status
		+ ";expires=" + expires.toUTCString()
		+ ";path=/";

}

// reset mm counter cookie
// ===========================
function reset_mm_cookie(count) {

	if ( ! count ) {
		count = 0;
	}

	//console.log("reset count to " + count);

	var expires = new Date();
	expires.setTime(expires.getTime() + (3600 * 1000));

	document.cookie = "afa_mm_trigger=" + count.toString()
		+ ";expires=" + expires.toUTCString()
		+ ";path=/";

}

// helper function
// ===========================
function get_cookie(name) {

	let value = `; ${document.cookie}`;
	let parts = value.split(`; ${name}=`);

	//console.log("get_cookie " + name);
	//console.log(value);

	if (parts.length === 2) {
		return parts.pop().split(';').shift();
	}

	return null; // Return null if the cookie is not found

}

increment_mm_cookie();
show_mm_form();
