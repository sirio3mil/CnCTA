<?php

namespace CnCTA\Helper;

use limitium\TAPD\CCAuth\CCAuth;
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
        $auth = new CCAuth('sirio3mil@gmail.com', 'Imagin@Rey0717', true);
        $auth->setBasePath(implode(DIRECTORY_SEPARATOR, [
            dirname(dirname(__DIR__)),
            'data',
            'cookies'
        ]));
        $session = $auth->getSession();
        var_dump($session);
        /*
        $sessionHelper = new SessionHelper('sirio3mil@gmail.com');
        $sessionHelper->login('Imagin@Rey0717');
        $sessionHelper->setSessionId();
        $this->assertNotEmpty($sessionHelper->getSessionId());
        $this->assertNotEmpty($sessionHelper->getReferrer());
        $this->assertNotEmpty($sessionHelper->getSessionUrl());
        */
    }

    public function testSetSessionKey()
    {
        $sessionHelper = new SessionHelper();
        $sessionHelper->login('sirio3mil@gmail.com', 'Imagin@Rey0717');
        $sessionHelper->setSessionKey();
        $this->assertNotEmpty($sessionHelper->getSessionKey());
    }
}
