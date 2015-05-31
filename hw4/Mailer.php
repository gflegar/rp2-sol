<?php

class Mailer {
    /**
     * Send confirmation mail to user.
     *
     * @param $username
     * @param $code
     */
    public static function send($username, $code) {
        mail(
            $username . '@student.math.hr',
            'Confirmation mail',
            'Confirmation link: ' . $_SERVER['HTTP_HOST']
            . '/register.php?code=' . $code);
    }
}

