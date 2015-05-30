<?php

class Response {
    /**
     * Response body.
     */
    private $body;

    /**
     * Response headers.
     */
    private $headers = [];

    /**
     * Flash message type.
     */
    private $flashType = "";
    /**
     * Flash message.
     */
    private $flashMessage = "";
    /**
     * Flash data container.
     */
    private $flashWrapper = <<<HTML
<div class="%s">
    %s
</div>
HTML;

    /**
     * Construct a response with given body.
     *
     * @param $body response body
     */
    public function __construct($body) {
        $this->body = $body;
    }

    /**
     * Add header to response.
     *
     * @param $header
     */
    public function withHeader($header) {
        $this->headers[] = $header;
        return $this;
    }

    /**
     * Redirect to given location.
     */
    public function redirect($page) {
        return $this->withHeader('Location: ' . $_SERVER['PHP_SELF'] .
            '?page=' . $page);
    }

    /**
     * Add flash message.
     */
    public function flash($type, $message) {
        $this->flashType = $type;
        $this->flashMessage = $message;
        return $this;
    }

    /**
     * Run the response.
     */
    public function respond() {
        foreach ($this->headers as $header) {
            header($header);
        }

        echo sprintf($this->body, $this->getFlashData());
    }

    private function getFlashData() {
        $flashData = "";
        if (session_status() != PHP_SESSION_NONE) {
            if (isset($_SESSION['flash_message'])) {
                $flashData = sprintf($this->flashWrapper,
                    $_SESSION['flash_type'],
                    $_SESSION['flash_message']);
            }
            if ($this->flashMessage != "") {
                $_SESSION['flash_type'] = $this->flashType;
                $_SESSION['flash_message'] = $this->flashMessage;
            } else {
                unset($_SESSION['flash_type']);
                unset($_SESSION['flash_message']);
            }
        }
        return $flashData;
    }
}

/**
 * Create a new Response object.
 *
 * @param $body
 */
function response($body = '') {
    return new Response($body);
}

abstract class Page {

    /**
     * A seecret string used by session storage.
     */
    protected $seecret = "my seecret string";

    /**
     * Page header HTML.
     */
    protected $headHTML = <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>%s</title>
        <style type="text/css">
body {
    width: 960px;
    margin: auto;
}

.message, .error {
    width: 400px;
    margin: 40px auto;
    padding: 20px;
    border: 1px solid black;
    border-radius: 10px;
}

.message {
    background: rgba(46, 204, 113, 0.5);
}

.error {
    background: rgba(231, 76, 60, 0.5);
}

form {
    padding: 20px;
    background: #f0f0f0;
    border: 1px solid black;
    border-radius: 10px;
}

form.send input {
    width: 100%%%%;
}

form.send textarea {
    width: 100%%%%;
}

form.center {
    width: 400px;
    margin: 40px auto;
}

form.center input, form.center a {
    float: right;
}

form div {
    padding: 10px 0;
}

nav {
    background: #2e8ece;
    color: white;
}

nav ul {
    overflow: hidden;
    padding: 0;
    margin: 0;
}

nav li {
    display: block;
    padding: 5px;
    float: right;
}

nav a {
    color: white;
    text-decoration: none;
}

h2 {
    border-bottom: 1px solid black;
}

section {
    background: #f0f0f0;
    border: 1px solid black;
    border-radius: 10px;
    padding: 10px;
    margin: 20px 0;
}

section pre {
    background: white;
    border: 1px solid black;
    padding: 10px;
}

section a {
    float: right;
    color: blue;
}
        </style>
    </head>
    <body>
HTML;

    /**
     * Page footer HTML.
     */
    protected $footerHTML = <<<HTML
    %s
    </body>
</html>
HTML;

    /**
     * Construct a new Page object.
     */
    public function __construct($title = '') {
        $this->headHTML = sprintf($this->headHTML, $title);
        session_start();
    }

    /**
     * Respond to GET request.
     */
    abstract protected function get();

    /**
     * Respond to POST request.
     */
    abstract protected function post();

    /**
     * Respond to request.
     */
    public function getResponse() {
        switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            return $this->get();
        case 'POST':
            return $this->post();
        default:
            throw new Exception("Unsuported method");
        }
    }

    /**
     * Raise an exception with given message.
     *
     * @param string $errorMsg Message to raise
     */
    protected function raiseError($errorMsg) {
        throw new Exception($errorMsg);
    }
}


class FileModel {
    /**
     * File containing data.
     */
    protected $filename;

    /**
     * Create a new FileModel object.
     */
    public function __construct($filename) {
        $this->filename = $filename;
        $this->createFile();
    }

    /**
     * Open underlaying file.
     */
    protected function open($method) {
        $handle = fopen($this->filename, $method) or
            $this->raiseError($PHP_ERRMSG);
        return $handle;
    }

