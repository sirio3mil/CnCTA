<?php

namespace CnCTA\Controllers;

use Curl\Curl;
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
class CnCTAController
{

    /** @var CnCTAController */
    protected static $instance;

    /** @var string */
    protected $user;

    /** @var string */
    protected $password;

    /** @var string */
    protected $agent;

    /** @var string */
    protected $cookie;

    /** @var string */
    protected $sessionId;

    /** @var string */
    protected $sessionServer;

    /** @var string */
    protected $referrer;

    /** @var string */
    protected $sessionKey;

    /** @var string */
    protected $sessionUrl;

    const SESSION_MIDDLE_URL = '/Presentation/Service.svc/ajaxEndpoint/';

    public function __construct()
    {
        // change to suitable location, tmp path assumes apache server
        $this->cookie = apache_getenv("TMP") . '\\' . md5($this->user) . '.txt';
        $this->agent = 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.114 Safari/537.36';
    }

    /**
     * @return Curl
     * @throws ErrorException
     */
    protected function getCurlInstance()
    {
        $curl = new Curl();
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
     * @return CnCTAController
     */
    public static function getInstance(): CnCTAController
    {
        if (!isset(self::$instance)) {
            self::$instance = new CnCTAController ();
        }
        return self::$instance;
    }

    /**
     * @param string $user
     * @param string $password
     * @return mixed
     * @throws ErrorException
     */
    public function login(string $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
        $loginFields = [
            'spring-security-redirect' => '',
            'id' => '',
            'timezone' => '2',
            'j_username' => $this->user,
            'j_password' => $this->password,
            '_web_remember_me' => ''
        ];
        $curl = $this->getCurlInstance();
        $curl->setOpts([
            CURLOPT_FOLLOWLOCATION => true
        ]);
        return $curl->post('https://www.tiberiumalliances.com/j_security_check', $loginFields);
    }

    /**
     * @throws ErrorException
     * @throws Exception
     */
    public function LastWorld(): void
    {
        $curl = $this->getCurlInstance();
        $curl->setReferer('https://www.tiberiumalliances.com/login/auth');
        $curl->get('https://www.tiberiumalliances.com/game/launch');
        if (empty($curl->response)) {
            throw new Exception('LastWorld Curl result was empty');
        } elseif ($curl->error) {
            throw new Exception($curl->errorMessage, $curl->errorCode);
        }
        if (preg_match('/sessionId\" value=\"([^"]+)"/', $curl->response, $match)) {
            $this->sessionId = $match[1];
        } else {
            throw new Exception('Did not find sessionId');
        }
        // grab last used server
        if (preg_match('/([^"]+)\/index\.aspx/', $curl->response, $match)) {
            $this->sessionServer = $match[1];
            $this->referrer = $match[0];
            $this->sessionUrl = $this->sessionServer . CnCTAController::SESSION_MIDDLE_URL;
        }
    }

    /**
     * @throws Exception
     */
    public function OpenSession(): void
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
        if (empty($curl->response)) {
            throw new Exception('getData Curl result was empty');
        } elseif ($curl->error) {
            throw new Exception($curl->errorMessage, $curl->errorCode);
        }
        return json_decode($curl->response);
    }
}
