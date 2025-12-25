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

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var GuzzleClient
     */
    private $httpClient;

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
        // Add auth credentials to every request
        $params['UserID'] = $this->userID;
        $params['APIKey'] = $this->apiKey;

        try {
            $options = [];
            if (strtoupper($method) === 'GET') {
                $options['query'] = $params;
            } else {
                // For POST/PUT, documentation implies functionality usually via params or body.
                // Assuming query params for now as per research "utilize the HTTP GET method".
                // But if POST is needed, we might need 'form_params' or 'json'.
                // Research said: "All endpoints ... utilize the HTTP GET method".
                // So strictly GET.
                $options['query'] = $params;
            }

            $response = $this->httpClient->request($method, $endpoint, $options);
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Some APIs return plain text on error or success, check for that?
                // For now throw exception
                throw new ClubkashException('Failed to decode JSON response: ' . $content);
            }

            // Check API specific error codes if structure allows.
            // Usually valid JSON is returned.
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
