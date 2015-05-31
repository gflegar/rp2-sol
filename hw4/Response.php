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
    private $flashType = Null;

    /**
     * Flash message.
     */
    private $flashMessage = Null;

    /**
     * Create a new Response object.
     *
     * @param $body Response body.
     */
    public function __construct($body) {
        $this->body = $body;
    }

    /**
     * Add a header to response.
     *
     * @param $header Header content.
     *
     * @return $this
     */
    public function addHeader($header) {
        $this->headers[] = $header;
        return $this;
    }

    /**
     * Redirect to given address.
     *
     * @param $location Redirect address.
     *
     * @return $this
     */
    public function redirect($location) {
        return $this->addHeader('Location: ' . $location);
    }

    /**
     * Add flash message to response.
     *
     * @param $type message type
     * @param $message message content
     *
     * @return $this
     */
    public function flash($type, $message) {
        $this->flashType = $type;
        $this->flashMessage = $message;
        return $this;
    }

    /**
     * Respond with given response.
     *
     * @return void
     */
    public function respond() {
        foreach($this->headers as $header) {
            header($header);
        }
        echo sprintf($this->body, $this->createFlashMessage());
    }

    /**
     * Add flash message to session.
     *
     * @return string
     */
    private function createFlashMessage() {
        $message = Null;
        $type = Null;
        if (session_status() == PHP_SESSION_ACTIVE) {
            if (isset($_SESSION['flash_message'])) {
                $message = $_SESSION['flash_message'];
                $type = $_SESSION['flash_type'];
            }
            if ($this->flashMessage !== Null) {
                $_SESSION['flash_message'] = $this->flashMessage;
                $_SESSION['flash_type'] = $this->flashType;
            } else {
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
            }
        }
        return ($message === Null) ? "" :
            sprintf(file_get_contents('flash.html'), $type, $message);
    }
}

/**
 * Create a new Response object.
 *
 * @param $body Response body;
 *
 * @return Response
 */
function response($body = '') {
    return new Response($body);
}

/**
 * Create response from file.
 *
 * @param $filename File that contains response body.
 *
 * @return Response
 */
function view($filename) {
    return new Response(file_get_contents($filename));
}

