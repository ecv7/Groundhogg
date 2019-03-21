<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-03-21
 * Time: 12:11 PM
 */

if ( ! class_exists( 'PHPMailer' ) ){
    require_once ABSPATH . WPINC . '/class-phpmailer.php';
    require_once ABSPATH . WPINC . '/class-smtp.php';
}

class GH_SS_Mailer extends PHPMailer
{
    /**
     * Create a message and send it.
     * Uses the sending method specified by $Mailer.
     * @throws phpmailerException
     * @return boolean false on error - See the ErrorInfo property for details of the error.
     */
    public function send()
    {
        try {

            if (!$this->preSend()) {
                return false;
            }

            $message = $this->getSentMIMEMessage();

            $response = WPGH()->service_manager->request( 'emails/wp_mail/v2', [
                    'SentMIMEMessage' => $message,
            ], 'POST' );

        } catch (phpmailerException $exc) {

            $this->mailHeader = '';
            $this->setError($exc->getMessage());

            if ($this->exceptions) {
                throw $exc;
            }

            return false;
        }

        if ( is_wp_error( $response ) ){
            throw new phpmailerException( $response->get_error_message(), self::STOP_CRITICAL );
        }

        return true;
    }
}