    /**
     * Close underlaying file.
     */
    protected function close($handle) {
        fclose($handle) or
            $this->raiseError($PHP_ERRMSG);
    }

    /**
     * Create the file if it does not exist.
     */
    private function createFile() {
        if (!file_exists($this->filename)) {
            $handle = $this->open('w');
            $this->close($handle);
        }
    }
    /**
     * Raise an error.
     */
    private function raiseError($data) {
        throw new Exception($data);
    }
}

class User extends FileModel {

    /**
     * Create a new User object.
     */
    public function __construct() {
        parent::__construct('users.tsv');
    }

    /**
     * Get user data.
     */
    public function getData($username) {
        $handle = $this->open('r');
        $returnData = False;
        while (($data = fgetcsv($handle, 0, "\t")) !== False) {
            if ($data === Null) {
                $this->raiseError($PHP_ERRMSG);
            }
            if (count($data) < 2) {
                continue;
            }
            if ($data[0] == $username) {
                $returnData = $data;
                break;
            }
        }

        $this->close($handle);

        return $returnData;
    }

    /**
     * Create a new user.
     */
    public function create($username, $password) {
        if ($this->getData($username) !== False) {
            return False;
        }

        $handle = $this->open('a');

        fputcsv($handle, [$username, md5($password)], "\t") or
            $this->raiseError($PHP_ERRMSG);

        $this->close($handle);

        return True;
    }

}

class Message extends FileModel {
    /**
     * Create a new Message object.
     */
    public function __construct($username) {
        parent::__construct($username . '.json');
    }

    /**
     * Get messages from file.
     */
    public function getAll() {
        $ret = json_decode(file_get_contents($this->filename), true);
        return $ret == '' ? ['next' => '0', 'data' => []] : $ret;
    }

    /**
     * Add message to file.
     */
    public function create($from, $content) {
        $messages = $this->getAll();
        $id = $messages['next']++;
        $messages['data'][$id] = compact('from', 'content');
        file_put_contents(
            $this->filename,
            json_encode($messages, JSON_FORCE_OBJECT));
    }

    /**
     * Delete message from file.
     */
    public function delete($id) {
        $messages = $this->getAll();
        unset($messages['data'][$id]);
        file_put_contents(
            $this->filename,
            json_encode($messages, JSON_FORCE_OBJECT));
    }
}

class Auth {
    /**
     * Secret string used for sessions.
     */
    private static $secret = "my secret string";

    /**
     * Set authentification.
     */
    public static function set($username) {
        $_SESSION['login'] = $username;
        $_SESSION['token'] = md5($username . Auth::$secret);
    }

    /**
     * Check if user is authentificated.
     */
    public static function isAuth() {
        if (!isset($_SESSION['token']) or !isset($_SESSION['login'])) {
            return False;
        }
        return $_SESSION['token'] == md5($_SESSION['login'] . Auth::$secret);
    }

    /**
     * Clear session data.
     */
    public static function clear() {
        session_unset();
        session_destroy();
    }

    /**
     * Get user login.
     */
    public static function get() {
        if (!Auth::isAuth()) {
            return False;
        }
        return $_SESSION['login'];
    }
}

class LoginPage extends Page {
    /**
     * Login page HTML.
     */
    private $loginHTML = <<<HTML
<form method="POST" class="center">
    <div>
        <label for="username">Username:
            <input name="username" type="text">
        </label>
    </div>
    <div>
        <label for="password">Password:
            <input name="password" type="password">
        </label>
    </div>
    <div>
        <button type="submit">Login</button>
        <a href="?page=register"><button type="button">Register</button></a>
    </div>
</form>
HTML;

    /**
     * Create a new LoginPage object.
     */
    public function __construct() {
        parent::__construct('Login');
    }

    /**
     * Respond to get request.
     */
    protected function get() {
        if (Auth::isAuth()) {
            return response()->redirect('');
        }
        return response(
            $this->headHTML .
            $this->loginHTML .
            $this->footerHTML);
    }

    /**
     * Respond to post request.
     */
    protected function post() {
        $userData = (new User)->getData($_POST['username']);

        if ($userData === False or $userData[1] != md5($_POST['password'])) {
            return response()->redirect('login')->
                flash('error',"Wrong username or password.");
        }
        Auth::set($userData[0]);
        return response()->redirect('');
    }
}

class HomePage extends Page {

    /**
     * Home page start.
     */
    private $pageBodyStart = <<<HTML
<nav>
    <ul>
        <li><a href="?page=logout">Logout</a></li>
    </ul>
</nav>
<h1>Welcome %s</h1>
<h2>Your messages:</h2>
HTML;

