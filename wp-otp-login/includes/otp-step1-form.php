<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
$messages = array(
    'generic_error' => 'Si è verificato un errore, riprova.',
    'rate_limited'  => 'Troppi tentativi. Riprova più tardi.',
    'expired_otp'   => 'Il codice OTP è scaduto, richiedine uno nuovo.',
    'captcha_error' => 'Verifica il reCAPTCHA prima di procedere.'
);

// Controllo opzioni reCAPTCHA
$captcha_enabled = get_option('wp_otp_login_captcha_enabled', false);
$site_key = get_option('wp_otp_login_site_key', '');
$secret_key = get_option('wp_otp_login_secret_key', '');

// Se captcha abilitato e chiavi presenti, prepara l'inclusione
if ( $captcha_enabled && $site_key && $secret_key ) {
    echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<title><?php _e('Accedi con OTP'); ?></title>
<?php wp_admin_css( 'login', true ); ?>
<meta name="viewport" content="width=device-width" />
</head>
<body class="login no-js login-action-login">
<form name="loginform" id="loginform" action="<?php echo esc_url( site_url('wp-login.php?action=otp_request') ); ?>" method="post">
    <?php wp_nonce_field('otp_request_nonce'); ?>
    <p>
        <label for="user_login"><?php _e('Nome utente o Email'); ?></label>
        <input type="text" name="log" id="user_login" class="input" value="" size="20" />
    </p>
    <?php if ( isset($messages[$error]) ) : ?>
        <div class="error"><strong>Errore:</strong> <?php echo esc_html($messages[$error]); ?></div>
    <?php endif; ?>
    <?php if ( $captcha_enabled && $site_key && $secret_key ) : ?>
        <p>
            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
        </p>
    <?php endif; ?>
    <p class="submit" style="width: auto;">
        <input type="submit" name="otp_request_submit" id="otp_request_submit" class="button button-primary button-large" value="Invia OTP" />
    </p>
</form>
<p style="text-align:center;"><a href="<?php echo wp_login_url(); ?>">Torna al login classico</a></p>
</body>
</html>
