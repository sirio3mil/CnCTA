<?php

namespace CnCTA\Helper;

use PHPUnit\Framework\TestCase;

class SessionHelperTest extends TestCase
{

    public function testGetResponse()
    {

    }

    public function testResetSessionCookies()
    {
        $sessionHelper = new SessionHelper('sirio3mil@gmail.com');
        $sessionHelper->resetSessionCookies();
        $this->assertFileNotExists($sessionHelper->getCookie());
    }

    public function testSetSessionCookies()
    {
        $sessionHelper = new SessionHelper('sirio3mil@gmail.com');
        $sessionHelper->setSessionCookies();
        $this->assertFileExists($sessionHelper->getCookie());
        $this->assertRegexp('/JSESSIONID/', file_get_contents($sessionHelper->getCookie()));
    }

    public function testRegister()
    {
        $sessionHelper = new SessionHelper('sirio3mil@gmail.com');
        $sessionHelper->setSessionCookies();
        $sessionHelper->register();
        $this->assertNotEmpty($sessionHelper->getState());
        $this->assertNotEmpty($sessionHelper->getExecution());
        $this->assertNotEmpty($sessionHelper->getLoginUrl());
    }

    public function testLogin()
    {
        $sessionHelper = new SessionHelper('sirio3mil@gmail.com');
        $sessionHelper->setSessionCookies();
        $sessionHelper->register();
        $sessionHelper->login('#LeNtilla1');
    }
}
