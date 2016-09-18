<?php

namespace ElasticBundle\Service\Elastic;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

use ElasticBundle\Service\CURLManager;
/**
 * Class ElasticManager
 * @package ElasticBundle\Service\Elastic
 */
class ElasticManager
{

    const ELASTIC_NQT_DIVIDER = 100000000;

    const TRANSACTION_TYPE_PAYMENT = 0;
    const TRANSACTION_TYPE_MESSAGING = 1;
    const TRANSACTION_TYPE_ACCOUNT_CONTROL = 2;
    const TRANSACTION_TYPE_WORK_CONTROL = 3;

    const INPUT_DATA_TYPE_BLOCK_HEIGHT = 0;
    const INPUT_DATA_TYPE_TRANSACTION_ID = 1;
    const INPUT_DATA_TYPE_TRANSACTION_FULL_HASH = 2;
    const INPUT_DATA_TYPE_ADDRESS_RS = 3;

    // TODO delete offset when Elastic wallet will be fixed
    const ELASTIC_TIME_OFFSET = 1385294399;

    /**
     * @var string
     */
    private $daemonAddress;

    private $daemonPort;

    private $elasticValidator;

    private $mainAccountPassphrase;

    /**
     * @var \ElasticBundle\Service\CURLManager
     */
    private $curlManager;

    /**
     * @param CURLManager $curlManager
     * @param string $daemonAddress
     * @param string $mainAccountPassphrase
     * @param int $daemonPort
     */
    public function __construct(CURLManager $curlManager, $daemonAddress, $daemonPort, $mainAccountPassphrase)
    {

        $this->setElasticDaemonAddress($daemonAddress, $daemonPort);
        $this->daemonPort = $daemonPort;
        $this->curlManager = $curlManager;
        $this->mainAccountPassphrase = $mainAccountPassphrase;
        $this->elasticValidator = new ElasticValidator();

    }

    /**
     * @return ElasticValidator
     */
    public function getElasticValidator()
    {

        return $this->elasticValidator;

    }

    /**
     * @param string $address
     * @param int $port
     */
    private function setElasticDaemonAddress($address, $port = 7876)
    {

        $this->daemonAddress = 'http://' . $address . ':' . $port . '/nxt?requestType=';

    }

    /**
     * @param int $firstIndex
     * @param null $lastIndex
     * @param bool|false $includeTransactions
     * @param int|null $cache
     * @return bool|mixed
     * @throws \Exception
     */
    public function getBlocks($firstIndex = 0, $lastIndex = null, $includeTransactions = false, $cacheTTL = null)
    {

        $query = 'getBlocks';

        $firstIndex = (int) $firstIndex;
        $lastIndex = (int) $lastIndex;

        if($firstIndex === 0 || $firstIndex > 0) {

            $query .= '&firstIndex=' . $firstIndex;

        }

        if($lastIndex && $lastIndex > 0) {

            $query .= '&lastIndex=' . $lastIndex;

        }

        if($includeTransactions) {

            $query .= '&includeTransactions=true';

        }

        $result = $this->curlManager->getURL($this->daemonAddress . $query, $cacheTTL ? $cacheTTL : null);

        if(!$result) {

            return false;

        }

        $blocks = json_decode($result, true);


        if(isset($blocks['errorCode'])) {

            return false;

        }

        if(!$blocks) {

            return false;

        }

        return $blocks;

    }

    /**
     * @param $type
     * @return string
     */
    public function translateTransactionNumericType($type)
    {

        if(is_numeric($type)) {

            $type = (int) $type;

        }

        if(!is_int($type)) {

            throw new Exception('Wrong parameter.');

        }

        switch($type) {

            case self::TRANSACTION_TYPE_PAYMENT: return 'Payment'; break;
            case self::TRANSACTION_TYPE_MESSAGING: return 'Message'; break;
            case self::TRANSACTION_TYPE_ACCOUNT_CONTROL: return 'Account'; break;
            case self::TRANSACTION_TYPE_WORK_CONTROL: return 'PoW'; break;
            default: return 'Unknown'; break;

        }

    }

    /**
     * @param string $address
     * @param bool $includeEffectiveBalance
     * @return bool|mixed
     * @throws \Exception
     */
    public function getAccount($address, $includeEffectiveBalance = false)
    {

        if(!$this->elasticValidator->validateAddress($address)) {

            return false;

        }

        $query = 'getAccount&account=' . $address;

        if($includeEffectiveBalance) {

            $query .= '&includeEffectiveBalance=true';

        }

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $accountInfo = json_decode($result, true);

        if(!$accountInfo) {

            return false;

        }

        if(isset($accountInfo['errorCode'])) {

            return false;

        }

        return $accountInfo;

    }

