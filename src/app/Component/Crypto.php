<?php

namespace App\Component;

class Crypto
{
    private static $key = null;

    public static function init($key)
    {
        self::$key = $key;
        return (new self());
    }

    public static function generateKey()
    {
        $method = 'AES-128-CBC';
        $ivlen = openssl_cipher_iv_length($method);
        $isCryptoStrong = false; // Will be set to true by the function if the algorithm used was cryptographically secure
        $iv = openssl_random_pseudo_bytes($ivlen, $isCryptoStrong);
        return bin2hex($iv);
    }

    public static function sign($input)
    {
        if( !self::$key )
            return ['data' => null, 'mac' => null];

        $key = hex2bin(self::$key);

        $data_string = $input;
        //$data_string = gettype($data_string) == 'string' && @json_decode($data_string, true) ? @json_decode($data_string, true) : $data_string;
        //$data_string = gettype($data_string) == 'string' ? $data_string : json_encode($data_string, JSON_UNESCAPED_UNICODE);
        //pre($data_string);

        $method = 'AES-128-CBC';
        $ivlen = openssl_cipher_iv_length($method);
        $isCryptoStrong = false; // Will be set to true by the function if the algorithm used was cryptographically secure
        $iv = openssl_random_pseudo_bytes($ivlen, $isCryptoStrong);

        $ciphertext_raw = openssl_encrypt($data_string, 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
        $ciphertext_raw_64 = base64_encode($ciphertext_raw);

        $hmac = bin2hex(hash_hmac('SHA256', $ciphertext_raw_64, $key, $as_binary = true));
        $iv = bin2hex($iv);

        //pre(self::verify($data_string, base64_encode($hmac . $iv)));

        return ['data' => json_decode($data_string, true), 'mac' => base64_encode($hmac . $iv)];
    }

    public static function verify($input, $mac_hash)
    {
        if( !self::$key )
            return 'data_verified_error';

        try {

            $b64_string = base64_decode($mac_hash);
            $mac_length = 64;
            $iv_length = 32;

            $data_string = $input;
            //pre(in_array(gettype($input), ['string', null]) || $input == null);
            //die(pre($data_string));

            $encryptMac = substr($b64_string, 0, $mac_length);
            $encryptIv = substr($b64_string, $mac_length, $iv_length);

            $key = hex2bin(self::$key);
            $iv = hex2bin($encryptIv);
            $hmac = hex2bin($encryptMac);

            $ciphertext_raw = openssl_encrypt($data_string, 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
            $ciphertext_raw_64 = base64_encode($ciphertext_raw);
            $calcmac = hash_hmac('SHA256', $ciphertext_raw_64, $key, $as_binary = true);
        }
        catch(\Exception $e){

            return 'data_verified_error';
        }

        if (hash_equals($hmac, $calcmac)) {// timing attack safe comparison

            return self::canJSON($input);
        }
        return 'data_verified_error';
    }

    public static function encrypt($input)
    {
        if( !self::$key )
            return null;

        $input = gettype($input) == 'string' ? $input : json_encode($input, JSON_UNESCAPED_UNICODE);
        $key = hex2bin(self::$key);

        $method = 'AES-128-CBC';
        $ivlen = openssl_cipher_iv_length($method);
        $isCryptoStrong = false; // Will be set to true by the function if the algorithm used was cryptographically secure
        $iv = openssl_random_pseudo_bytes($ivlen, $isCryptoStrong);

        $ciphertext_raw = openssl_encrypt($input, 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
        $ciphertext_raw_64 = base64_encode($ciphertext_raw);

        $hmac = bin2hex(hash_hmac('SHA256', $input, $key, $as_binary = true));
        $iv = bin2hex($iv);

        return base64_encode($hmac . $iv . $ciphertext_raw_64);
    }

    public static function decrypt($b64)
    {
        if( !self::$key )
            return null;

        try {

            $b64_string = base64_decode($b64);
            $mac_length = 64;
            $iv_length = 32;

            $encryptMac = substr($b64_string, 0, $mac_length);
            $encryptIv = substr($b64_string, $mac_length, $iv_length);
            $encrypted = substr($b64_string, $mac_length + $iv_length);

            $key = hex2bin(self::$key);
            $iv = hex2bin($encryptIv);
            $hmac = hex2bin($encryptMac);

            $original_plaintext = openssl_decrypt($encrypted, 'AES-128-CBC', $key, $options = 0, $iv);
            $decrypted = trim($original_plaintext);

            $calcmac = hash_hmac('SHA256', $decrypted, $key, $as_binary = true);

            if (hash_equals($hmac, $calcmac)) {

                return self::canJSON($decrypted);
            }
        }
        catch (\Exception $e){

        }
        return null;
    }

    public static function canJSON($input)
    {
        $json = gettype($input) == 'string' ? @json_decode($input, true) : null;
        if ($json)
            return $json;
        return $input;
    }
}
