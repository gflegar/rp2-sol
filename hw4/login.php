<?php

require_once('Response.php');
require_once('RequestHandler.php');
require_once('User.php');
require_once('utils.php');
require_once('Mailer.php');

class LoginHandler extends RequestHandler {
    /**
     * Respond to GET request.
     *
     * @param $request Request
     */
    protected function get($request) {
        return view('login_form.html');
    }

    /**
     * Respond to POST request.
     *
     * @param $request Request
     */
    protected function post($request) {
        $login = $request['login'];
        $username = $request['username'];
        $password = $request['password'];
        $repPassword = $request['repeat_password'];
        if ($login == 'register') {
            return $this->register($username, $password, $repPassword);
        } else {
            return $this->login($username, $password);
        }
    }

    /**
     * Log in existing user.
     *
     * @param $username
     * @param $password
     */
    private function login($username, $password) {
        $data = User::get($username);
        if ($data == False or $data['password'] != md5($password)) {
            return response()->redirect('/login.php')
                ->flash('error', 'Wrong username or password');
        }

        if ($data['confirmed'] == 1) {
            return response('Welcome ' . $data['username'] . '!');
        } else {
            return response()->redirect('/login.php')
                ->flash('error', 'You need to confirm your email.');
        }
    }

    /**
     * Register new user.
     *
     * @param $useraname
     * @param $password
     * @param $repPassword
     */
    private function register($username, $password, $repPassword) {
        if ($password != $repPassword) {
            return response()->redirect('/login.php')
                ->flash('error', 'Passwords do not match');
        }

        if (User::get($username) !== False) {
            return response()->redirect('/login.php')
                ->flash('error', 'User with that username already exists.');
        }

        $code = randString(64);

        Mailer::send($username, $code);

        $result = User::set([
            'username' => $username,
            'password' => $password,
            'code'     => $code,
            'confirmed' => 0
        ]);

        if ($result === True) {
            return response()->redirect('/login.php')
                ->flash('message', 'You succesfuly registered.' .
                        ' Confirm your email and log in.');
        } else {
            return response()->redirect('/login.php')
                ->flash('error', $result);
        }
    }
}

session_start();
(new LoginHandler)->response()->respond();

