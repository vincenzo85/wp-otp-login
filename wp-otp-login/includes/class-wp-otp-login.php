<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_OTP_Login {

    const OTP_VALIDITY_MINUTES = 5;
    const OTP_REQUEST_LIMIT = 5;
    const OTP_REQUEST_WINDOW = 3600;
    const OTP_ATTEMPT_LIMIT = 5;
    const OTP_ATTEMPT_WINDOW = 3600;

    public function __construct() {
        // Intercetta la pagina di login prima che venga generata la form standard
        add_action( 'login_init', array( $this, 'maybe_show_otp_screens' ) );

        // Processa richieste (mantieni la logica di richiesta OTP e verifica OTP, nonce, rate limiting come nel codice avanzato)
        add_action( 'login_form_otp_request', array( $this, 'process_otp_request' ) );
        add_action( 'login_form_otp_verify', array( $this, 'process_otp_verification' ) );

        // In aggiunta, mostra un link nella schermata di login classica solo se non siamo in OTP mode
        add_action( 'login_form', array( $this, 'add_otp_link' ) );

        // Skippa l'autenticazione con password in OTP mode
        add_filter( 'authenticate', array( $this, 'skip_password_auth_in_otp_mode' ), 20, 3 );
    }

    public function maybe_show_otp_screens() {
        if ( isset( $_GET['otp'] ) ) {
            $step = sanitize_text_field($_GET['otp']);

            // Carica lo stile login di WordPress
            login_header( $step == '1' ? __('Accedi con OTP') : __('Verifica OTP'), '', array() );

            if ( $step == '1' ) {
                // Schermata richiesta OTP
                $nonce = wp_create_nonce('otp_request_nonce');
                include WP_OTP_LOGIN_DIR . 'includes/otp-step1-form.php';
            } elseif ( $step == '2' ) {
                // Schermata verifica OTP
                $nonce = wp_create_nonce('otp_verify_nonce');
                $user = isset($_GET['user']) ? sanitize_text_field($_GET['user']) : '';
                include WP_OTP_LOGIN_DIR . 'includes/otp-step2-form.php';
            }

            login_footer();
            exit; // Importante per evitare che WordPress mostri il form standard
        }
    }

    private function generate_otp() {
        return rand(100000, 999999);
    }

    private function send_otp_email( $user, $otp ) {
        $subject = 'Il tuo codice OTP';

        // Messaggio HTML
        $message = '
        <html>
        <head>
        <meta charset="UTF-8" />
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f3f3f3;
                margin: 0;
                padding: 0;
            }
            .container {
                background: #ffffff;
                max-width: 500px;
                margin: 50px auto;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            h1 {
                font-size: 20px;
                margin-bottom: 20px;
                color: #333333;
            }
            p {
                font-size: 16px;
                line-height: 1.5;
                color: #555555;
            }
            .otp-code {
                display: inline-block;
                background: #eee;
                padding: 10px 20px;
                font-size: 24px;
                font-weight: bold;
                letter-spacing: 5px;
                color: #333333;
                border-radius: 4px;
                margin: 20px 0;
            }
            .footer {
                font-size: 12px;
                color: #aaaaaa;
                margin-top: 30px;
            }
        </style>
        </head>
        <body>
            <div class="container">
                <h1>Il tuo codice OTP</h1>
                <p>Ciao '. esc_html( $user->display_name ) .',</p>
                <p>Hai richiesto un codice di accesso temporaneo (OTP). Inserisci il seguente codice nella schermata di verifica del login entro 5 minuti:</p>
                <div class="otp-code">'. esc_html( $otp ) .'</div>
                <p>Se non hai richiesto tu questo codice, ignora questa email.</p>
                <div class="footer">
                    © '.date('Y').' Tutti i diritti riservati.
                </div>
            </div>
        </body>
        </html>
        ';
    
        $headers = array(
            'From: <no-reply@carsa.loc>',
            'Content-Type: text/html; charset=UTF-8'
        );
        wp_mail( $user->user_email, $subject, $message, $headers );
    }

    public function add_otp_link() {
        // Se siamo in OTP mode non mostrare il link
        if ( isset( $_GET['otp'] ) ) return;
        echo '<p style="text-align:center;"><a href="'.esc_url( wp_login_url() . '?otp=1' ).'">Accedi con OTP</a></p>';
    }

    public function handle_otp_screens() {
        if ( isset( $_GET['otp'] ) && $_GET['otp'] == '1' ) {
            $nonce = wp_create_nonce('otp_request_nonce');
            include WP_OTP_LOGIN_DIR . 'includes/otp-step1-form.php';
            exit;
        } elseif ( isset( $_GET['otp'] ) && $_GET['otp'] == '2' ) {
            $nonce = wp_create_nonce('otp_verify_nonce');
            include WP_OTP_LOGIN_DIR . 'includes/otp-step2-form.php';
            exit;
        }
    }

    public function process_otp_request() {
        if ( isset( $_POST['otp_request_submit'] ) ) {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'otp_request_nonce' ) ) {
                wp_die('Nonce verification failed.', 'Security Error', array('response' => 403));
            }
            // Verifica reCAPTCHA
            $captcha_enabled = get_option('wp_otp_login_captcha_enabled', false);

            $secret_key = get_option('wp_otp_login_secret_key', '');

            if ( $captcha_enabled && ! empty( $secret_key ) ) {
                $recaptcha_response = isset($_POST['g-recaptcha-response']) ? sanitize_text_field($_POST['g-recaptcha-response']) : '';

                // Se il token è vuoto o non valido, blocca il flusso
                if ( empty($recaptcha_response) || ! $this->verify_recaptcha($recaptcha_response, $secret_key) ) {
                    wp_redirect( wp_login_url() . '?otp=1&error=captcha_error' );
                    exit;
                }
            }

            $username = sanitize_text_field( $_POST['log'] );
            if ( empty( $username ) ) {
                $this->redirect_with_error('otp=1', 'generic_error');
            }

            // Rate limit richieste OTP
            if ( ! $this->check_rate_limit('otp_request_' . md5($username), self::OTP_REQUEST_LIMIT, self::OTP_REQUEST_WINDOW) ) {
                $this->redirect_with_error('otp=1', 'rate_limited');
            }

            $user = get_user_by( 'login', $username );
            if ( ! $user ) {
                $user = get_user_by( 'email', $username );
            }

            // Errore generico per non rivelare se l'utente esiste
            if ( ! $user ) {
                $this->redirect_with_error('otp=1', 'generic_error');
            }

            $otp = $this->generate_otp();
            $otp_hash = wp_hash($otp);

            $otp_data = array(
                'user_id'  => $user->ID,
                'otp_hash' => $otp_hash,
                'expires'  => time() + (self::OTP_VALIDITY_MINUTES * 60)
            );

            set_transient('otp_data_' . $user->ID, $otp_data, self::OTP_VALIDITY_MINUTES * 60);

            $this->send_otp_email( $user, $otp );

            wp_redirect( wp_login_url() . '?otp=2&user='.urlencode($username) );
            exit;
        }
    }

    private function verify_recaptcha($response, $secret_key) {
        $endpoint = "https://www.google.com/recaptcha/api/siteverify";
        $remote_ip = $_SERVER['REMOTE_ADDR'];
    
        $response_data = wp_remote_post($endpoint, array(
            'body' => array(
                'secret' => $secret_key,
                'response' => $response,
                'remoteip' => $remote_ip
            )
        ));
    
        if ( is_wp_error( $response_data ) ) {
            return false;
        }
    
        $json = json_decode(wp_remote_retrieve_body($response_data), true);
    
        return ( isset($json['success']) && $json['success'] === true );
    }

    public function process_otp_verification() {
        if ( isset( $_POST['otp_verify_submit'] ) ) {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'otp_verify_nonce' ) ) {
                wp_die('Nonce verification failed.', 'Security Error', array('response' => 403));
            }

            $username = sanitize_text_field( $_POST['log'] );
            $entered_otp = sanitize_text_field( $_POST['otp_code'] );

            if ( empty($username) || empty($entered_otp) ) {
                $this->redirect_with_error('otp=2&user='.urlencode($username), 'generic_error');
            }

            // Rate limit tentativi verifica OTP
            if ( ! $this->check_rate_limit('otp_verify_' . md5($username), self::OTP_ATTEMPT_LIMIT, self::OTP_ATTEMPT_WINDOW) ) {
                $this->redirect_with_error('otp=2&user='.urlencode($username), 'rate_limited');
            }

            $user = get_user_by( 'login', $username );
            if ( ! $user ) {
                $user = get_user_by( 'email', $username );
            }

            if ( ! $user ) {
                $this->redirect_with_error('otp=2', 'generic_error');
            }

            $otp_data = get_transient('otp_data_' . $user->ID);
            if ( ! $otp_data || ! isset($otp_data['otp_hash'], $otp_data['expires']) ) {
                $this->redirect_with_error('otp=2&user='.urlencode($username), 'generic_error');
            }

            if ( time() > $otp_data['expires'] ) {
                // OTP scaduto
                delete_transient('otp_data_' . $user->ID);
                $this->redirect_with_error('otp=1', 'expired_otp');
            }

            // Verifica l'hash
            if ( wp_hash($entered_otp) === $otp_data['otp_hash'] ) {
                delete_transient('otp_data_' . $user->ID);
                wp_set_auth_cookie( $user->ID, false );
                wp_redirect( admin_url() );
                exit;
            } else {
                $this->redirect_with_error('otp=2&user='.urlencode($username), 'generic_error');
            }
        }
    }

    public function skip_password_auth_in_otp_mode( $user, $username, $password ) {
        if ( isset( $_GET['otp'] ) ) {
            return null;
        }
        return $user;
    }

    private function redirect_with_error($query, $error_code) {
        wp_redirect( wp_login_url() . '?' . $query . '&error=' . urlencode($error_code) );
        exit;
    }

    private function check_rate_limit($key, $limit, $window) {
        $attempts = get_transient($key);
        if ( false === $attempts ) {
            $attempts = 1;
            set_transient($key, $attempts, $window);
        } else {
            $attempts++;
            set_transient($key, $attempts, $window);
        }
        return ($attempts <= $limit);
    }
}
