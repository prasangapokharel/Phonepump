<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Elliptic\EC;
use kornrunner\Keccak;

class TronWalletGenerator {
    
    /**
     * Generate a new TRON wallet
     * @return array
     */
    public static function generateWallet() {
        try {
            // Generate private key (32 bytes)
            $privateKey = bin2hex(random_bytes(32));
            
            // Generate address from private key
            $address = self::privateKeyToAddress($privateKey);
            
            if (!$address) {
                throw new Exception("Failed to generate address from private key");
            }
            
            // Verify the generated address
            if (!self::isValidTronAddress($address)) {
                throw new Exception("Generated invalid TRON address");
            }
            
            return [
                'success' => true,
                'private_key' => $privateKey,
                'address' => $address,
                'public_key' => self::privateKeyToPublicKey($privateKey)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Convert private key to TRON address
     * @param string $privateKeyHex
     * @return string|false
     */
    public static function privateKeyToAddress($privateKeyHex) {
        try {
            // Create elliptic curve instance
            $ec = new EC('secp256k1');
            
            // Get key pair from private key
            $keyPair = $ec->keyFromPrivate($privateKeyHex, 'hex');
            
            // Get public key (uncompressed, without 0x04 prefix)
            $publicKey = $keyPair->getPublic('hex');
            
            // Remove 0x04 prefix if present
            if (substr($publicKey, 0, 2) === '04') {
                $publicKey = substr($publicKey, 2);
            }
            
            // Convert public key to binary
            $publicKeyBin = hex2bin($publicKey);
            
            // Get Keccak-256 hash of public key
            $hash = Keccak::hash($publicKeyBin, 256);
            
            // Take last 20 bytes of the hash
            $addressBytes = substr($hash, -20);
            
            // Add TRON prefix (0x41)
            $addressWithPrefix = "\x41" . $addressBytes;
            
            // Calculate checksum (double SHA256)
            $checksum = hash('sha256', hash('sha256', $addressWithPrefix, true), true);
            
            // Take first 4 bytes of checksum
            $checksumBytes = substr($checksum, 0, 4);
            
            // Combine address with checksum
            $fullAddress = $addressWithPrefix . $checksumBytes;
            
            // Encode in Base58
            $address = self::base58Encode($fullAddress);
            
            return $address;
            
        } catch (Exception $e) {
            error_log("Error converting private key to address: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get public key from private key
     * @param string $privateKeyHex
     * @return string|false
     */
    public static function privateKeyToPublicKey($privateKeyHex) {
        try {
            $ec = new EC('secp256k1');
            $keyPair = $ec->keyFromPrivate($privateKeyHex, 'hex');
            return $keyPair->getPublic('hex');
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Base58 encoding without GMP dependency
     * @param string $data
     * @return string
     */
    private static function base58Encode($data) {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = 58;
        
        // Convert binary data to hex string for easier manipulation
        $hex = bin2hex($data);
        
        // Use BCMath if available, otherwise use pure PHP
        if (extension_loaded('bcmath')) {
            $num = self::hexToBcmath($hex);
            
            $encoded = '';
            while (bccomp($num, '0') > 0) {
                $remainder = bcmod($num, $base);
                $encoded = $alphabet[intval($remainder)] . $encoded;
                $num = bcdiv($num, $base, 0);
            }
        } else {
            // Pure PHP implementation for smaller numbers
            $bytes = array_values(unpack('C*', $data));
            $num = '0';
            
            // Convert bytes to decimal string
            foreach ($bytes as $byte) {
                $num = self::addStrings(self::multiplyStrings($num, '256'), strval($byte));
            }
            
            $encoded = '';
            while (self::compareStrings($num, '0') > 0) {
                $remainder = self::modString($num, strval($base));
                $encoded = $alphabet[intval($remainder)] . $encoded;
                $num = self::divideString($num, strval($base));
            }
        }
        
        // Add leading zeros
        for ($i = 0; $i < strlen($data) && $data[$i] === "\x00"; $i++) {
            $encoded = $alphabet[0] . $encoded;
        }
        
        return $encoded;
    }
    
    /**
     * Base58 decoding without GMP dependency
     * @param string $encoded
     * @return string
     */
    public static function base58Decode($encoded) {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = 58;
        
        if (extension_loaded('bcmath')) {
            $num = '0';
            for ($i = 0; $i < strlen($encoded); $i++) {
                $char = $encoded[$i];
                $pos = strpos($alphabet, $char);
                if ($pos === false) {
                    throw new Exception("Invalid character in Base58 string");
                }
                $num = bcadd(bcmul($num, $base), $pos);
            }
            
            $hex = self::bcmathToHex($num);
        } else {
            // Pure PHP implementation
            $num = '0';
            for ($i = 0; $i < strlen($encoded); $i++) {
                $char = $encoded[$i];
                $pos = strpos($alphabet, $char);
                if ($pos === false) {
                    throw new Exception("Invalid character in Base58 string");
                }
                $num = self::addStrings(self::multiplyStrings($num, strval($base)), strval($pos));
            }
            
            $hex = self::decimalToHex($num);
        }
        
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }
        
        $decoded = hex2bin($hex);
        
        // Add leading zeros
        for ($i = 0; $i < strlen($encoded) && $encoded[$i] === $alphabet[0]; $i++) {
            $decoded = "\x00" . $decoded;
        }
        
        return $decoded;
    }
    
    /**
     * Convert hex to BCMath number
     */
    private static function hexToBcmath($hex) {
        $num = '0';
        for ($i = 0; $i < strlen($hex); $i++) {
            $digit = hexdec($hex[$i]);
            $num = bcadd(bcmul($num, '16'), $digit);
        }
        return $num;
    }
    
    /**
     * Convert BCMath number to hex
     */
    private static function bcmathToHex($num) {
        $hex = '';
        while (bccomp($num, '0') > 0) {
            $remainder = bcmod($num, '16');
            $hex = dechex(intval($remainder)) . $hex;
            $num = bcdiv($num, '16', 0);
        }
        return $hex ?: '0';
    }
    
    /**
     * Pure PHP string arithmetic functions
     */
    private static function addStrings($a, $b) {
        $result = '';
        $carry = 0;
        $i = strlen($a) - 1;
        $j = strlen($b) - 1;
        
        while ($i >= 0 || $j >= 0 || $carry > 0) {
            $sum = $carry;
            if ($i >= 0) $sum += intval($a[$i--]);
            if ($j >= 0) $sum += intval($b[$j--]);
            
            $result = ($sum % 10) . $result;
            $carry = intval($sum / 10);
        }
        
        return $result;
    }
    
    private static function multiplyStrings($a, $b) {
        if ($a === '0' || $b === '0') return '0';
        
        $result = '0';
        for ($i = strlen($b) - 1; $i >= 0; $i--) {
            $temp = self::multiplyByDigit($a, intval($b[$i]));
            $temp .= str_repeat('0', strlen($b) - 1 - $i);
            $result = self::addStrings($result, $temp);
        }
        
        return $result;
    }
    
    private static function multiplyByDigit($num, $digit) {
        if ($digit === 0) return '0';
        
        $result = '';
        $carry = 0;
        
        for ($i = strlen($num) - 1; $i >= 0; $i--) {
            $prod = intval($num[$i]) * $digit + $carry;
            $result = ($prod % 10) . $result;
            $carry = intval($prod / 10);
        }
        
        if ($carry > 0) $result = $carry . $result;
        
        return $result;
    }
    
    private static function compareStrings($a, $b) {
        if (strlen($a) > strlen($b)) return 1;
        if (strlen($a) < strlen($b)) return -1;
        return strcmp($a, $b);
    }
    
    private static function divideString($a, $b) {
        if ($b === '0') throw new Exception("Division by zero");
        if (self::compareStrings($a, $b) < 0) return '0';
        
        $result = '';
        $temp = '';
        
        for ($i = 0; $i < strlen($a); $i++) {
            $temp .= $a[$i];
            $temp = ltrim($temp, '0') ?: '0';
            
            $count = 0;
            while (self::compareStrings($temp, $b) >= 0) {
                $temp = self::subtractStrings($temp, $b);
                $count++;
            }
            
            $result .= $count;
        }
        
        return ltrim($result, '0') ?: '0';
    }
    
    private static function modString($a, $b) {
        $quotient = self::divideString($a, $b);
        $product = self::multiplyStrings($quotient, $b);
        return self::subtractStrings($a, $product);
    }
    
    private static function subtractStrings($a, $b) {
        if (self::compareStrings($a, $b) < 0) return '0';
        
        $result = '';
        $borrow = 0;
        $i = strlen($a) - 1;
        $j = strlen($b) - 1;
        
        while ($i >= 0) {
            $sub = intval($a[$i]) - $borrow;
            if ($j >= 0) $sub -= intval($b[$j--]);
            
            if ($sub < 0) {
                $sub += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }
            
            $result = $sub . $result;
            $i--;
        }
        
        return ltrim($result, '0') ?: '0';
    }
    
    private static function decimalToHex($decimal) {
        if ($decimal === '0') return '0';
        
        $hex = '';
        while (self::compareStrings($decimal, '0') > 0) {
            $remainder = self::modString($decimal, '16');
            $hex = dechex(intval($remainder)) . $hex;
            $decimal = self::divideString($decimal, '16');
        }
        
        return $hex;
    }
    
    /**
     * Validate TRON address
     * @param string $address
     * @return bool
     */
    public static function isValidTronAddress($address) {
        try {
            // Check if address starts with 'T' and has correct length
            if (strlen($address) !== 34 || $address[0] !== 'T') {
                return false;
            }
            
            // Decode the address
            $decoded = self::base58Decode($address);
            
            // Check length (21 bytes address + 4 bytes checksum)
            if (strlen($decoded) !== 25) {
                return false;
            }
            
            // Extract address and checksum
            $addressBytes = substr($decoded, 0, 21);
            $checksum = substr($decoded, 21, 4);
            
            // Verify checksum
            $calculatedChecksum = substr(hash('sha256', hash('sha256', $addressBytes, true), true), 0, 4);
            
            return $checksum === $calculatedChecksum;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Import wallet from private key
     * @param string $privateKeyHex
     * @return array
     */
    public static function importFromPrivateKey($privateKeyHex) {
        try {
            // Validate private key format
            if (!ctype_xdigit($privateKeyHex) || strlen($privateKeyHex) !== 64) {
                throw new Exception("Invalid private key format");
            }
            
            // Generate address from private key
            $address = self::privateKeyToAddress($privateKeyHex);
            
            if (!$address) {
                throw new Exception("Failed to generate address from private key");
            }
            
            return [
                'success' => true,
                'private_key' => $privateKeyHex,
                'address' => $address,
                'public_key' => self::privateKeyToPublicKey($privateKeyHex)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>