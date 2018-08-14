<?php
require_once 'DirectkitException.php';

final class DirectkitJson
{
    /**
     *
     * @var string $directkitUrl
     */
    private $directkitUrl;
    
    /**
     *
     * @var string $webkitUrl
     */
    private $webkitUrl;
    
    /**
     *
     * @var string $wlLogin
     */
    private $wlLogin;
    
    /**
     *
     * @var string $wlPass
     */
    private $wlPass;
    
    /**
     *
     * @var string $language
     */
    private $language;
    
    public function __construct($directkitUrl, $webkitUrl, $wlLogin, $wlPass, $language)
    {
        
        //@TODO validate args
        
        $this->directkitUrl = $directkitUrl . "/";
        $this->webkitUrl = $webkitUrl;
        $this->wlLogin = $wlLogin;
        $this->wlPass = $wlPass;

        $supportedLangs = array(
            'da' => 'da',
            'de' => 'ge',
            'en' => 'en',
            'es' => 'sp',
            'fi' => 'fi',
            'fr' => 'fr',
            'it' => 'it',
            'ko' => 'ko',
            'no' => 'no',
            'pt' => 'po',
            'sv' => 'sw'
        );
        $language = substr($language, 0, 2);
        if (array_key_exists($language, $supportedLangs)) {
            $this->language = $supportedLangs[$language];
        } else {
            $this->language = 'en';
        }
    }

    public function GetWalletDetails($params)
    {
        $response = self::sendRequest('GetWalletDetails', $params);
        
        return $response->WALLET;
    }

    /**
     *
     * @param array $params
     * @return MoneyInWeb
     */
    public function MoneyInWebInit($params)
    {
        $response =  self::sendRequest('MoneyInWebInit', $params);
        return $response->MONEYINWEB;
    }

    public function MoneyInWithCardId($params)
    {
        $response = self::sendRequest('MoneyInWithCardId', $params);
    
        return $response->TRANS->HPAY;
    }

    public function MoneyInIDealInit($params)
    {
        $response =  self::sendRequest('MoneyInIDealInit', $params);
        return $response->IDEALINIT;
    }

    public function MoneyInIDealConfirm($transactionId)
    {
        $params = array(
            'transactionId'=> $transactionId
        );
        
        $response = self::sendRequest('MoneyInIDealConfirm', $params);
        return $response->TRANS->HPAY;
    }

    public function MoneyInSofortInit($params)
    {
        $response =  self::sendRequest('MoneyInSofortInit', $params);
        return $response->SOFORTINIT;
    }
    
    public function GetMoneyInTransDetails($params)
    {
        $response = self::sendRequest('GetMoneyInTransDetails', $params);

        foreach ($response->TRANS->HPAY as $HPAY) {
            return $HPAY;
        }

        throw new Exception("No Result for getMoneyInTransDetails");
    }
    
    // IP of end-user
    private function getUserIP() {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        elseif (!empty($_SERVER["REMOTE_ADDR"])) {
            $ip = $_SERVER["REMOTE_ADDR"];
        } else {
            $ip = "";
        }

        return $ip;
    }

    private function sendRequest($methodName, $params)
    {
        $ua = "";
        if (isset($_SERVER["HTTP_USER_AGENT"])) {
            $ua = $_SERVER["HTTP_USER_AGENT"];
        }
        $ua = "Woocommerce/" . $ua;
            
        $ip = $this->getUserIP();;
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        $url = $this->directkitUrl . $methodName;

        $baseParams = array(
                'wlLogin'  => $this->wlLogin,
                'wlPass'   => $this->wlPass,
                'language' => $this->language,
                'version'  => "10.0",
                'walletIp' => $ip,
                'walletUa' => $ua,
        );
        
        $requestParams = array_merge($baseParams, $params);
        $requestParams = array('p' => $requestParams);
                        
        $headers = array(
            "Content-type: application/json; charset=utf-8",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Pragma: no-cache"
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestParams));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        // Log
        $requestParams["p"]["wlPass"] = "*masked*";
        WC_Gateway_Lemonway::log("Lemon Way: " . $url . " - Request: " . json_encode($requestParams) . " - Response: " . $response);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        } else {
            $responseCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $this->throwErrorResponseCode($responseCode);
            
            if ($responseCode == 200) {
                //General parsing
                $response = json_decode($response);
                
                //Check error
                if (isset($response->d->E)) {
                    throw new DirectkitException($response->d->E->Msg, $response->d->E->Code);
                }
                 
                return $response->d;
            }
        }
    }
    
    /**
     * Throw an Exception for HTTP CODE
     * @param int $responseCode
     * @throws Exception
     */
    protected function throwErrorResponseCode($responseCode)
    {
        switch ($responseCode) {
            case 200:
                break;
            case 400:
                throw new Exception("Bad Request : The server cannot or will not process the request due to something that is perceived to be a client error", 400);
                break;
            case 403:
                throw new Exception("IP is not allowed to access Lemon Way's API, please contact support@lemonway.fr", 403);
                break;
            case 404:
                throw new Exception("Check that the access URLs are correct. If yes, please contact support@lemonway.fr", 404);
                print "Check that the access URLs are correct. If yes, please contact support@lemonway.fr";
                break;
            case 500:
                throw new Exception("Lemon Way internal server error, please contact support@lemonway.fr", 500);
                break;
            default:
                throw  new Exception(sprintf("HTTP CODE %d IS NOT SUPPORTED", $responseCode), $responseCode);
                break;
        }
    }
    
    public function formatMoneyInUrl($moneyInToken, $cssUrl = '')
    {
        return $this->webkitUrl . "?moneyintoken=".$moneyInToken.'&p='.urlencode($cssUrl).'&lang='.$this->language;
    }
}
