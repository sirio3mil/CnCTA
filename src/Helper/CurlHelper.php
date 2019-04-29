<?php


namespace CnCTA\Helper;


use Curl\CaseInsensitiveArray;
use Curl\Curl;
use Exception;

class CurlHelper extends Curl
{
    /** @var boolean */
    protected $verbose;

    /** @var string */
    protected $path;

    /**
     * @param bool $verbose
     * @return CurlHelper
     */
    public function setVerbose(bool $verbose): CurlHelper
    {
        $this->verbose = $verbose;
        $this->verbose($verbose);
        return $this;
    }

    public function setCookieFile($cookie_file)
    {
        $this->path = dirname($cookie_file);
        return parent::setCookieFile($cookie_file);
    }

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
        /** @var CaseInsensitiveArray $headers */
        $headers = $this->getResponseHeaders();
        if($headers->offsetExists('content-encoding')){
            $encoding = $headers->offsetGet('content-encoding');
            if ($encoding == 'gzip'){
                $this->response = gzdecode($this->response);
            }
        }
        $this->saveResponse();
        return $this->response;
    }

    protected function saveResponse(): void
    {
        if ($this->verbose) {
            $trace = debug_backtrace();
            $caller = $trace[2];
            /** @var CaseInsensitiveArray $headers */
            $headers = $this->getResponseHeaders();
            $fileContent = '';
            foreach ($headers as $key => $value) {
                $fileContent .= "{$key}: {$value}" . PHP_EOL;
            }
            $fileContent .= $this->response;
            file_put_contents($this->path . DIRECTORY_SEPARATOR . $caller['function'] . '.txt', $fileContent);
            print_r($caller['function'] . PHP_EOL);
        }
    }
}