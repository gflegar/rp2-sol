<?php

require_once('Response.php');
require_once('RequestHandler.php');
require_once('User.php');

class RegisterHandler extends RequestHandler {
    /**
     * Respond to GET request.
     *
     * @param $request Request
     */
    protected function get($request) {
        $user = User::getByCode($request['code']);

        if ($user === False) {
            return response()->redirect('login.php')
                ->flash('error', 'Bad confirmation code.');
        }

        User::confirm($user['username']);

        return response()->redirect('login.php')
            ->flash('message', 'Email address confirmed.');
    }
}

session_start();
(new RegisterHandler)->response()->respond();

