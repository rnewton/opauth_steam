<?php
/**
 * Steam OpenID strategy for Opauth
 * 
 * Implemented with Mewp's LightOpenID Library,
 *   included at Vendor/lightopenid
 *   (https://gitorious.org/lightopenid/lightopenid)
 * 
 * More information on Opauth: http://opauth.org
 * 
 * @copyright    Copyright © 2015 Robert Newton
 * @link         http://opauth.org
 * @package      Opauth.SteamStrategy
 * @license      MIT License
 */

/**
 * Steam OpenID strategy for Opauth
 * 
 * @package         Opauth.OpenIDStrategy
 */
class SteamStrategy extends OpauthStrategy{
    
    /**
     * Compulsory config keys, listed as unassociative arrays
     */
    public $expects = array('key', 'domain');
    
    /**
     * Optional config keys, without predefining any default values.
     */
    public $optionals = array();
    
    /**
     * Optional config keys with respective default values, listed as associative arrays
     * eg. array('scope' => 'email');
     */
    public $defaults = array(
        'identity' => 'http://steamcommunity.com/openid'
    );
    
    /**
     * @var LightOpenID
     */
    private $openid;

    public function __construct($strategy, $env)
    {
        parent::__construct($strategy, $env);
        
        $this->openid = new LightOpenID($this->strategy['domain']);
    }
    
    /**
     * Ask for OpenID identifer
     */
    public function request()
    {
        if (!$this->openid->mode){
            if (empty($_POST['openid_url'])){
                $this->render($this->strategy['identifier_form']);
            }
            else{
                $this->openid->identity = $_POST['openid_url'];
                try{
                    $this->redirect($this->openid->authUrl());
                } catch (Exception $e){
                    $error = array(
                        'provider' => 'Steam',
                        'code' => 'bad_identifier',
                        'message' => $e->getMessage()
                    );

                    $this->errorCallback($error);
                }
            }
        }
        elseif ($this->openid->mode == 'cancel'){
            $error = array(
                'provider' => 'Steam',
                'code' => 'cancel_authentication',
                'message' => 'User has canceled authentication'
            );

            $this->errorCallback($error);
        }
        elseif (!$this->openid->validate()){
            $error = array(
                'provider' => 'Steam',
                'code' => 'not_logged_in',
                'message' => 'User has not logged in'
            );

            $this->errorCallback($error);
        }
        else{
            $attributes = $this->openid->getAttributes();

            ddd($attributes);
            $this->auth = array(
                'provider' => 'Steam',
                'uid' => $this->openid->identity,
                'info' => array(),
                'credentials' => array(),
                'raw' => $this->openid->getAttributes()
            );
            
            if (!empty($attributes['contact/email'])) $this->auth['info']['email'] = $attributes['contact/email'];
            if (!empty($attributes['namePerson'])) $this->auth['info']['name'] = $attributes['namePerson'];
            if (!empty($attributes['fullname'])) $this->auth['info']['name'] = $attributes['fullname'];
            if (!empty($attributes['namePerson/first'])) $this->auth['info']['first_name'] = $attributes['namePerson/first'];
            if (!empty($attributes['namePerson/last'])) $this->auth['info']['last_name'] = $attributes['namePerson/last'];
            if (!empty($attributes['namePerson/friendly'])) $this->auth['info']['nickname'] = $attributes['namePerson/friendly'];
            if (!empty($attributes['contact/phone'])) $this->auth['info']['phone'] = $attributes['contact/phone'];
            if (!empty($attributes['contact/web'])) $this->auth['info']['urls']['website'] = $attributes['contact/web'];
            if (!empty($attributes['media/image'])) $this->auth['info']['image'] = $attributes['media/image'];
            
            $this->callback();
        }
    }
}