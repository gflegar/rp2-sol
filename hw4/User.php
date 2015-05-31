<?php

class User {

    /**
     * Database connection.
     */
    private $db;

    /**
     * Create a new User object.
     */
    private function __construct() {
        if($this->createDB()) {
            $this->db = new PDO('sqlite:database.sqlite');
            $this->db->exec('CREATE TABLE users(' .
                'username VARCHAR(50) PRIMARY KEY, ' .
                'password VARCHAR(50) NOT NULL, ' .
                'code VARCHAR(64) NOT NULL, ' .
                'confirmed INTEGER NOT NULL)');
        } else {
            $this->db = new PDO('sqlite:database.sqlite');
        }
    }

    /**
     * Create database if it does not exist.
     */
    private function createDB() {
        if (!file_exists('database.sqlite')) {
            $handle = fopen('database.sqlite', 'w');
            if ($handle == False) {
                throw new Exception("Unable to create database");
            }
            fclose($handle);
            return True;
        }
        return False;
    }

    /**
     * Database connection instance.
     */
    private static $instance = Null;

    /**
     * Validation regex.
     */
    private static $validation = [
        'username' => '/^\\w{6,}$/',
        'password' => '/^.{6,}$/'
    ];

    /**
     * Validation error.
     */
    private static $validationError = [
        'username' =>
            'Username has to contain at least 6 alphanumeric characters',
        'password' =>
            'Password has to contain at least 6 characters.'
    ];

    /**
     * Get user instance.
     */
    private static function getInstance() {
        if (User::$instance === Null) {
            $instance = new User;
        }
        return $instance;
    }

    /**
     * Get data for user.
     *
     * @param $username
     *
     * @return array or False
     */
    public static function get($username) {
        $instance = User::getInstance();
        $st = $instance->db
            ->prepare('SELECT * FROM users WHERE username = :username');
        if ($st === False) {
            throw new Exception($instance->db->errorInfo()[2]);
        }
        $st->execute([':username' => $username]);
        return $st->fetch();
    }

    /**
     * Set user data.
     *
     * @param $data User data.
     *
     * @return True or error message
     */
    public static function set($data) {
        $result = User::validate($data);
        if ($result === True) {
            $instance = User::getInstance();
            $st = $instance->db
                ->prepare('INSERT INTO users VALUES ' .
                    '(:username, :password, :code, :confirmed)');
            $st->execute([
                ':username'  => $data['username'],
                ':password'  => md5($data['password']),
                ':code'      => $data['code'],
                ':confirmed' => $data['confirmed']
            ]);
        }
        return $result;
    }

    /**
     * Get username by confirmation code.
     *
     * @param $code
     *
     * @return user data
     */
    public static function getByCode($code) {
        $instance = User::getInstance();
        $st = $instance->db
            ->prepare('SELECT * FROM users WHERE code = :code ' .
                      'AND confirmed = 0');
        $st->execute([':code' => $code]);

        return $st->fetch();
    }

    /**
     * Confirm user
     */
    public static function confirm($username) {
        $instance = User::getInstance();
        $st = $instance->db
            ->prepare('UPDATE users SET confirmed=1 WHERE ' .
                      'username = :username');
        $st->execute([':username' => $username]);
    }

    /**
     * Validate user data.
     *
     * @param $data user data
     *
     * @return True or error message
     */
    private static function validate($data) {
        foreach($data as $key => $value) {
            if (array_key_exists($key, User::$validation) and
                    preg_match(User::$validation[$key], $value) == 0) {
                return User::$validationError[$key];
            }
        }
        return True;
    }
}

