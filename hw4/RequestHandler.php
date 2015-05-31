<?php

abstract class RequestHandler {
    /**
     * Respond to GET request.
     *
     * @param $request Request data.
     *
     * @return Response
     */
    protected function get($request) {
        $this->raiseError('Unsuported method');
    }

    /**
     * Respond to POST request.
     *
     * @param $request Request data.
     *
     * @return Response
     */
    protected function post($request) {
        $this->raiseError('Unsuported method');
    }

    /**
     * Raise an error.
     *
     * @param $msg Error message.
     *
     * @return True
     */
    protected function raiseError($msg) {
        throw new Exception($msg);
        return True;
    }

    /**
     * Return response to given request.
     */
    public function response() {
        switch($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            return $this->get($_GET);
        case 'POST':
            return $this->post($_POST);
        default:
            $this->raiseError('Unsuported method');
        }
    }
}

