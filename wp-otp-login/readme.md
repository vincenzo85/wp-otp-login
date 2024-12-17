# WP OTP Login

**Versione:** 1.2.0  
**Autore:** Vincenzo di Franco
**email** info@soluzioniweb.net


## Descrizione

WP OTP Login è un plugin per WordPress che aggiunge un metodo alternativo di autenticazione tramite OTP (One-Time Password) inviato via email. L’utente può scegliere se loggarsi con la password classica o richiedere un codice OTP via email. Una volta inserito l’OTP corretto, l’utente viene autenticato.

Il plugin permette inoltre di:

- Abilitare Google reCAPTCHA per impedire richieste automatiche eccessive.
- Impostare una scadenza per l’OTP e limitare il numero di tentativi di richiesta/verifica.

Questo plugin è un esempio dimostrativo ed è consigliato testare e adeguare le misure di sicurezza al proprio ambiente di produzione.

## Funzionalità

- **Login via Password o OTP:** L’utente può selezionare “Accedi con OTP” dalla pagina di login.  
- **OTP via Email:** Invio di un codice temporaneo che deve essere inserito entro un tempo limite.  
- **Limitazione Tentativi:** Rate limiting sia sulle richieste OTP che sui tentativi di verifica per ridurre i brute force.  
- **Integrazione reCAPTCHA:** Opzionale, attivabile dal pannello di impostazioni, per prevenire spam di richieste OTP.  
- **Pannello Impostazioni:** Permette di configurare reCAPTCHA (chiavi site e secret) e attivare/disattivare il CAPTCHA.

## Requisiti

- WordPress 5.0 o superiore.
- PHP 7.0 o superiore.
- Funzione `wp_mail()` funzionante o configurazione SMTP.
- (Opzionale) Chiavi Google reCAPTCHA (site_key e secret_key) valide.

## Installazione

1. Carica la cartella del plugin in `wp-content/plugins/` o installalo tramite il pannello di amministrazione di WordPress.
2. Attiva il plugin dalla pagina “Plugin” in WordPress.
3. Vai su **Impostazioni > OTP Login** per configurare eventuali chiavi reCAPTCHA e attivare/disattivare il CAPTCHA.

## Utilizzo

1. Alla pagina di login (`wp-login.php`), seleziona “Accedi con OTP”.
2. Inserisci il nome utente o l’email e, se reCAPTCHA è abilitato, risolvi il CAPTCHA.
3. Riceverai un OTP via email. Inseriscilo nella schermata successiva.
4. Se l’OTP è corretto, accederai alla bacheca di WordPress.

## Sicurezza e Limitazioni

- L’OTP ha una scadenza temporale per impedire il riutilizzo dopo un certo tempo.
- Il rate limiting riduce i tentativi di forza bruta.
- L’integrazione con reCAPTCHA aiuta a bloccare richieste automatiche.
- Per maggior sicurezza, consigliamo di usare HTTPS e valutare l’uso di meccanismi di protezione aggiuntivi (ad es. captcha anche nella fase di verifica OTP, ulteriore MFA, ecc.).

## Licenza

GPL v2 o successiva.

---