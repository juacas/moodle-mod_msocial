<?php
// This file is part of MSocial activity for Moodle http://moodle.org/
//
// MSocial for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// MSocial for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with MSocial for Moodle.  If not, see <http://www.gnu.org/licenses/>.
namespace mod_msocial\connector;
defined('MOODLE_INTERNAL') || die();

/** Curl wrapper for OAuth */
class OAuthCurl {

    public function __construct() {
    }

    public static function fetch_data($url) {
        $options = [
                        CURLOPT_RETURNTRANSFER => true, // ...return web page.
                        CURLOPT_HEADER => false, // ...don't return headers.
                        CURLOPT_FOLLOWLOCATION => true, // ...follow redirects.
                        CURLOPT_SSL_VERIFYPEER => false
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return $header;
    }
}

/**
 * Twitter-API-PHP : Simple PHP wrapper for the v1.1 API
 *
 * PHP version 5.3.10
 *
 * @category Awesomeness
 * @package  Twitter-API-PHP
 * @author   James Mallison <me@j7mbo.co.uk>
 * @license  MIT License
 * @link     http://github.com/j7mbo/twitter-api-php
 */
class TwitterAPIExchange {

    /**
     * @var string
     */
    private $oauthaccesstoken;

    /**
     * @var string
     */
    private $oauthaccesstokensecret;

    /**
     * @var string
     */
    private $consumerkey;

    /**
     * @var string
     */
    private $consumersecret;

    /**
     * @var array
     */
    private $postfields;

    /**
     * @var string
     */
    private $getfield;

    /**
     * @var mixed
     */
    protected $oauth;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $requestmethod;

    /**
     * Create the API access object. Requires an array of settings::
     * oauth access token, oauth access token secret, consumer key, consumer secret
     * These are all available by creating your own application on dev.twitter.com
     * Requires the cURL library
     *
     * @throws \Exception When cURL isn't installed or incorrect settings parameters are provided
     *
     * @param array $settings
     */
    public function __construct(array $settings) {
        if (!in_array('curl', get_loaded_extensions())) {
            throw new Exception('You need to install cURL, see: http://curl.haxx.se/docs/install.html');
        }

        if (!isset($settings['oauth_access_token'])
                || !isset($settings['oauth_access_token_secret'])
                || !isset($settings['consumer_key'])
                || !isset($settings['consumer_secret'])) {
            throw new Exception('Make sure you are passing in the correct parameters');
        }

        $this->oauthaccesstoken = $settings['oauth_access_token'];
        $this->oauthaccesstokensecret = $settings['oauth_access_token_secret'];
        $this->consumerkey = $settings['consumer_key'];
        $this->consumersecret = $settings['consumer_secret'];
    }

    /**
     * Set postfields array, example: array('screen_name' => 'J7mbo')
     *
     * @param array $array Array of parameters to send to API
     *
     * @throws \Exception When you are trying to set both get and post fields
     *
     * @return TwitterAPIExchange Instance of self for method chaining
     */
    public function set_postfields(array $array) {
        if (!is_null($this->get_getfield())) {
            throw new Exception('You can only choose get OR post fields.');
        }

        if (isset($array['status']) && substr($array['status'], 0, 1) === '@') {
            $array['status'] = sprintf("\0%s", $array['status']);
        }

        $this->postfields = $array;

        // Rebuild oAuth.
        if (isset($this->oauth['oauth_signature'])) {
            $this->build_oauth($this->url, $this->requestmethod);
        }

        return $this;
    }

    /**
     * Set getfield string, example: '?screen_name=J7mbo'
     *
     * @param string $string Get key and value pairs as string
     *
     * @throws \Exception
     *
     * @return \TwitterAPIExchange Instance of self for method chaining
     */
    public function set_getfield($string) {
        if (!is_null($this->get_postfields())) {
            throw new Exception('You can only choose get OR post fields.');
        }

        $getfields = preg_replace('/^\?/', '', explode('&', $string));
        $params = array();

        foreach ($getfields as $field) {
            if ($field !== '') {
                list($key, $value) = explode('=', $field);
                $params[$key] = $value;
            }
        }

        $this->getfield = '?' . http_build_query($params);
        $this->getfield = str_replace("&amp;", "&", $this->getfield);
        return $this;
    }

    /**
     * Get getfield string (simple getter)
     *
     * @return string $this->getfields
     */
    public function get_getfield() {
        return $this->getfield;
    }

    /**
     * Get postfields array (simple getter)
     *
     * @return array $this->postfields
     */
    public function get_postfields() {
        return $this->postfields;
    }

