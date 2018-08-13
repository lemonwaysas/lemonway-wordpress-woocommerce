<?php
require_once 'models/Operation.php';
require_once 'models/Wallet.php';
require_once 'models/IdealInit.php';
require_once 'models/SofortInit.php';
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
    
    /**
     * Information about used plugin E.g: Prestashop-1.6.4 or Magento-1.9.3 ...
     * @var string $pluginType
     */
    private $pluginType;
    
    public function __construct($directkitUrl, $webkitUrl, $wlLogin, $wlPass, $language, $pluginType = 'Generic-1.0.0')
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
        
        $this->pluginType = $pluginType;
    }

    public function GetWalletDetails($params)
    {
        $response = self::sendRequest('GetWalletDetails', $params);
        
        return new Wallet($response->WALLET);
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
        return new IdealInit($response);
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
        return new SofortInit($response);
    }
    
    public function GetMoneyInTransDetails($params)
    {
        $response = self::sendRequest('GetMoneyInTransDetails', $params);

        foreach ($response->TRANS->HPAY as $HPAY) {
            return $HPAY;
        }

        throw new Exception("No Result for getMoneyInTransDetails");
    }
    
    private function sendRequest($methodName, $params)
    {
        $ua = '';
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
        }
        $ua = $this->pluginType."/" . $ua;
            
        $ip = '';
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
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
        //self::printDirectkitInput($requestParams);
                        
        $headers = array(
            "Content-type: application/json; charset=utf-8",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Pragma: no-cache"
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->directkitUrl . $methodName);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestParams));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

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
                
                //self::printDirectkitOutput($response);
                    
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
