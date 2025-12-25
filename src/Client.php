<?php

namespace HenryEjemuta\Clubkash;

use GuzzleHttp\Client as GuzzleClient;

class Client
{
    private const BASE_URL = 'https://www.nellobytesystems.com/';
    
    /**
     * @var string
     */
    private $userID;
    private $apiKey;
    private $httpClient;

    /**
     * @var string|null
     */
    private $token;

    /**
     * Client constructor.
     *
     * @param string $userID
     * @param string $apiKey
     * @param array $config
     */
    public function __construct(string $userID, string $apiKey, array $config = [])
    {
        $this->userID = $userID;
        $this->apiKey = $apiKey;
        $this->token = $config['token'] ?? null; // Allow passing token in config

        $baseUrl = $config['base_url'] ?? self::BASE_URL;
        // Ensure base URL ends with a slash
        if (substr($baseUrl, -1) !== '/') {
            $baseUrl .= '/';
        }

        $timeout = $config['timeout'] ?? 30;
        
        $guzzleConfig = [
            'base_uri' => $baseUrl,
            'timeout' => $timeout,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
        
        if (isset($config['handler'])) {
            $guzzleConfig['handler'] = $config['handler'];
        }

        $this->httpClient = new GuzzleClient($guzzleConfig);
    }

    /**
     * Authenticate and generate a new token.
     * Note: Token expires after 7 days.
     *
     * @return array
     * @throws ClubkashException
     */
    public function authenticate(): array
    {
        // Placeholder for authentication endpoint
        $response = $this->request('GET', 'APIToken.asp', [
             'username' => $this->userID,
             'apikey' => $this->apiKey
        ]);

        if (isset($response['token'])) {
            $this->token = $response['token'];
        }

        return $response;
    }

    /**
     * Make a request to the API.
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @return array
     * @throws ClubkashException
     */
    private function request(string $method, string $endpoint, array $params = []): array
    {
        $options = [];

        // Use token if available, otherwise fallback to UserID/APIKey
        if ($this->token) {
             $options['headers']['Authorization'] = 'Bearer ' . $this->token;
        } else {
             $params['UserID'] = $this->userID;
             $params['APIKey'] = $this->apiKey;
        }

        if (strtoupper($method) === 'GET') {
            $options['query'] = $params;
        } else {
            $options['query'] = $params;
        }

        try {
            $response = $this->httpClient->request($method, $endpoint, $options);
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ClubkashException('Failed to decode JSON response: ' . $content);
            }

            return $data;

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new ClubkashException('API Request Failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get wallet balance.
     *
     * @return array
     * @throws ClubkashException
     */
    public function getWalletBalance(): array
    {
        return $this->request('GET', 'APIWalletBalanceV1.asp');
    }

    /**
     * Purchase Airtime.
     *
     * @param string $mobileNetwork Network Code (01=MTN, 02=GLO, 03=9Mobile, 04=Airtel)
     * @param float $amount Amount to top up
     * @param string $mobileNumber Phone number
     * @param string|null $requestID Unique Request ID (optional, generated if null)
     * @return array
     * @throws ClubkashException
     */
    public function purchaseAirtime(string $mobileNetwork, float $amount, string $mobileNumber, string $requestID = null): array
    {
        return $this->request('GET', 'APIAirtimeV1.asp', [
            'MobileNetwork' => $mobileNetwork,
            'Amount' => $amount,
            'MobileNumber' => $mobileNumber,
            'RequestID' => $requestID ?? $this->generateRequestID(),
        ]);
    }

    /**
     * Purchase Data Bundle.
     *
     * @param string $mobileNetwork Network Code (01=MTN, 02=GLO, 03=9Mobile, 04=Airtel)
     * @param string $dataPlan Data Plan Code
     * @param string $mobileNumber Phone number
     * @param string|null $requestID Unique Request ID (optional, generated if null)
     * @return array
     * @throws ClubkashException
     */
    public function purchaseData(string $mobileNetwork, string $dataPlan, string $mobileNumber, string $requestID = null): array
    {
        return $this->request('GET', 'APIDatabundleV1.asp', [
            'MobileNetwork' => $mobileNetwork,
            'DataPlan' => $dataPlan,
            'MobileNumber' => $mobileNumber,
            'RequestID' => $requestID ?? $this->generateRequestID(),
        ]);
    }

    /**
     * Purchase Cable TV Subscription.
     *
     * @param string $cableTV Provider (dstv, gotv, startimes)
     * @param string $package Package Code
     * @param string $smartCardNo SmartCard/IUC Number
     * @param string $phoneNo Phone number used for the subscription
     * @param string|null $requestID Unique Request ID (optional, generated if null)
     * @return array
     * @throws ClubkashException
     */
    public function purchaseCableTV(string $cableTV, string $package, string $smartCardNo, string $phoneNo, string $requestID = null): array
    {
        return $this->request('GET', 'APICableTVV1.asp', [
            'CableTV' => $cableTV,
            'Package' => $package,
            'SmartCardNo' => $smartCardNo,
            'PhoneNo' => $phoneNo,
            'RequestID' => $requestID ?? $this->generateRequestID(),
        ]);
    }

    /**
     * Purchase Electricity Token.
     *
     * @param string $electricCompany Company Code (01=Eko, 02=Ikeja, etc.)
     * @param string $meterNo Meter Number
     * @param string $meterType Meter Type (01=Prepaid, 02=Postpaid)
     * @param float $amount Amount
     * @param string $phoneNo Phone number
     * @param string|null $requestID Unique Request ID (optional, generated if null)
     * @return array
     * @throws ClubkashException
     */
    public function purchaseElectricity(string $electricCompany, string $meterNo, string $meterType, float $amount, string $phoneNo, string $requestID = null): array
    {
        return $this->request('GET', 'APIElectricityV1.asp', [
            'ElectricCompany' => $electricCompany,
            'MeterNo' => $meterNo,
            'MeterType' => $meterType,
            'Amount' => $amount,
            'PhoneNo' => $phoneNo,
            'RequestID' => $requestID ?? $this->generateRequestID(),
        ]);
    }

    /**
     * Generate a unique request ID.
     *
     * @return string
     */
    private function generateRequestID(): string
    {
        return uniqid('', true);
    }
}
