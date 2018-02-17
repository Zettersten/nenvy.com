<?php

/**
 * @version 2.0.0
 * @package OneDrive
 * @copyright © 2016 Perfect Web sp. z o.o., All rights reserved. https://www.perfect-web.co
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @author Piotr Moćko
 */
// No direct access
function_exists('add_action') or die;

/**
 * Oauth controller class.
 */
class LiveConnectClient
{

    protected $options;
    protected $http;
    protected $access_id       = 0;
    protected static $access   = array();
    protected static $token    = array();
    protected static $instance = null;

    public function __construct($options = null)
    {
        $ssl = (defined('FORCE_SSL_ADMIN') AND FORCE_SSL_ADMIN ? true : null );

        $this->options = array(
            'clientid' => get_option('pweb_onedrive_client_id'),
            'clientsecret' => get_option('pweb_onedrive_client_secret'),
            'sendheaders' => false,
            'authmethod' => 'get',
            'authurl' => 'https://login.live.com/oauth20_token.srf',
            'tokenurl' => 'https://login.live.com/oauth20_token.srf',
            'redirecturi' => PWebOneDrive::build_route(array('action' => 'callback'), $ssl),
            'userefresh' => true,
            'storetoken' => true,
            'usecookie' => true,
            'cookiename' => 'wl_auth',
            'timeout' => 25,
            'sslverify' => false
        );

        if (is_array($options))
            $this->options = array_merge($this->options, (array) $options);

        $this->http = new WP_Http;
    }

    public static function getInstance($options = null)
    {
        // Automatically instantiate the singleton object if not already done.
        if (empty(self::$instance))
        {
            self::setInstance(new LiveConnectClient($options));
        }
        return self::$instance;
    }

    public static function setInstance($instance)
    {
        if (($instance instanceof LiveConnectClient) || $instance === null)
        {
            self::$instance = & $instance;
        }
    }

    public static function getUserIdFromResource($resource_id)
    {
        if (preg_match('/^.+\.([0-9a-f]+)\..+$/', $resource_id, $match))
        {
            return $match[1];
        }
        return null;
    }

