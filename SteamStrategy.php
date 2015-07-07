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
    public $expects = array('key', 'domain', 'callback_url');
    
    /**
     * @var LightOpenID
     */
    private $openid;

    public function __construct($strategy, $env)
    {
        parent::__construct($strategy, $env);
        $this->strategy['strategy_callback_url'] = $this->strategy['callback_url'];
        
        $this->openid = new LightOpenID($this->strategy['domain']);
    }
    
    /**
     * Ask for OpenID identifer
     */
    public function request()
    {
        if (!$this->openid->mode){
            $this->openid->identity = 'http://steamcommunity.com/openid';
            header('Location: ' . $this->openid->authUrl());
            exit();
        } else if ($this->openid->mode == 'cancel') {
            $this->errorCallback(array(
                'provider' => 'Steam',
                'code' => 'cancel_authentication',
                'message' => 'User has canceled authentication'
            ));
        } else if (!$this->openid->validate()) {
            $this->errorCallback(array(
                'provider' => 'Steam',
                'code' => 'not_logged_in',
                'message' => 'User has not logged in'
            ));
        } else {
            $steamId = '';
            if (preg_match('/http:\/\/steamcommunity.com\/openid\/id\/(\d+)/', $this->openid->data['openid_identity'], $matches)) {
                $steamId = $matches[1];
            }

            $userInfo = $this->userInfo($steamId);
            
            $this->auth = array(
                'provider' => 'Steam',
                'uid' => $steamId,
                'info' => $userInfo,
                'credentials' => $this->openid->getAttributes(),
                'raw' => $userInfo
            );

            $this->callback();
        }
    }

    private function userInfo($steamId)
    {
        if (empty($steamId)) {
            $this->errorCallback(array(
                'provider' => 'Steam',
                'code' => 'steam_id_missing',
                'message' => 'No steam ID supplied'
            ));
        }

        //We mute alerts from the following line because we do not want to give away our API key in case file_get_contents() throws a warning.
        @$data = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=".$this->strategy['key']."&steamids=".$steamId);

        if (false === $data) {
            $this->errorCallback(array(
                'provider' => 'Steam',
                'code' => 'user_callback',
                'message' => 'User information query failed'
            ));
        }

        $data = json_decode($data, true)['response']['players'][0];

        return array(
            'name' => $data['realname'],
            'nickname' => $data['personaname'],
            'image' => $data['avatarfull']
            // Nothing else in $data is especially useful outside of the context of steam
        );
    }
}