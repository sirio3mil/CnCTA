<?php

namespace CnCTA\Helper;

use PHPUnit\Framework\TestCase;

class SessionHelperTest extends TestCase
{

    public function testGetResponse()
    {

    }

    public function testLogin()
    {
        $sessionHelper = new SessionHelper();
        $sessionHelper->login('sirio3mil@gmail.com', 'Imagin@Rey0717');
        $this->assertFileExists($sessionHelper->getCookie());
    }

    public function testSetSessionId()
    {
        $sessionHelper = new SessionHelper('sirio3mil@gmail.com');
        $sessionHelper->setVerbose(true);
        $sessionHelper->login('Imagin@Rey0717');
        $sessionHelper->setSessionId();
        $this->assertNotEmpty($sessionHelper->getSessionId());
        $this->assertNotEmpty($sessionHelper->getReferrer());
        $this->assertNotEmpty($sessionHelper->getSessionUrl());
    }

    public function testSetSessionKey()
    {
        $sessionHelper = new SessionHelper();
        $sessionHelper->login('sirio3mil@gmail.com', 'Imagin@Rey0717');
        $sessionHelper->setSessionKey();
        $this->assertNotEmpty($sessionHelper->getSessionKey());
    }
}