    /**
     * Get an option from the LiveConnectClient instance.
     *
     * @param   string  $key  The name of the option to get
     *
     * @return  mixed  The option value
     */
    public function getOption($key)
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : null;
    }

    /**
     * Set an option for the LiveConnectClient instance.
     *
     * @param   string  $key    The name of the option to set
     * @param   mixed   $value  The option value to set
     *
     * @return  LiveConnectClient  This object for method chaining
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Get the access token or redict to the authentication URL.
     *
     * @return  WP_Error|string The access token or WP_Error on failure.
     */
    public function authenticate()
    {
        $this->log(__METHOD__);

        if (isset($_GET['code']) AND ( $data['code'] = $_GET['code']))
        {
            $data['grant_type']    = 'authorization_code';
            $data['redirect_uri']  = $this->getOption('redirecturi');
            $data['client_id']     = $this->getOption('clientid');
            $data['client_secret'] = $this->getOption('clientsecret');

            $response = $this->http->post($this->getOption('tokenurl'), array('body' => $data, 'timeout' => $this->getOption('timeout'), 'sslverify' => $this->getOption('sslverify')));

            if (is_wp_error($response))
            {
                return $response;
            }
            elseif ($response['response']['code'] >= 200 AND $response['response']['code'] < 400)
            {
                if (isset($response['headers']['content-type']) AND strpos($response['headers']['content-type'], 'application/json') !== false)
                {
                    $token = array_merge(json_decode($response['body'], true), array('created' => time()));
                }
				else
				{
					parse_str($response['body'], $token);
					$token = array_merge($token, array('created' => time()));
				}

                $this->setToken($token);

                return $token;
            }
            else
            {
                return new WP_Error('oauth_failed', 'Error code ' . $response['response']['code'] . ' received requesting access token: ' . $response['body'] . '.');
            }
        }

        if ($this->getOption('sendheaders'))
        {
            // If the headers have already been sent we need to send the redirect statement via JavaScript.
            if (headers_sent())
            {
                echo "<script>document.location.href='" . str_replace("'", "&apos;", $this->createUrl()) . "';</script>\n";
            }
            else
            {
                // All other cases use the more efficient HTTP header for redirection.
                header('HTTP/1.1 303 See other');
                header('Location: ' . $this->createUrl());
                header('Content-Type: text/html; charset=utf-8');
            }

            die();
        }
        return false;
    }

    /**
     * Verify if the client has been authenticated
     *
     * @return  boolean  Is authenticated
     */
    public function isAuthenticated()
    {
        $this->log(__METHOD__);

        $token = $this->getToken();

        if (!$token || !array_key_exists('access_token', $token))
        {
            return false;
        }
        elseif (array_key_exists('expires_in', $token) && $token['created'] + $token['expires_in'] < time() + 20)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Create the URL for authentication.
     *
     * @return  WP_Error|string The URL or WP_Error on failure.
     */
    public function createUrl()
    {
        $this->log(__METHOD__);

        if (!$this->getOption('authurl') || !$this->getOption('clientid'))
        {
            return new WP_Error('oauth_failed', 'Authorization URL and client_id are required');
        }

        $url = $this->getOption('authurl');

        if (strpos($url, '?'))
        {
            $url .= '&';
        }
        else
        {
            $url .= '?';
        }

        $url .= 'response_type=code';
        $url .= '&client_id=' . urlencode($this->getOption('clientid'));

        if ($this->getOption('redirecturi'))
        {
            $url .= '&redirect_uri=' . urlencode($this->getOption('redirecturi'));
        }

        if ($this->getOption('scope'))
        {
            $scope = is_array($this->getOption('scope')) ? implode(' ', $this->getOption('scope')) : $this->getOption('scope');
            $url .= '&scope=' . urlencode($scope);
        }

        if ($this->getOption('state'))
        {
            $url .= '&state=' . urlencode($this->getOption('state'));
        }

        if (is_array($this->getOption('requestparams')))
        {
            foreach ($this->getOption('requestparams') as $key => $value)
            {
                $url .= '&' . $key . '=' . urlencode($value);
            }
        }

        return $url;
    }

    public function handlePageRequest()
    {
        $this->log(__METHOD__);

        if ($this->isAuthenticated() OR ( isset($_GET['access_token']) AND $_GET['access_token']))
        {
            $this->log(__METHOD__ . '. There is a token available already');
            // There is a token available already. It should be the token flow. Ignore it.
            return;
        }

        $token = $this->authenticate();
        if (is_wp_error($token))
        {
            $this->log(__METHOD__ . '. Authentication error: ' . $token->get_error_message(), E_USER_ERROR);
            $token = false;
        }

        if ($token === false)
        {
            $token = $this->loadToken();
            if (is_array($token) && array_key_exists('expires_in', $token) && $token['created'] + $token['expires_in'] < time() + 20)
            {
                if (!$this->getOption('userefresh'))
                {
                    return false;
                }

                $token = $this->refreshToken($token['refresh_token']);
                if (is_wp_error($token))
                {
                    $this->log(__METHOD__ . '. Refreshing token error: ' . $token->get_error_message(), E_USER_ERROR);
                    return false;
                }
            }
        }

        $error = array(
            'error' => isset($_GET['error']) ? $_GET['error'] : null,
            'error_description' => isset($_GET['error_description']) ? $_GET['error_description'] : null
        );

        if ($error['error'])
        {
            $this->setToken($error);
        }

        $this->log(__METHOD__ . '. End');

        return
                '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' .
                '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:msgr="http://messenger.live.com/2009/ui-tags">' .
                '<head>' .
                '<title>Live SDK Callback Page</title>' .
                '<script src="//js.live.net/v5.0/wl.js" type="text/javascript"></script>' .
                '</head>' .
                '<body></body>' .
                '</html>';
    }

    public function getAccessId()
    {
        return $this->access_id;
    }

    public function getAccessIdByResource($resource_id)
    {
        global $wpdb;

        $this->log(__METHOD__ . '. Resource: ' . $resource_id);

        if (!isset(self::$access[$resource_id]))
        {
            if (!$this->getOption('storetoken'))
                return null;

            $sql_like                   = method_exists($wpdb, 'esc_like') ? $wpdb->esc_like($resource_id) : like_escape($resource_id);
            $sql                        = $wpdb->prepare('SELECT `access_id` FROM `' . $wpdb->prefix . 'onedrive_storage` WHERE `resource_id` LIKE %s', $sql_like);
            self::$access[$resource_id] = (int) $wpdb->get_var($sql);
        }

        return self::$access[$resource_id];
    }

    /**
     * Set an option for the LiveConnectClient instance.
     *
     * @param   array  $value  The access token
     * @param	bool   $proccess	TRUE - save token and set cookie
     *
     * @return  LiveConnectClient  This object for method chaining
     */
    public function setToken($value, $process = true)
    {
        $this->log(__METHOD__ . '. Token: ' . print_r($value, true));

        $error = false;
        if (is_array($value) AND array_key_exists('error', $value))
        {
            $error = $value;
        }
        else
        {
            if (is_array($value) && !array_key_exists('expires_in', $value) && array_key_exists('expires', $value))
            {
                $value['expires_in'] = $value['expires'];
                unset($value['expires']);
            }
            self::$token[$this->access_id] = $value;
        }

        if ($process)
        {
            $token = self::$token[$this->access_id];

            if ($error === false AND is_array($token) AND array_key_exists('refresh_token', $token))
            {
                $this->saveToken($token);
            }

            if ($this->getOption('usecookie'))
            {
                $authCookie   = isset($_COOKIE[$this->getOption('cookiename')]) ? $_COOKIE[$this->getOption('cookiename')] : '';
                $cookieValues = $this->parseQueryString($authCookie);

                if ($error === false AND is_array($token))
                {
                    $cookieValues['access_token']         = $token['access_token'];
                    $cookieValues['authentication_token'] = $token['authentication_token'];
                    $cookieValues['scope']                = $token['scope'];
                    $cookieValues['expires_in']           = $token['expires_in'];
                    $cookieValues['created']              = $token['created'];
                }

                if (!empty($error))
                {
                    $cookieValues['error']             = $error['error'];
                    $cookieValues['error_description'] = $error['error_description'];
                }
                else
                {
                    unset($cookieValues['error'], $cookieValues['error_description']);
                }

                $this->log(__METHOD__ . '. Set cookie: ' . print_r($cookieValues, true) . "\r\n" . 'Cookie domain: ' . $_SERVER['HTTP_HOST']);

                if (!headers_sent())
                {
                    setrawcookie($this->getOption('cookiename'), $this->buildQueryString($cookieValues), 0, '/', $_SERVER['HTTP_HOST']);
                }
            }
        }

        return $this;
    }

    /**
     * Get the access token from the LiveConnectClient instance.
     *
     * @return  array  The access token
     */
    public function getToken()
    {
        $this->log(__METHOD__);

        $token = isset(self::$token[$this->access_id]) ?
                self::$token[$this->access_id] :
                (isset(self::$token[0]) ? self::$token[0] : null);

        if (!$token AND $this->loadToken())
        {
            $token = self::$token[$this->access_id];
        }

        return $token;
    }

    protected function loadToken()
    {
        global $wpdb;

        $this->log(__METHOD__);

        $token = null;

        if ($this->getOption('usecookie'))
        {
            $authCookie   = isset($_COOKIE[$this->getOption('cookiename')]) ? $_COOKIE[$this->getOption('cookiename')] : '';
            $cookieValues = $this->parseQueryString($authCookie);

            $this->log(__METHOD__ . '. Get cookie: ' . print_r($cookieValues, true));

            if (array_key_exists('access_token', $cookieValues))
            {
                $token                         = array();
                $token['access_token']         = $cookieValues['access_token'];
                $token['authentication_token'] = $cookieValues['authentication_token'];
                $token['scope']                = $cookieValues['scope'];
                $token['expires_in']           = $cookieValues['expires_in'];
                $token['created']              = isset($cookieValues['created']) ? $cookieValues['created'] : 0;
            }
        }

        if ($this->getOption('storetoken') AND $this->access_id)
        {
            $this->log(__METHOD__ . '. Loading token from database');

            $sql   = $wpdb->prepare(
                    'SELECT `access_token`, `refresh_token`, `created`, `expires_in` ' .
                    'FROM `' . $wpdb->prefix . 'onedrive_access` WHERE `id` = %d', $this->access_id);
            $token = $wpdb->get_row($sql, ARRAY_A);
        }

        $this->log(__METHOD__ . '. Loaded token: ' . print_r($token, true));

        if ($token)
        {
            $this->setToken($token, false);
            return true;
        }

        return false;
    }

    protected function saveToken($token = array())
    {
        global $wpdb;

        $this->log(__METHOD__);

        if (!$this->getOption('storetoken'))
            return null;

        $user_id = null;

        if (!$this->access_id)
        {
            $response = $this->query('me');
            $this->log(__METHOD__ . '. ' . print_r($response, true));
            if (!is_wp_error($response) AND ! empty($response['body']) AND isset($response['body']->id))
            {
                $user_id = $response['body']->id;
            }
            $this->log(__METHOD__ . '. Get User ID. Result: ' . $user_id);

            if ($user_id)
            {
                // save the refresh token and associate it with the user identified by your site credential system.
                $sql_like        = method_exists($wpdb, 'esc_like') ? $wpdb->esc_like($user_id) : like_escape($user_id);
                $sql             = $wpdb->prepare(
                        'SELECT `id` ' .
                        'FROM `' . $wpdb->prefix . 'onedrive_access` WHERE `user_id` LIKE %s', $sql_like);
                $this->access_id = (int) $wpdb->get_var($sql);

                $this->log(__METHOD__ . '. Get access ID by User ID. Result: ' . $this->access_id);
            }
        }

        $access = array(
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'],
            'created' => $token['created'],
            'expires_in' => $token['expires_in']
        );

        if ($this->access_id)
        {
            $this->log(__METHOD__ . '. Update token by access ID: ' . $this->access_id);

            return $wpdb->update($wpdb->prefix . 'onedrive_access', $access, array('id' => (int) $this->access_id), array('%s', '%s', '%d', '%d'));
        }
        elseif ($user_id)
        {
            $this->log(__METHOD__ . '. Insert new token for User ID: ' . $user_id);

            $access['user_id'] = $user_id;

            $result          = $wpdb->insert($wpdb->prefix . 'onedrive_access', $access, array('%s', '%s', '%d', '%d', '%s'));
            $this->access_id = $wpdb->insert_id;

            return $result;
        }
        else
        {
            $this->log(__METHOD__ . '. Failed getting User ID');
            return false;
        }
    }

    /**
     * Refresh the access token instance.
     *
     * @param   string  $token  The refresh token
     *
     * @return  WP_Error|array  The new access token or WP_Error on failure.
     */
    public function refreshToken($token = null)
    {
        $this->log(__METHOD__);

        if (!$this->getOption('userefresh'))
        {
            return new WP_Error('oauth_failed', 'Refresh token is not supported for this OAuth instance.');
        }

        if (!$token)
        {
            $token = $this->getToken();

            if (!array_key_exists('refresh_token', $token))
            {
                return new WP_Error('oauth_failed', 'No refresh token is available.');
            }
            $token = $token['refresh_token'];
        }
        $data['grant_type']    = 'refresh_token';
        $data['refresh_token'] = $token;
        $data['client_id']     = $this->getOption('clientid');
        $data['client_secret'] = $this->getOption('clientsecret');

        $response = $this->http->post($this->getOption('tokenurl'), array('body' => $data, 'timeout' => $this->getOption('timeout'), 'sslverify' => $this->getOption('sslverify')));

        if (is_wp_error($response))
        {
            $this->log(__METHOD__ . '. Rrequest error: ' . $response->get_error_message(), E_USER_ERROR);
            return $response;
        }
        elseif ($response['response']['code'] >= 200 || $response['response']['code'] < 400)
        {
            if (strpos($response['headers']['content-type'], 'application/json') !== false)
            {
                $token = array_merge(json_decode($response['body'], true), array('created' => time()));
            }
            else
            {
                parse_str($response['body'], $token);
                $token = array_merge($token, array('created' => time()));
            }

            $this->setToken($token);

            return $token;
        }
        else
        {
            return new WP_Error('oauth_failed', 'Error code ' . $response['response']['code'] . ' received refreshing token: ' . $response['body'] . '.');
        }
    }

    /**
     * Send a signed Oauth request.
     *
     * @param   string  $url      The URL forf the request.
     * @param   mixed   $data     The data to include in the request
     * @param   array   $headers  The headers to send with the request
     * @param   string  $method   The method with which to send the request
     * @param   int     $timeout  The timeout for the request
     *
     * @return  WP_Error|array The response or WP_Error on failure.
     */
    public function query($url = null, $data = null, $headers = array(), $method = 'get', $timeout = null)
    {
        $this->log(__METHOD__ . '. URL: ' . $url . ' ' . print_r($data, true));

        $url = strpos($url, 'http') === 0 ? $url : 'https://apis.live.net/v5.0/' . ltrim($url, '/');

        $token = $this->getToken();
        if (is_array($token) && array_key_exists('expires_in', $token) && $token['created'] + $token['expires_in'] < time() + 20)
        {
            if (!$this->getOption('userefresh'))
            {
                return false;
            }
            $token = $this->refreshToken($token['refresh_token']);
        }

        if (!$this->getOption('authmethod') || $this->getOption('authmethod') == 'bearer')
        {
            $headers['Authorization'] = 'Bearer ' . $token['access_token'];
        }
        elseif ($this->getOption('authmethod') == 'get')
        {
            if (strpos($url, '?'))
            {
                $url .= '&';
            }
            else
            {
                $url .= '?';
            }
            $url .= $this->getOption('getparam') ? $this->getOption('getparam') : 'access_token';
            $url .= '=' . $token['access_token'];
        }

        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => $timeout > 0 ? $timeout : $this->getOption('timeout'),
            'sslverify' => $this->getOption('sslverify')
        );

        switch ($method)
        {
            case 'get':
            case 'delete':
            case 'trace':
            case 'head':
                break;
            case 'post':
            case 'put':
            case 'patch':
                $args['body'] = $data;
                break;
            default:
                return new WP_Error('oauth_failed', 'Unknown HTTP request method: ' . $method . '.');
        }

        $response = $this->http->request($url, $args);

        $this->log(__METHOD__ . '. ' . print_r($response, true));

        if (is_wp_error($response))
        {
            $this->log(__METHOD__ . '. Request error: ' . $response->get_error_message(), E_USER_ERROR);
        }
        elseif ($response['response']['code'] < 200 OR $response['response']['code'] >= 400)
        {
            $error = __METHOD__ . '. Response code ' . $response['response']['code'] . ' received requesting data: ' . $response['body'] . '.';
            $this->log($error, E_USER_ERROR);
            return new WP_Error('oauth_failed', $error);
        }
        elseif (isset($response['headers']['content-type']) AND strpos($response['headers']['content-type'], 'application/json') !== false)
        {
            $response['body'] = json_decode($response['body']);
        }

        return $response;
    }

    public function queryByAccessId($access_id = 0, $url = null, $data = null, $headers = array(), $method = 'get')
    {
        $this->log(__METHOD__ . '. ID: ' . $access_id);

        $this->access_id = (int) $access_id;
        if (!$this->access_id)
        {
            return false;
        }

        return $this->query($url, $data, $headers, $method);
    }

    public function queryByRersourceId($resource_id = null, $url = null, $data = null, $headers = array(), $method = 'get')
    {
        $this->log(__METHOD__ . '. Resource: ' . $resource_id);

        $this->access_id = $this->getAccessIdByResource($resource_id);
        if (!$this->access_id)
        {
            $this->log(__METHOD__ . '. Missing access ID for resource: ' . $resource_id);
            return false;
        }

        return $this->query($url ? $url : $resource_id, $data, $headers, $method);
    }

    public function request($url = null, $headers = array())
    {
        $this->log(__METHOD__ . '. URL: ' . $url);

        $response = $this->http->get($url, array('headers' => $headers, 'timeout' => $this->getOption('timeout'), 'sslverify' => $this->getOption('sslverify')));

        if (is_wp_error($response))
        {
            $this->log(__METHOD__ . '. Request error ' . $response->get_error_message(), E_USER_ERROR);
        }
        elseif ($response['response']['code'] < 200 OR $response['response']['code'] >= 400)
        {
            $error = __METHOD__ . '. Response code ' . $response['response']['code'] . ' received requesting data: ' . $response['body'] . '.';
            $this->log($error, E_USER_ERROR);
            return new WP_Error('request_failed', $error);
        }

        return $response;
    }

    protected function buildQueryString($array)
    {
        $result = '';
        foreach ($array as $k => $v)
        {
            if ($result == '')
            {
                $prefix = '';
            }
            else
            {
                $prefix = '&';
            }
            $result .= $prefix . rawurlencode($k) . '=' . rawurlencode($v);
        }

        return $result;
    }

    protected function parseQueryString($query)
    {
        $result = array();
        $arr    = preg_split('/&/', $query);
        foreach ($arr as $arg)
        {
            if (strpos($arg, '=') !== false)
            {
                $kv                           = preg_split('/=/', $arg);
                $result[rawurldecode($kv[0])] = rawurldecode($kv[1]);
            }
        }
        return $result;
    }

    public function log($message = null, $level = E_USER_NOTICE)
    {
        if (!PWEBONEDRIVE_DEBUG)
            return;

        switch ($level)
        {
            case E_USER_ERROR:
                $prefix = '   Error     ';
                break;
            case E_USER_WARNING:
                $prefix = '   Warning   ';
                break;
            case E_USER_NOTICE:
            default:
                $prefix = '   Notice    ';
        }

        error_log("\r\n" . date('Y-m-d H:i:s') . $prefix . $message, 3, WP_CONTENT_DIR . '/debug.log');
    }

}
