<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
$user   = isset($_GET['user']) ? sanitize_text_field($_GET['user']) : '';

$messages = array(
    'generic_error' => 'Si è verificato un errore, riprova.',
    'rate_limited'  => 'Troppi tentativi. Riprova più tardi.'
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<title><?php _e('Verifica OTP'); ?></title>
<?php wp_admin_css( 'login', true ); ?>
<meta name="viewport" content="width=device-width" />
</head>
<body class="login no-js login-action-login">
<form name="loginform" id="loginform" action="<?php echo esc_url( site_url('wp-login.php?action=otp_verify') ); ?>" method="post">
    <?php wp_nonce_field('otp_verify_nonce'); ?>
    <p>
        <label for="user_login"><?php _e('Nome utente o Email'); ?></label>
        <input type="text" name="log" id="user_login" class="input" value="<?php echo esc_attr($user); ?>" size="20" />
    </p>
    <p>
        <label for="otp_code"><?php _e('Codice OTP'); ?></label>
        <input type="text" name="otp_code" id="otp_code" class="input" value="" size="20" />
    </p>
    <?php if ( isset($messages[$error]) ) : ?>
        <div class="error"><strong>Errore:</strong> <?php echo esc_html($messages[$error]); ?></div>
    <?php endif; ?>
    <p class="submit" style="width: auto;">
        <input type="submit" name="otp_verify_submit" id="otp_verify_submit" class="button button-primary button-large" value="Verifica OTP" />
    </p>
</form>
<p style="text-align:center;"><a href="<?php echo wp_login_url(); ?>">Torna al login classico</a></p>
</body>
</html>