    /**
     * @param string|null $address
     * @param int $firstIndex
     * @param int $lastIndex
     * @return bool|mixed
     * @throws \Exception
     */
    public function getBlockchainTransactions($address = null, $firstIndex = 0, $lastIndex = 199)
    {

        $query = 'getBlockchainTransactions';

        if($address && $this->elasticValidator->validateAddress($address)) {

            $query .= '&account=' . $address;

        }

        $firstIndex = (int) $firstIndex;
        $lastIndex = (int) $lastIndex;

        if($firstIndex === 0 || $firstIndex > 0) {

            $query .= '&firstIndex=' . $firstIndex;

        }

        if($lastIndex > 0) {

            $query .= '&lastIndex=' . $lastIndex;

        }

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $blockchainTransactions = json_decode($result, true);

        if(!$blockchainTransactions) {

            return false;

        }

        if(isset($blockchainTransactions['errorCode'])) {

            return false;

        }

        return $blockchainTransactions;

    }

    /**
     * @param $address
     * @param bool $includeTransactions
     * @param int $firstIndex
     * @param int $lastIndex
     * @return bool|mixed
     * @throws \Exception
     */
    public function getAccountBlocks($address, $includeTransactions = false, $firstIndex = 0, $lastIndex = 199)
    {

        if(!$this->elasticValidator->validateAddress($address)) {

            return false;

        }

        $query = 'getAccountBlocks&account=' . $address;

        $firstIndex = (int) $firstIndex;
        $lastIndex = (int) $lastIndex;

        if($includeTransactions) {

            $query .= '&includeTransactions=true';

        }

        if($firstIndex === 0 || $firstIndex > 0) {

            $query .= '&firstIndex=' . $firstIndex;

        }

        if($lastIndex > 0) {

            $query .= '&lastIndex=' . $lastIndex;

        }

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $accountTransactions = json_decode($result, true);

        if(!$accountTransactions) {

            return false;

        }

        if(isset($accountTransactions['errorCode'])) {

            return false;

        }

        return $accountTransactions;

    }

    public function getNextBlockGenerators($limit = 10)
    {

        $query = 'getNextBlockGenerators';

        if($limit && is_numeric($limit)) {

            $limit = (int) $limit;
            $query .= '&limit=' . $limit;

        }

        $result = $this->curlManager->getURL($this->daemonAddress . $query, 10);

        if(!$result) {

            return false;

        }

        $nextBlockGenerators = json_decode($result, true);

        if(!$nextBlockGenerators) {

            return false;

        }

        if(isset($nextBlockGenerators['errorCode'])) {

            return false;

        }

        return $nextBlockGenerators;

    }

    public function getBlockByHeight($blockHeight, $includeTransactions = false)
    {

        if(!$this->elasticValidator->validateBlockHeight($blockHeight)) {

            return false;

        }

        $blockHeight = (int) $blockHeight;

        $query = 'getBlock&height=' . $blockHeight;

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $blockInfo = json_decode($result, true);

        if(!$blockInfo) {

            return false;

        }

        if(isset($blockInfo['errorCode'])) {

            return false;

        }

        if(isset($blockInfo['transactions']) && is_array($blockInfo['transactions']) && count($blockInfo['transactions'])) {

            foreach($blockInfo['transactions'] as $transactionKey => $transactionId) {

                $transactionInfo = $this->getTransactionById($transactionId);

                if($transactionInfo) {

                    $blockInfo['transactions'][$transactionKey] = $transactionInfo;

                } else {

                    unset($blockInfo['transactions'][$transactionKey]);

                }

            }

        }

        return $blockInfo;

    }

    /**
     * @param int $transactionId
     * @return bool|mixed
     * @throws \Exception
     */
    public function getTransactionById($transactionId)
    {

        if(!$this->elasticValidator->validateTransactionId($transactionId)) {

            return false;

        }

        $query = 'getTransaction&transaction=' . $transactionId;

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $transactionInfo = json_decode($result, true);

        if(!$transactionInfo) {

            return false;

        }

        if(isset($transactionInfo['errorCode'])) {

            return false;

        }

        return $transactionInfo;

    }

    /**
     * @param string $transactionFullHash
     * @return bool|mixed
     * @throws \Exception
     */
    public function getTransactionByFullHash($transactionFullHash)
    {

        if(!$this->elasticValidator->validateTransactionFullHash($transactionFullHash)) {

            return false;

        }

        $query = 'getTransaction&fullHash=' . $transactionFullHash;

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $transactionInfo = json_decode($result, true);

        if(!$transactionInfo) {

            return false;

        }

        if(isset($transactionInfo['errorCode'])) {

            return false;

        }

        return $transactionInfo;

    }

    public function getPeers($includePeerInfo = false, $active = false, $state = null)
    {

        $query = 'getPeers';

        if($includePeerInfo) {

            $query .= '&includePeerInfo=true';

        }

        if($active) {

            $query .= '&active=true';

        }

        if($state) {

            $state = strtoupper($state);

            switch($state) {

                case 'CONNECTED':
                case 'DISCONNECTED':
                case 'NON_CONNECTED':
                {
                    $query .= '&state=' . $state;
                } break;

            }

        }

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $peersInfo = json_decode($result, true);

        if(!$peersInfo) {

            return false;

        }

        if(isset($peersInfo['errorCode'])) {

            return false;

        }

        return $peersInfo;

    }

