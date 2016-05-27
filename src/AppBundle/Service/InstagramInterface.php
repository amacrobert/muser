<?php
/**
 * InstagramInterface
 *
 * An interface for interacting with the instagram API.
 */

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\Session\Session;
use AppBundle\Service\CurlWrapper;

class InstagramInterface extends CurlWrapper {
    private $host;
    private $token;
    private $credentials;

    public function __construct($credentials) {
        $this->credentials = $credentials;
        $this->host = 'https://api.instagram.com/v1/';
        parent::__construct();
    }

    /**
     * Exchange a code for an auth token
     */
    public function authenticate($code) {
        $fields = [
            'client_id'     => $this->credentials['client_id'],
            'client_secret' => $this->credentials['client_secret'],
            'redirect_uri'  => $this->credentials['redirect_uri'],
            'grant_type'    => 'authorization_code',
            'code'          => $code,
        ];
        $uri = 'https://api.instagram.com/oauth/access_token';

        // Bypass $this->request because this is a special case
        $result = json_decode(parent::request($uri, $fields, 'post'));

        if (!empty($result->user->access_token)) {
            $this->setToken($result->user->access_token);
        }

        return $result;
    }

    private function setToken($token) {
        $this->token = $token;
        return $this;
    }

    /**
     * Get recent posts with a given tag
     *
     * @param string $tag
     *   The tag to search
     */
    public function getRecentTaggedMedia($tag) {
        $path = 'tags/' . $tag . '/media/recent';
        return $this->request($path);
    }

    /**
     * Get a single instagram post
     *
     * @param string $id
     *   The id of the media to fetch
     */
    public function getMedia($id) {
        $path = 'media/' . $id;
        return $this->request($path, [], 'get', false);
    }

    /**
     * Like a post
     *
     * @param $id
     *   The id of the media to like
     */
    public function like($id) {
        $path = 'media/' . $id . '/likes';
        return $this->request($path, [], 'post');
    }

    /**
     * Unlike a post
     *
     * @param $id
     *   The id of the media to like
     */
    public function unlike($id) {
        $path = 'media/' . $id . '/likes?';
        // The Instagram API expects the access token to be in the query, even
        // though DELETE accepts a body.
        $path .= http_build_query(['access_token' => $this->getToken()]);

        return $this->request($path, [], 'delete');
    }

    /**
     * Make a request to the API as an authenticated user.
     */
    public function request($path, $fields = array(), $method = 'get', $bypass_cache = true) {
        $uri = $this->host . $path;
        $fields['access_token'] = $this->getToken();

        return json_decode(parent::request($uri, $fields, $method, $bypass_cache));
    }

    protected function getToken() {
        if (empty($this->token)) {
            $session = new Session();
            $this->setToken($session->get('access_token', null));
        }

        return $this->token;
    }
}
