<?php

namespace App\Gateway;

use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TwitchGateway
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $token = null;

    /**
     * @var Carbon
     */
    private $token_expires = null;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return bool
     */
    public function isTokenValid()
    {
        if(!empty($this->token) && !empty($this->token_expires))
        {
            if(!$this->token_expires->isPast())
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getOAuthToken()
    {
        if($this->isTokenValid())
        {
            return $this->token;
        }
        $url = "https://id.twitch.tv/oauth2/token";
        try {
            $request = $this->client->post($url,[
                'headers' => [
                    'Content-Type' => "application/x-www-form-urlencoded"
                ],
                'form_params' => [
                    'client_id' => Config::get('twitch.client_id'),
                    'client_secret' => Config::get('twitch.client_secret'),
                    'grant_type' => 'client_credentials'
                ]
            ]);
        } catch (\GuzzleHttp\Exception\GuzzleException $exception)
        {
            Log::error('[TwitchGateway][getOAuthToken][GuzzleException] ' . $exception->getMessage(),[
                'exception' => $exception
            ]);

            throw  new \Exception($exception->getMessage());
        } catch (\Throwable $exception)
        {
            Log::error('[TwitchGateway][getOAuthToken] ' . $exception->getMessage(),[
                'exception' => $exception
            ]);
            throw  new \Exception($exception->getMessage());
        }


        $contents = json_decode($request->getBody()->getContents(),true);

        if(json_last_error()){
            throw new \Exception('[TwitchGateway][getOAuthToken] Invalid Json Code['.json_last_error().']');
        }

        $this->token = $contents['access_token'];
        $this->expires_in = Carbon::now()->addSeconds($contents['expires_in']);

        return $contents['access_token'];
    }

    /**
     * @param $username
     * @return array
     * @throws \Exception
     */
    public function getUserData($username)
    {
        $url = "https://api.twitch.tv/helix/users?login=" . strtolower($username);
        $token = $this->getOAuthToken();

        try {
            $request = $this->client->get($url,[
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Client-Id' => Config::get('twitch.client_id')
                ],
            ]);
        }catch (\GuzzleHttp\Exception\GuzzleException $exception)
        {
            Log::error('[TwitchGateway][getUserData][GuzzleException] ' . $exception->getMessage(),[
                'exception' => $exception
            ]);

            throw  new \Exception($exception->getMessage());
        } catch (\Throwable $exception)
        {
            Log::error('[TwitchGateway][getUserData] ' . $exception->getMessage(),[
                'exception' => $exception
            ]);
            throw  new \Exception($exception->getMessage());
        }

        $contents = json_decode($request->getBody()->getContents(),true);

        if(json_last_error()){
            throw new \Exception('[TwitchGateway][getUserData] Invalid Json Code['.json_last_error().']');
        }

        return $contents;

    }

}