    public function getPeer($ipAddress)
    {

        $query = 'getPeer';

        if(!$this->elasticValidator->validateIpAddress($ipAddress)) {

            return false;

        }

        $query .= '&peer=' . $ipAddress;

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $peerInfo = json_decode($result, true);

        if(!$peerInfo) {

            return false;

        }

        if(isset($peerInfo['errorCode'])) {

            return false;

        }

        return $peerInfo;

    }

    public function isPeerUp($ipAddress)
    {

        if(!$this->elasticValidator->validateIpAddress($ipAddress)) {

            return false;

        }

        $result = shell_exec("nmap -p {$this->daemonPort} -PN {$ipAddress}");

        if(!$result && !trim($result)) {


            return false;

        }

        if(strpos($result, $this->daemonPort . '/tcp open') !== false) {

            return true;

        }

        return false;

    }

    public function getInboundPeers($includePeerInfo = false)
    {

        $query = 'getInboundPeers';

        if($includePeerInfo) {

            $query .= '&includePeerInfo=true';

        }

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $peersInfo = json_decode($result, true);

        if(!$peersInfo) {

            return false;

        }

        if(isset($peersInfo['errorCode'])) {

            return false;

        }

        return $peersInfo;

    }

    public function getBlockchainStatus()
    {

        $query = 'getBlockchainStatus';

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $blockchainStatus = json_decode($result, true);

        if(!$blockchainStatus) {

            return false;

        }

        if(isset($blockchainStatus['errorCode'])) {

            return false;

        }

        return $blockchainStatus;

    }

    public function getMyInfo()
    {

        $query = 'getMyInfo';

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $myInfo = json_decode($result, true);

        if(!$myInfo) {

            return false;

        }

        if(isset($myInfo['errorCode'])) {

            return false;

        }

        return $myInfo;

    }

    public function sendMoney($address, $amount, $fee = 1, $deadline = 1440)
    {

        if(!$this->elasticValidator->validateAddress($address)) {

            return false;

        }

        if(!is_numeric($amount)) {

            return false;

        }

        if(!is_numeric($fee)) {

            return false;

        }

        if(!is_numeric($deadline)) {

            return false;

        }

        $amount = (int) $amount;
        $amountNQT = $amount * self::ELASTIC_NQT_DIVIDER;
        $fee = (int) $fee;
        $feeNQT = $fee * self::ELASTIC_NQT_DIVIDER;

        $deadline = (int) $deadline;

        $request = 'sendMoney';
        $query = '';

        if($address) {

            if(strlen($query) === 0) {

                $query .= 'recipient=' . $address;

            } else {

                $query .= '&recipient=' . $address;

            }

        }

        if($amountNQT) {

            if(strlen($query) === 0) {

                $query .= 'amountNQT=' . $amountNQT;

            } else {

                $query .= '&amountNQT=' . $amountNQT;

            }


        }

        if($feeNQT) {

            if(strlen($query) === 0) {

                $query .= 'feeNQT=' . $feeNQT;

            } else {

                $query .= '&feeNQT=' . $feeNQT;

            }

        }

        if($deadline) {

            if(strlen($query) === 0) {

                $query .= 'deadline=' . $deadline;

            } else {

                $query .= '&deadline=' . $deadline;

            }


        }

        if(strlen($query) === 0) {

            $query .= 'secretPhrase=' . $this->mainAccountPassphrase;

        } else {

            $query .= '&secretPhrase=' . $this->mainAccountPassphrase;

        }

        $result = $this->curlManager->getURLByPostMethod($this->daemonAddress . $request, $query);

        if(!$result) {

            return false;

        }

        $sendMoney = json_decode($result, true);

        if(!$sendMoney) {

            return false;

        }

        if(isset($sendMoney['errorCode'])) {

            return false;

        }

        return $sendMoney;

    }


    public function getTime()
    {

        $query = 'getTime';

        $result = $this->curlManager->getURL($this->daemonAddress . $query);

        if(!$result) {

            return false;

        }

        $time = json_decode($result, true);

        if(!$time) {

            return false;

        }

        if(isset($time['errorCode'])) {

            return false;

        }

        return $time;

    }

    /**
     * @param mixed $input
     * @return int
     */
    public function determineDataType($input)
    {

        if($this->elasticValidator->validateTransactionFullHash($input)) {

            return self::INPUT_DATA_TYPE_TRANSACTION_FULL_HASH;

        }

        if($this->elasticValidator->validateTransactionId($input)) {

            return self::INPUT_DATA_TYPE_TRANSACTION_ID;

        }

        if($this->elasticValidator->validateAddress($input)) {

            return self::INPUT_DATA_TYPE_ADDRESS_RS;

        }

        if($this->elasticValidator->validateBlockHeight($input)) {

            return self::INPUT_DATA_TYPE_BLOCK_HEIGHT;

        }

        return -1;

    }

}