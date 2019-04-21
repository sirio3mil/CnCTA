<?php

namespace CnCTA\Helper;

use ErrorException;
use Exception;

/**
 * GNU Public License 3.0
 * Copyright (C) 2014 Gary Coleman <cybershark@gmail.com>
 *
 * PHP Curl Class for getting game data from Command & Conquer Tiberium Alliances
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * */
class SessionHelper
{

    /** @var SessionHelper */
    protected static $instance;

    /** @var string */
    protected $username;

    /** @var string */
    protected $agent;

    /** @var string */
    protected $cookie;

    /** @var string */
    protected $sessionId;

    /** @var string */
    protected $referrer;

    /** @var string */
    protected $sessionKey;

    /** @var string */
    protected $sessionUrl;

    /** @var boolean */
    protected $verbose;

    const SESSION_MIDDLE_URL = '/Presentation/Service.svc/ajaxEndpoint/';

    public function __construct(string $username)
    {
        $this->username = $username;
        $this->verbose = false;
        $this->agent = implode(' ', [
            'Mozilla/5.0 (Windows NT 6.3; WOW64)',
            'AppleWebKit/537.36 (KHTML, like Gecko)',
            'Chrome/35.0.1916.114 Safari/537.36'
        ]);
        $this->cookie = implode(DIRECTORY_SEPARATOR, [
            dirname(dirname(__DIR__)),
            'data',
            'cookies',
            md5($this->username) . '.txt'
        ]);
    }

    /**
     * @param bool $verbose
     * @return SessionHelper
     */
    public function setVerbose(bool $verbose): SessionHelper
    {
        $this->verbose = $verbose;
        return $this;
    }

    /**
     * @param string $cookie
     * @return SessionHelper
     */
    public function setCookie(string $cookie): SessionHelper
    {
        $this->cookie = $cookie;
        return $this;
    }

    /**
     * @return string
     */
    public function getCookie(): string
    {
        return $this->cookie;
    }

    /**
     * @return CurlHelper
     * @throws ErrorException
     */
    protected function getCurlInstance()
    {
        $curl = new CurlHelper();
        $curl->setVerbose($this->verbose);
        $curl->setUserAgent($this->agent);
        $curl->setCookieFile($this->cookie);
        $curl->setCookieJar($this->cookie);
        $curl->setOpts([
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true
        ]);
        return $curl;
    }

    /**
     * @return SessionHelper
     */
    public static function getInstance(): SessionHelper
    {
        if (!isset(self::$instance)) {
            self::$instance = new SessionHelper ();
        }
        return self::$instance;
    }

    /**
     * @param string $password
     * @return mixed
     * @throws ErrorException
     */
    public function login(string $password)
    {
        $curl = $this->getCurlInstance();
        $curl->setOpts([
            CURLOPT_FOLLOWLOCATION => true
        ]);
        $curl->post('https://www.tiberiumalliances.com/login/j_security_check', [
            'spring-security-redirect' => '',
            'id' => '',
            'timezone' => '2',
            'j_username' => $this->username,
            'j_password' => $password,
            '_web_remember_me' => ''
        ]);
        return $curl->getResponse();
    }

    /**
     * @throws ErrorException
     * @throws Exception
     */
    public function setSessionId(): void
    {
        $curl = $this->getCurlInstance();
        $curl->setReferer('https://www.tiberiumalliances.com/login/auth');
        $curl->setOpts([
            CURLOPT_FOLLOWLOCATION => true
        ]);
        $curl->get('https://www.tiberiumalliances.com/game/launch');
        $response = $curl->getResponse();
        if (preg_match('/sessionId\" value=\"([^"]+)"/', $response, $match)) {
            $this->sessionId = $match[1];
        } else {
            throw new Exception('Did not find sessionId');
        }
        // grab last used server
        if (preg_match('/([^"]+)\/index\.aspx/', $response, $match)) {
            $this->referrer = $match[0];
            $this->sessionUrl = $match[1] . SessionHelper::SESSION_MIDDLE_URL;
        }
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @return string
     */
    public function getReferrer(): string
    {
        return $this->referrer;
    }

    /**
     * @return string
     */
    public function getSessionUrl(): string
    {
        return $this->sessionUrl;
    }

    /**
     * @throws Exception
     */
    public function setSessionKey(): void
    {
        $data = [
            'session' => $this->sessionId,
            'reset' => true,
            'refId' => -1,
            'version' => -1,
            'platformId' => 1
        ];
        $invalid = "00000000-0000-0000-0000-000000000000";
        $result = $this->getData('OpenSession', $data);
        $tries = 0;
        $maxTries = 2;
        while (($result->i == $invalid) && ($tries < $maxTries)) {
            sleep(2); // Lets not flood the server
            $result = $this->getData('OpenSession', $data);
            $this->sessionKey = $result->i;
            $tries++;
        }
        if ($result->i === $invalid) {
            throw new Exception('invalid Session ID:' . $result->i);
        }
        $this->sessionKey = $result->i;
    }

    /**
     * @return string
     */
    public function getSessionKey(): string
    {
        return $this->sessionKey;
    }

    /**
     * @param string $endpoint
     * @param array $data
     * @return mixed
     * @throws ErrorException
     * @throws Exception
     */
    public function getResponse(string $endpoint, array $data = [])
    {
        $data = array_merge(['session' => $this->sessionKey], $data);
        return $this->getData($endpoint, $data);
    }

    /**
     * @param string $endpoint
     * @param array $data
     * @return mixed
     * @throws ErrorException
     * @throws Exception
     */
    protected function getData(string $endpoint, array $data)
    {
        $curl = $this->getCurlInstance();
        $curl->setReferer($this->referrer);
        $curl->setHeaders([
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'X-Qooxdoo-Response-Type' => 'application/json'
        ]);
        $curl->setConnectTimeout(2);
        $curl->setOpts([
            CURLOPT_VERBOSE => true
        ]);
        $curl->post($this->sessionUrl . $endpoint, $data);
        return json_decode($curl->getResponse());
    }
}
