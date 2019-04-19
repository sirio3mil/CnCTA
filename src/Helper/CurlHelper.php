<?php


namespace CnCTA\Helper;


use Curl\Curl;
use Exception;

class CurlHelper extends Curl
{
    /**
     * @return mixed
     * @throws Exception
     */
    public function getResponse()
    {
        if (empty($this->response)) {
            throw new Exception('Empty response');
        } elseif ($this->error) {
            throw new Exception($this->errorMessage, $this->errorCode);
        }
        return $this->response;
    }
}