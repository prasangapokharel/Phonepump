<?php
/**
 * TronWeb PHP wrapper for TRON blockchain interactions
 */
class TronWeb {
    private $apiUrl = 'https://api.trongrid.io';
    private $apiKey = '3022fab4-cd87-48c5-b5d1-65fb3e588f67'; // Your TronGrid API key if you have one
    
    public function __construct($apiKey = '') {
        if (!empty($apiKey)) {
            $this->apiKey = $apiKey;
        }
    }
    
    /**
     * Get account balance in SUN
     */
    public function getBalance($address) {
        $url = $this->apiUrl . "/v1/accounts/{$address}";
        $response = $this->makeRequest($url);
        
        if (isset($response['data'][0]['balance'])) {
            return $response['data'][0]['balance'];
        }
        
        return 0;
    }
    
    /**
     * Send TRX from one address to another
     * Amount should be in SUN (1 TRX = 1,000,000 SUN)
     */
    public function sendTransaction($fromAddress, $toAddress, $amount, $privateKey) {
        try {
            // Step 1: Create transaction
            $createTxUrl = $this->apiUrl . "/wallet/createtransaction";
            $createTxData = [
                "owner_address" => $this->addressToHex($fromAddress),
                "to_address" => $this->addressToHex($toAddress),
                "amount" => $amount,
                "visible" => true
            ];
            
            $unsignedTx = $this->makeRequest($createTxUrl, 'POST', $createTxData);
            
            if (!isset($unsignedTx['txID'])) {
                return [
                    'success' => false,
                    'error' => 'Failed to create transaction',
                    'details' => $unsignedTx
                ];
            }
            
            // Step 2: Sign transaction
            $signTxUrl = $this->apiUrl . "/wallet/gettransactionsign";
            $signTxData = [
                "transaction" => $unsignedTx,
                "privateKey" => $privateKey
            ];
            
            $signedTx = $this->makeRequest($signTxUrl, 'POST', $signTxData);
            
            if (!isset($signedTx['signature'])) {
                return [
                    'success' => false,
                    'error' => 'Failed to sign transaction',
                    'details' => $signedTx
                ];
            }
            
            // Step 3: Broadcast transaction
            $broadcastUrl = $this->apiUrl . "/wallet/broadcasttransaction";
            $broadcastResult = $this->makeRequest($broadcastUrl, 'POST', $signedTx);
            
            if (isset($broadcastResult['result']) && $broadcastResult['result'] === true) {
                return [
                    'success' => true,
                    'txid' => $unsignedTx['txID'],
                    'details' => $broadcastResult
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to broadcast transaction',
                    'details' => $broadcastResult
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Convert TRON address to hex format
     */
    private function addressToHex($address) {
        if (substr($address, 0, 1) === 'T') {
            // Convert from Base58 to hex
            $decoded = $this->base58Decode($address);
            return '0x' . bin2hex($decoded);
        }
        return $address;
    }
    
    /**
     * Base58 decode function
     */
    private function base58Decode($input) {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);
        
        if (strlen($input) === 0) {
            return '';
        }
        
        $bytes = [0];
        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            $pos = strpos($alphabet, $char);
            
            if ($pos === false) {
                throw new Exception('Invalid character found: ' . $char);
            }
            
            for ($j = 0; $j < count($bytes); $j++) {
                $bytes[$j] *= $base;
            }
            
            $bytes[0] += $pos;
            
            $carry = 0;
            for ($j = 0; $j < count($bytes); $j++) {
                $bytes[$j] += $carry;
                $carry = $bytes[$j] >> 8;
                $bytes[$j] &= 0xff;
            }
            
            while ($carry > 0) {
                array_push($bytes, $carry & 0xff);
                $carry >>= 8;
            }
        }
        
        // Convert to binary
        $result = '';
        foreach ($bytes as $byte) {
            $result .= chr($byte);
        }
        
        return $result;
    }
    
    /**
     * Make HTTP request to TRON API
     */
    private function makeRequest($url, $method = 'GET', $data = null) {
        $curl = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        
        // Add API key if available
        $headers = [];
        if (!empty($this->apiKey)) {
            $headers[] = "TRON-PRO-API-KEY: {$this->apiKey}";
        }
        $headers[] = "Content-Type: application/json";
        $options[CURLOPT_HTTPHEADER] = $headers;
        
        // Add data for POST requests
        if ($method === 'POST' && !is_null($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error: " . $err);
        }
        
        return json_decode($response, true);
    }
}

/**
 * Create a new TronWeb instance
 */
function createTronWeb($apiKey = '') {
    return new TronWeb($apiKey);
}
?>