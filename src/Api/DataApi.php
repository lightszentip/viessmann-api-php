<?php

namespace Lightszentip\Viessmannapi\Api;

use Lightszentip\Viessmannapi\Connection\Login;

class DataApi
{

    private Login $login;

    public function __construct(Login $login)
    {
        $this->login = $login;
    }

    private function header()
    {
        return array("Authorization: Bearer ".$this->login->getAccessToken());
    }

    private function execute(string $url): string
    {
        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $this->header(),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        );

        // Data Curl Call
        //
        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);
        $response = curl_exec($curl);
        $apiErr = curl_error($curl);
        curl_close($curl);
        print_r($apiErr);

        return $response;
    }


    public function readUserData(): void
    {
        $url = "https://api.viessmann.com/users/v1/users/me?sections=identity";

        $response = $this->execute($url);

        echo($response);
    }

    public function getDeviceFeatures(): void
    {
        $url='https://api.viessmann.com/iot/v2/features/installations/'.$this->login->getInstallationId().'/gateways/'.$this->login->getGatewayId().'/devices/'.$this->login->getDeviceId().'/features';

        $response = $this->execute($url);

        $jsonResponse = json_decode($response, true);

        print_r($jsonResponse);
    }

    public function getGatewayFeatures(): void
    {
        $url='https://api.viessmann.com/iot/v2/features/installations/'.$this->login->getInstallationId().'/gateways/'.$this->login->getGatewayId().'/features';

        $response = $this->execute($url);

        $jsonResponse = json_decode($response, true);

        print_r($jsonResponse);
    }

    public function getEvents(): void
    {
        $url='https://api.viessmann.com/iot/v1/events-history/events?gatewaySerial='.$this->login->getGatewayId().'&installationId='.$this->login->getInstallationId();

        $response = $this->execute($url);

        $jsonResponse = json_decode($response, true);

        print_r($jsonResponse);
    }


    public function getDevices(): void
    {
        $url='https://api.viessmann.com/iot/v1/equipment/installations/'.$this->login->getInstallationId().'/gateways/'.$this->login->getGatewayId().'/devices';

        $response = $this->execute($url);

        $jsonResponse = json_decode($response, true);

        print_r($jsonResponse);
    }




}