<?php

namespace Lightszentip\Viessmannapi\Connection;

use DOMDocument;
use DOMXPath;
use Exception;

class Login
{
    const AUTHORIZE_URL = "https://iam.viessmann.com/idp/v2/authorize";
    const TOKEN_URL = "https://iam.viessmann.com/idp/v2/token";

    private string $codeChallenge;
    private string $filePathCredentials;
    private string $accessToken;
    private string $clientId;
    private string $installationId;
    private string $gatewayId;

    private string $deviceId = "0"; //TODO implement https://api.viessmann.com/iot/v1/equipment/installations/{installationId}/gateways/{gatewaySerial}/devices to get the device ids

    function randomString()
    {
        $randomString = bin2hex(random_bytes(10));
        $this->codeChallenge = hash("SHA256",$randomString);
    }

    /**
     * @throws Exception
     */
    function __construct(string $filePath)
    {
        $this->filePathCredentials = $filePath;
        $this->setupLogin();
    }


    /**
     *  Find the code in the HTML response
     * @param $response
     * @param $params
     * @return array
     */
    private function findCodeInHtml($response): string
    {
        // Create a new DOMDocument instance
        $dom = new DOMDocument();
        // Load the HTML content
        $dom->loadHTML($response);

        // Create a DOMXPath instance
        $xpath = new DOMXPath($dom);

        // Find all <a> elements
        $links = $xpath->query('//a[@href]');

        // Loop through the <a> elements
        foreach ($links as $link) {
            // Get the href attribute value
            $href = $link->getAttribute('href');

            // Parse the URL to get the query string
            $urlParts = parse_url($href);
            $queryString = $urlParts['query'] ?? '';

            // Parse the query string to get the parameter values
            parse_str($queryString, $params);

            // Check if the 'code' parameter exists
            if (isset($params['code'])) {
                return $params['code'];
            }
        }
        return "";
    }

    /**
     * @param bool|array $credentials
     * @return string
     */
    private function getUrl(array $credentials): string
    {
        $this->randomString();
        // Code Request Settings
        $parameters = array(
            'client_id' => $credentials["client_id"],
            'code_challenge' => $this->codeChallenge,
            'scope' => 'IoT%20User',
            'redirect_uri' => $credentials["callback_uri"],
            'response_type' => 'code',
        );
        $generateAuthorizeUrl = self::AUTHORIZE_URL . '?';
        foreach ($parameters as $key => $value) {
            $generateAuthorizeUrl .= $key . '=' . $value . '&';
        }
        return rtrim($generateAuthorizeUrl, '&');
    }

    /**
     * @param bool|array $credentials
     * @param string $code
     * @return string
     */
    private function getGenerateTokenUrl(bool|array $credentials, string $code): string
    {

        $parameters = array(
            'client_id' => $credentials["client_id"],
            'code_verifier' => $this->codeChallenge,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $credentials["callback_uri"],
        );

        $generateTokenUrl = self::TOKEN_URL . '?';
        foreach ($parameters as $key => $value) {
            $generateTokenUrl .= $key . '=' . $value . '&';
        }

        $generateTokenUrl .= "code=$code";
        return $generateTokenUrl;
    }



    /**
     * @param string $generateTokenUrl
     * @param array $header
     * @return mixed
     */
    private function getToken(string $generateTokenUrl, array $header): mixed
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $generateTokenUrl,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_POST => true,
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        // Token extraction
        //
        $json = json_decode($response, true);
        return $json;
    }

    /**
     * @param string $filePath
     * @return void
     * @throws Exception
     */
    private function setupLogin(): void
    {
        $credentials = parse_ini_file(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $this->filePathCredentials . "/credentials.properties");
        $this->clientId = $credentials["client_id"];
        $this->installationId = $credentials["installationId"];
        $this->gatewayId = $credentials["gatewayId"];
        $generateAuthorizeUrl = $this->getUrl($credentials);
        $header = array("Content-Type: application/x-www-form-urlencoded");

        // Code Request
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $generateAuthorizeUrl,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $credentials['user'] . ":" . $credentials['pwd'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_POST => true,
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $code = $this->findCodeInHtml($response);
        if ($code == "") throw new Exception("Code not found");

        $generateTokenUrl = $this->getGenerateTokenUrl($credentials, $code);

        $json = $this->getToken($generateTokenUrl, $header);

        if (array_key_exists('error', $json)) throw new Exception("Error found in token" . $json['error']);
        $this->accessToken = $json['access_token'];
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getInstallationId(): string
    {
        return $this->installationId;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getGatewayId(): string
    {
        return $this->gatewayId;
    }

    /**
     * @return string
     */
    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

}