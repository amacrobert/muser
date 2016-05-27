<?php
/**
 * Authentication
 *
 * Handles auth-related functionality.
 */

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\Session\Session;
use AppBundle\Service\CurlWrapper;

class Authentication {

    private $insta;
    private $message;

    public function __construct($insta) {
        $this->insta = $insta;
    }

    /**
     * Get the user's information
     *
     * @param Request $request
     *   The page request
     * @return mixed
     *   If the user is logged in, an object containing access_token and user
     *   If the user is not logged in, false
     */
    public function auth($request) {
        $session = new Session();
        $access_token = $session->get('access_token', null);
        $code = $request->query->get('code', null);

        // User already has an access token. Return it.
        if ($access_token) {
            return (object)[
                'access_token' => $access_token,
                'user' => json_decode($session->get('user')),
            ];
        }

        // User doesn't have an access token but has a code. Exchange code for token.
        if ($code) {
            $result = $this->insta->authenticate($code);

            // Error getting auth token
            if (!empty($result->code) && $result->code == 400) {
                $this->setMessage($result->error_message);
                return false;
            }

            // Successfully authenicated user. Store info in session.
            $session->set('access_token', $result->access_token);
            $session->set('user', json_encode($result->user));

            return (object)[
                'access_token' => $result->access_token,
                'user' => $result->user,
            ];
        }

        // No access token and no code - user is not logged in
        return false;
    }

    public function logout() {
        $session = new Session();
        $session->remove('access_token');
        $session->remove('user');
    }

    public function getMessage() {
        return $this->message;
    }

    protected function setMessage($message = '') {
        $this->message = $message;
        return $this;
    }
}