    /**
     * Home page end.
     */
    private $pageBodyEnd = <<<HTML
<h2>Write message:</h2>
<form method="POST" class="send">
    <div>
        <label for="to">To:
            <input name="to" type="text">
        </label>
    </div>
    <div>
        <label for="content">Message:</label>
        <textarea name="content" rows="10"></textarea>
    </div>
    <div>
        <button type="submit">Send</button>
    </div>
</form>
</ul>
HTML;

    /**
     * Message item.
     */
    private $messageItem = <<<HTML
<section>
<span>From: %s</span> <a href="?page=/delete&id=%d">I have read this</a>
<pre>
%s
</pre>
</section>
HTML;

    /**
     * Create a new HomePage object.
     */
    public function __construct() {
        parent::__construct('Message exchange');
    }

    /**
     * Get user homepage.
     */
    protected function get() {
        if (!Auth::isAuth()) {
            return response()->redirect('login');
        }
        $body = $this->headHTML .
            sprintf($this->pageBodyStart, Auth::get()) .
            $this->renderMessages() .
            $this->pageBodyEnd .
            $this->footerHTML;
        return response($body);
    }

    /**
     * Handle post request.
     */
    protected function post() {
        if (!Auth::isAuth()) {
            return response()->redirect('login');
        }
        $username = $_POST['to'];
        $content = $_POST['content'];

        if ((new User)->getData($username)) {
            (new Message($username))->create(Auth::get(), $content);
        } else {
            return response()->redirect('')->
                flash('error', 'That user does not exist.');
        }

        return response()->redirect('')->flash('message', 'Message sent');
    }

    /**
     * Return users messages.
     */
    private function renderMessages() {
        $out = "";
        $messages = (new Message(Auth::get()))->getAll()['data'];
        foreach ($messages as $index => $message) {
            $out = $out . sprintf($this->messageItem,
                $message['from'],
                $index,
                $message['content']);
        }
        return $out;
    }
}

class RegisterPage extends Page {

    /**
     * Register page body.
     */
    private $pageBody = <<<HTML
<form method="POST" class="center">
    <div>
        <label for="username">Username:
            <input name="username" type="text">
        </label>
    </div>
    <div>
        <label for="password">Password:
            <input name="password" type="password">
        </label>
    </div>
    <div>
        <label for="repeat_password">Repeat password:
            <input name="repeat_password" type="password">
        </label>
    </div>
    <div>
        <button type="submit">Register</button>
    </div>
</form>
HTML;

    /**
     * Construct a new RegisterPage object.
     */
    public function __construct() {
        parent::__construct("Register");
    }

    /**
     * Respond to GET request.
     */
    protected function get() {
        if (Auth::isAuth()) {
            return response()->redirect('');
        }
        return response(
            $this->headHTML .
            $this->pageBody .
            $this->footerHTML
        );
    }

    /**
     * Respond to POST request.
     */
    protected function post() {
        if ($_POST['password'] != $_POST['repeat_password']) {
            return response()->redirect('register')->
                flash('error', 'Passwords do not match');
        }
        if ((new User)->create($_POST['username'], $_POST['password'])) {
            return response()->redirect('login')->
                flash('message', 'Registration successfull, login to start ' .
                      'messaging');
        } else {
            return response()->redirect('register')->
                flash('error', 'User with that name already exists');
        }
    }
}

class LogoutPage extends Page {
    /**
     * Create a new LogoutPage object.
     */
    public function __construct() {
        parent::__construct('');
    }

    /**
     * Log out current user.
     */
    protected function get() {
        Auth::clear();
        return response()->redirect('login');
    }

    protected function post() {
        $this->raiseError('Unsuported method');
    }
}

class DeleteMessagePage extends Page {
    /**
     * Create a new DeleteMessagePage object.
     */
    public function __construct() {
        parent::__construct('');
    }

    /**
     * Delete sent messaage.
     */
    protected function get() {
        if (!Auth::isAuth()) {
            return response()->redirect('login');
        }
        if (isset($_GET['id'])) {
            (new Message(Auth::get()))->delete($_GET['id']);
        }
        return response()->redirect('');
    }

    /**
     * Raise unsuported method error.
     */
    protected function post() {
        $this->raiseError('Unsuported method');
    }
}

class RouteService {
    /**
     * Registered routes.
     */
    private $routes;

    /**
     * Create a new RouteService object.
     */
    public function __construct($routes) {
        $this->routes = $routes;
    }

    /**
     * Route a request to registered route.
     */
    public function route($route) {
        if (!array_key_exists($route, $this->routes)) {
            throw new Exception('Unregistered route');
        }
        (new $this->routes[$route])->getResponse()->respond();
    }
}

$routeService = new RouteService([
    ''          => 'HomePage',
    'login'     => 'LoginPage',
    'logout'    => 'LogoutPage',
    'register'  => 'RegisterPage',
    '/delete'   => 'DeleteMessagePage'
]);

$routeService->route(isset($_GET['page']) ? $_GET['page'] : '');

