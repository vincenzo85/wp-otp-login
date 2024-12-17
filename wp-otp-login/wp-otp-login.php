<?php
/**
 * Plugin Name: WP OTP Login (Enhanced Security Example)
 * Description: Login via OTP con misure di sicurezza migliorate (rate limiting, expiry, nonce, etc.).
 * Version:     1.2.0
 * Author:      Vincenzo Di Franco 
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_OTP_LOGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once WP_OTP_LOGIN_DIR . 'includes/class-wp-otp-login.php';

add_action( 'plugins_loaded', function() {
    new WP_OTP_Login();
});
// Aggiunge pagina di impostazioni
add_action( 'admin_menu', 'wp_otp_login_add_settings_page' );
function wp_otp_login_add_settings_page() {
    add_options_page(
        'Impostazioni OTP Login',
        'OTP Login',
        'manage_options',
        'wp-otp-login-settings',
        'wp_otp_login_settings_page_html'
    );
}

// Registra le impostazioni
add_action( 'admin_init', 'wp_otp_login_register_settings' );
function wp_otp_login_register_settings() {
    register_setting( 'wp_otp_login_settings', 'wp_otp_login_captcha_enabled', array(
        'type' => 'boolean',
        'default' => false
    ) );
    register_setting( 'wp_otp_login_settings', 'wp_otp_login_site_key', array(
        'type' => 'string',
        'default' => ''
    ) );
    register_setting( 'wp_otp_login_settings', 'wp_otp_login_secret_key', array(
        'type' => 'string',
        'default' => ''
    ) );

    add_settings_section(
        'wp_otp_login_captcha_section',
        'Impostazioni Google reCAPTCHA',
        'wp_otp_login_captcha_section_cb',
        'wp-otp-login-settings'
    );

    add_settings_field(
        'wp_otp_login_captcha_enabled',
        'Attiva reCAPTCHA',
        'wp_otp_login_field_checkbox',
        'wp-otp-login-settings',
        'wp_otp_login_captcha_section',
        array(
            'label_for' => 'wp_otp_login_captcha_enabled',
            'option_name' => 'wp_otp_login_captcha_enabled'
        )
    );

    add_settings_field(
        'wp_otp_login_site_key',
        'Site Key',
        'wp_otp_login_field_text',
        'wp-otp-login-settings',
        'wp_otp_login_captcha_section',
        array(
            'label_for' => 'wp_otp_login_site_key',
            'option_name' => 'wp_otp_login_site_key'
        )
    );

    add_settings_field(
        'wp_otp_login_secret_key',
        'Secret Key',
        'wp_otp_login_field_text',
        'wp-otp-login-settings',
        'wp_otp_login_captcha_section',
        array(
            'label_for' => 'wp_otp_login_secret_key',
            'option_name' => 'wp_otp_login_secret_key'
        )
    );
}

function wp_otp_login_captcha_section_cb() {
    echo '<p>Inserisci le chiavi di Google reCAPTCHA per proteggere la richiesta OTP.</p>';
}

function wp_otp_login_field_checkbox($args) {
    $value = get_option($args['option_name'], false);
    echo '<input type="checkbox" id="'.esc_attr($args['label_for']).'" name="'.esc_attr($args['option_name']).'" value="1" '.checked(1, $value, false).' />';
    echo '<p class="description">Spunta per abilitare reCAPTCHA nella richiesta OTP.</p>';
}

function wp_otp_login_field_text($args) {
    $value = get_option($args['option_name'], '');
    echo '<input type="text" id="'.esc_attr($args['label_for']).'" name="'.esc_attr($args['option_name']).'" value="'.esc_attr($value).'" class="regular-text" />';
}

// Pagina HTML delle impostazioni
function wp_otp_login_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset($_GET['settings-updated']) ) {
        add_settings_error('wp_otp_login_messages', 'wp_otp_login_message', 'Impostazioni salvate', 'updated');
    }

    settings_errors('wp_otp_login_messages');

    echo '<div class="wrap">';
    echo '<h1>Impostazioni OTP Login</h1>';
    echo '<form action="options.php" method="post">';
    settings_fields('wp_otp_login_settings');
    do_settings_sections('wp-otp-login-settings');
    submit_button('Salva Impostazioni');
    echo '</form></div>';
}

// Mostra una notifica di donazione nel backend
add_action( 'admin_notices', 'wp_otp_login_donation_notice' );

function wp_otp_login_donation_notice() {
    // Controlla se l'utente corrente è amministratore
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Recupera la data dell'ultima notifica
    $last_donation_notice = get_option( 'wp_otp_last_donation_notice', 0 );
    $current_time = time();

    // Mostra la notifica solo se è passato almeno un mese (30 giorni)
    if ( $current_time - $last_donation_notice < MONTH_IN_SECONDS ) {
        return;
    }

    // Link per la donazione (sostituisci con il tuo link PayPal)
    $donation_link = 'https://buy.stripe.com/4gw7ut4RX1zh8hO288';

    // Messaggio di notifica
    echo '
    <div class="notice notice-success is-dismissible">
        <p><strong>Supporta WP OTP Login!</strong></p>
        <p>Se questo plugin ti è utile, considera una piccola donazione per supportare lo sviluppo continuo. 🙏</p>
        <p>
            <a href="' . esc_url( $donation_link ) . '" class="button button-primary" target="_blank" rel="noopener noreferrer">Fai una Donazione</a>
        </p>
    </div>
    ';

    // Aggiorna la data dell'ultima notifica
    update_option( 'wp_otp_last_donation_notice', $current_time );
}