    /**
     * Build the Oauth object using params set in construct and additionals
     * passed to this method. For v1.1, see: https://dev.twitter.com/docs/api/1.1
     *
     * @param string $url           The API url to use. Example: https://api.twitter.com/1.1/search/tweets.json
     * @param string $requestmethod Either POST or GET
     *
     * @throws \Exception
     *
     * @return \TwitterAPIExchange Instance of self for method chaining
     */
    public function build_oauth($url, $requestmethod) {
        if (!in_array(strtolower($requestmethod), array('post', 'get'))) {
            throw new Exception('Request method must be either POST or GET');
        }

        $consumerkey = $this->consumerkey;
        $consumersecret = $this->consumersecret;
        $oauthaccesstoken = $this->oauthaccesstoken;
        $oauthaccesstokensecret = $this->oauthaccesstokensecret;

        $oauth = array(
            'oauth_consumer_key' => $consumerkey,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $oauthaccesstoken,
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );

        $getfield = $this->get_getfield();

        if (!is_null($getfield)) {
            $getfields = str_replace('?', '', explode('&', $getfield));

            foreach ($getfields as $g) {
                $split = explode('=', $g);

                // In case a null is passed through.
                if (isset($split[1])) {
                    $oauth[$split[0]] = urldecode($split[1]);
                }
            }
        }

        $postfields = $this->get_postfields();

        if (!is_null($postfields)) {
            foreach ($postfields as $key => $value) {
                $oauth[$key] = $value;
            }
        }

        $baseinfo = $this->build_base_string($url, $requestmethod, $oauth);
        $compositekey = rawurlencode($consumersecret) . '&' . rawurlencode($oauthaccesstokensecret);
        $oauthsignature = base64_encode(hash_hmac('sha1', $baseinfo, $compositekey, true));
        $oauth['oauth_signature'] = $oauthsignature;

        $this->url = $url;
        $this->requestmethod = $requestmethod;
        $this->oauth = $oauth;

        return $this;
    }

    /**
     * Perform the actual data retrieval from the API
     *
     * @param boolean $return      If true, returns data. This is left in for backward compatibility reasons
     * @param array   $curloptions Additional Curl options for this request
     *
     * @throws \Exception
     *
     * @return string json If $return param is true, returns json data.
     */
    public function perform_request($return = true, $curloptions = array()) {
        if (!is_bool($return)) {
            throw new Exception('performRequest parameter must be true or false');
        }

        $header = array($this->build_authorization_header($this->oauth), 'Expect:');
        $header[] = 'Content-Type: application/x-www-form-urlencoded';
        $getfield = $this->get_getfield();
        $postfields = $this->get_postfields();

        $options = array(
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false
                ) + $curloptions;

        if (!is_null($postfields)) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($postfields);
        } else {
            if ($getfield !== '') {
                $options[CURLOPT_URL] .= $getfield;
            }
        }

        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $json = curl_exec($feed);

        if (($error = curl_error($feed)) !== '') {
            curl_close($feed);

            throw new \Exception($error);
        }

        curl_close($feed);

        return $json;
    }

    /**
     * Private method to generate the base string used by cURL
     *
     * @param string $baseuri
     * @param string $method
     * @param array  $params
     *
     * @return string Built base string
     */
    private function build_base_string($baseuri, $method, $params) {
        $return = array();
        ksort($params);

        foreach ($params as $key => $value) {
            $return[] = rawurlencode($key) . '=' . rawurlencode($value);
        }

        return $method . "&" . rawurlencode($baseuri) . '&' . rawurlencode(implode('&', $return));
    }

    /**
     * Private method to generate authorization header used by cURL
     *
     * @param array $oauth Array of oauth data generated by buildOauth()
     *
     * @return string $return Header used by cURL for request
     */
    private function build_authorization_header(array $oauth) {
        $return = 'Authorization: OAuth ';
        $values = array();

        foreach ($oauth as $key => $value) {
            if (in_array($key,
                            array('oauth_consumer_key', 'oauth_nonce', 'oauth_signature',
                        'oauth_signature_method', 'oauth_timestamp', 'oauth_token', 'oauth_version'))) {
                $values[] = "$key=\"" . rawurlencode($value) . "\"";
            }
        }

        $return .= implode(', ', $values);
        return $return;
    }

    /**
     * Helper method to perform our request
     *
     * @param string $url
     * @param string $method
     * @param string $data
     * @param array  $curloptions
     *
     * @throws \Exception
     *
     * @return string The json response from the server
     */
    public function request($url, $method = 'get', $data = null, $curloptions = array()) {
        if (strtolower($method) === 'get') {
            $this->set_getfield($data);
        } else {
            $this->set_postfields($data);
        }

        return $this->build_oauth($url, $method)->perform_request(true, $curloptions);
    }

}
