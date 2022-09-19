<?php

namespace Mia\Andes\Service;

use GuzzleHttp\Psr7\Request;

class AndesService
{
    /**
     * URL de la API
     */
    const BASE_URL = 'https://fe.andesscd.com.co/api/';
    const TEST_BASE_URL = 'https://testfe.andesscd.com.co/api/';
    /**
     * 
     * @var string
     */
    protected $username = '';
    /**
     * 
     * @var string
     */
    protected $password = '';
    /**
     * 
     * @var string
     */
    protected $bearerToken = '';
    /**
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;
    /**
     *
     * @var boolean
     */
    protected $testMode = false;
    /**
     * 
     */
    public function __construct($username, $password, $isTest = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->testMode = $isTest;
        $this->guzzle = new \GuzzleHttp\Client(['base_uri' => $this->getBaseUrl()]);
    }

    public function create($vendor, $documentId, $signer, $expire = 30)
    {
        return $this->generateRequest('POST', 'Transaccion/create', [
            'documentoid' => $documentId,
            'Firmantes' => $signer,
            'NombreCreador' => $vendor,
            'DiasVence' => $expire
        ]);
    }

    public function uploadDocument($binaryFile)
    {
        $request = new Request('POST', $this->getBaseUrl() . 'Transaccion/upload/doc', ['Accept' => '*/*','Authorization' => 'Bearer ' . $this->bearerToken]);
        $response = $this->guzzle->send($request, ['multipart' => [['name' => 'file', 'contents' => $binaryFile, 'filename' => 'file.pdf']]]);
        
        if($response->getStatusCode() == 200 && $response->getStatusCode() == 201){
            return json_decode($response->getBody()->getContents());
        }
    }

    public function initAuthentication() 
    {
        $this->bearerToken = $this->getBearerToken();
    }
    
    public function getBearerToken()
    {
        $body = json_encode([
            'username' => $this->username,
            'password' => $this->password,
        ]);

        $request = new Request('POST', $this->getBaseUrl() . 'users/authenticate', ['Accept' => '*/*','Content-Type' => 'application/json'], $body);
        $response = $this->guzzle->send($request);
        
        if($response->getStatusCode() != 200||$response->getStatusCode() != 201){
            return '';
        }

        $data = json_decode($response->getBody()->getContents());
        return $data->token;
    }

    /**
     * Funcion para generar request
     */
    protected function generateRequest($method, $path, $params = null)
    {
        $body = null;
        if($params != null){
            $body = json_encode($params);
        }

        $request = new Request(
            $method, 
            $this->getBaseUrl() . $path, 
            [
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->bearerToken
            ],
            $body
        );

        $response = $this->guzzle->send($request);
        
        if($response->getStatusCode() == 200||$response->getStatusCode() == 201){
            return json_decode($response->getBody()->getContents());
        }
        
        return null;
    }

    public function getBaseUrl()
    {
        if($this->testMode){
            return self::TEST_BASE_URL;
        }
        return self::BASE_URL;
    }
}