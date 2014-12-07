<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

error_reporting(0);

class vkontakte_oauth
{
    const SCHEME = 'https';
    const HOST = 'api.vk.com';
    const AUTHORIZE_URI = '/oauth/authorize';
    const REQUEST_URI   = '/oauth/request_token';
    const ACCESS_URI    = '/oauth/access_token';
    const USERINFO_URI    = '/method/getProfiles';
    
    //Array that should contain the consumer secret and
    //key which should be passed into the constructor.
    private $_consumer = false;
    
    /**
     * Pass in a parameters array which should look as follows:
     * array('key'=>'example.com', 'secret'=>'mysecret');
     * Note that the secret should either be a hash string for
     * HMAC signatures or a file path string for RSA signatures.
     *
     * @param array $params
     */
    public function vkontakte_oauth($params)
    {
        $this->CI = get_instance();
        $this->CI->load->helper('oauth');
        
        if(!array_key_exists('method', $params))$params['method'] = 'GET';
        if(!array_key_exists('algorithm', $params))$params['algorithm'] = OAUTH_ALGORITHMS::HMAC_SHA1;
        
        $this->_consumer = $params;
    }
    
    /**
     * This is called to begin the oauth token exchange. This should only
     * need to be called once for a user, provided they allow oauth access.
     * It will return a URL that your site should redirect to, allowing the
     * user to login and accept your application.
     *
     * @param string $callback the page on your site you wish to return to
     *                         after the user grants your application access.
     * @return mixed either the URL to redirect to, or if they specified HMAC
     *         signing an array with the token_secret and the redirect url
     */
    public function get_request_token($callback, $will_post=true, $session_id=false)
    {

        $redirect = self::SCHEME.'://'.self::HOST.self::AUTHORIZE_URI."?response_type=code&scope=wall&client_id=".$this->_consumer['key']."&redirect_uri=".urlencode($callback);
        //$response = $this->_connect($redirect, '');
        //var_dump($response);
        header("Location: $redirect");
        exit();
        //If they are using HMAC then we need to return the token secret for them to store.
        /*if($this->_consumer['algorithm'] == OAUTH_ALGORITHMS::RSA_SHA1)return $redirect;
        else return array('token_secret'=>$resarray['oauth_token_secret'], 'redirect'=>$redirect);*/
    }
    
    /**
     * This is called to finish the oauth token exchange. This too should
     * only need to be called once for a user. The token returned should
     * be stored in your database for that particular user.
     *
     * @param string $token this is the oauth_token returned with your callback url
     * @param string $secret this is the token secret supplied from the request (Only required if using HMAC)
     * @param string $verifier this is the oauth_verifier returned with your callback url
     * @return array access token and token secret
     */
    public function get_access_token($callback = false, $secret = false)
    {
  
        if($secret !== false)$tokenddata['oauth_token_secret'] = urlencode($secret);

        $baseurl = self::SCHEME.'://'.self::HOST.self::ACCESS_URI."?client_id=".$this->_consumer['key']."&client_secret=".$this->_consumer['secret']."&code=$secret&redirect_uri=".urlencode($callback);

        $response = $this->_connect($baseurl, '');

        if (function_exists('json_decode'))
        {
            $obj = json_decode($response);
        }
        else
        {
            require_once(PATH_THIRD.'social_login_pro/libraries/inc/JSON.php');
            $json = new Services_JSON();
            $obj = $json->decode($response);
        } 
            
        if (strpos($response, 'error')!==false)
        {
            $oauth['oauth_problem'] = (isset($obj->error_description))?$obj->error_description:$obj->error;
        } 
        else
        {                            
            $oauth = get_object_vars($obj);
        }   
        
        //Return the token and secret for storage
        return $oauth;
    }
    
    /**
     * Connects to the server and sends the request,
     * then returns the response from the server.
     * @param <type> $url
     * @param <type> $auth
     * @return <type>
     */
    private function _connect($url, $auth)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        //curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($auth));

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    function get_user_data($response = array())
    {
        $access_token = $response['access_token'];

        $baseurl = self::SCHEME.'://'.self::HOST.self::USERINFO_URI."?uid=".$response['user_id']."&access_token=".$access_token."&fields=uid,first_name,last_name,screen_name,city,country,photo_medium";

        $response = $this->_connect($baseurl, array());
       
        if (function_exists('json_decode'))
        {
            $rawdata = json_decode($response);
        }
        else
        {
            require_once(PATH_THIRD.'social_login_pro/libraries/inc/JSON.php');
            $json = new Services_JSON();
            $rawdata = $json->decode($response);
        }    
        
        $location = '';
        
        if (isset($rawdata->response[0]->city) && !empty($rawdata->response[0]->city))
        {
            $baseurl = self::SCHEME.'://'.self::HOST."/method/places.getCityById?cids=".$rawdata->response[0]->city."&access_token=".$access_token;
            $response = $this->_connect($baseurl, array());
            if (function_exists('json_decode'))
            {
                $city = json_decode($response);
            }
            else
            {
                require_once(PATH_THIRD.'social_login_pro/libraries/inc/JSON.php');
                $json = new Services_JSON();
                $city = $json->decode($response);
            }  
            $location .= $city->response[0]->name.", ";
        }  
        
        if (isset($rawdata->response[0]->country) && !empty($rawdata->response[0]->country))
        {
            $baseurl = self::SCHEME.'://'.self::HOST."/method/places.getCountryById?cids=".$rawdata->response[0]->country."&access_token=".$access_token;
            $response = $this->_connect($baseurl, array());
            if (function_exists('json_decode'))
            {
                $country = json_decode($response);
            }
            else
            {
                require_once(PATH_THIRD.'social_login_pro/libraries/inc/JSON.php');
                $json = new Services_JSON();
                $country = $json->decode($response);
            }  
            $location .= $country->response[0]->name;
        }  
        
        $location = trim($location, ", ");                   

        $data = array();
        $data['username'] = $rawdata->response[0]->screen_name;
        $data['screen_name'] = $rawdata->response[0]->first_name." ".$rawdata->response[0]->last_name;
        $data['bio'] = "";
        $data['occupation'] = '';
        $data['email'] = "";
        $data['location'] = $location;
        $data['url'] = "http://vk.com/".$rawdata->response[0]->screen_name;
        $data['custom_field'] = $rawdata->response[0]->uid;
        $data['alt_custom_field'] = $rawdata->response[0]->uid;
        $data['avatar'] = $rawdata->response[0]->photo_medium;
        $data['photo'] = '';
        $data['status_message'] = '';
        $data['timezone'] = '';
        
        $data['full_name'] = $rawdata->response[0]->first_name." ".$rawdata->response[0]->last_name;
        $data['first_name'] = $rawdata->response[0]->first_name;
        $data['last_name'] = $rawdata->response[0]->last_name;
        $data['gender'] = '';

        return $data;
    }
    
    
    
    function start_following($username='', $response = array())
    {
        return false;
    }    



    function post($message, $url, $oauth_token='', $oauth_token_secret='', $userid='me', $usertoken='')
    {
        $baseurl = self::SCHEME.'://'.self::HOST."/method/wall.post?access_token=".$oauth_token."&message=".urlencode($message);         
                
        $ch = curl_init($baseurl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        //curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array());
        
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);

        $response = curl_exec($ch);

        curl_close($ch);

        return true;

    }
    
    
    
}
// ./system/application/libraries
?>