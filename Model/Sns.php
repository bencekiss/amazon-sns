<?php
/**
 * Copyright © 2015 ShopGo. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShopGo\AmazonSns\Model;

use Aws\Sns\SnsClient;
use Aws\Sns\MessageValidator\Message;
use Aws\Sns\MessageValidator\MessageValidator;

/**
 * SNS model
 */
class Sns extends \Magento\Framework\Model\AbstractModel
{
    /**
     * SNS message type "subscription confirmation"
     */
    const MESSAGE_TYPE_SUBSCRIPTION_CONFIRMATION = 'SubscriptionConfirmation';

    /**
     * SNS message type "notification"
     */
    const MESSAGE_TYPE_NOTIFICATION = 'Notification';

    /**
     * SNS endpoint path
     */
    const ENDPOINT_PATH = 'amazon/sns/endpoint';

    /**
     * SNS event "notificaiton"
     */
    const SNS_EVENT_NOTIFICATION = 'sns_notification';

    /**
     * Authenticate on subscribe
     */
    const AUTHENTICATE_ON_SUBSCRIBE = 'true';

    /**
     * Default version
     */
    const VERSION = 'latest';

    /**
     * XML path general version
     */
    const XML_PATH_GENERAL_VERSION = 'amazon_sns/general/version';

    /**
     * XML path general region
     */
    const XML_PATH_GENERAL_REGION = 'amazon_sns/general/region';

    /**
     * XML path general protocol
     */
    const XML_PATH_GENERAL_PROTOCOL = 'amazon_sns/general/protocol';

    /**
     * XML path general topic ARN
     */
    const XML_PATH_GENERAL_TOPIC_ARN = 'amazon_sns/general/topic_arn';

    /**
     * XML path credentials AWS key
     */
    const XML_PATH_CREDENTIALS_AWS_KEY = 'amazon_sns/credentials/aws_key';

    /**
     * XML path credentials AWS secret
     */
    const XML_PATH_CREDENTIALS_AWS_SECRET = 'amazon_sns/credentials/aws_secret';

    /**
     * @var SnsClient
     */
    protected $_sns;

    /**
     * @var MessageValidator
     */
    protected $_messageValidator;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param MessageValidator $messageValidator
     * @param Config $config
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        MessageValidator $messageValidator,
        Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_config = $config;
        $this->_sns = SnsClient::factory($this->getConfig());
        $this->_messageValidator = $messageValidator;
        $this->_storeManager = $storeManager;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        $version = $this->_config->getConfigData(self::XML_PATH_GENERAL_VERSION);
        return !$version ? self::VERSION : $version;
    }

    /**
     * Get region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->_config->getConfigData(self::XML_PATH_GENERAL_REGION);
    }

    /**
     * Get protocol
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->_config->getConfigData(self::XML_PATH_GENERAL_PROTOCOL);
    }

    /**
     * Get topic ARN
     *
     * @return string
     */
    public function getTopicArn()
    {
        return $this->_config->getConfigData(self::XML_PATH_GENERAL_TOPIC_ARN);
    }

    /**
     * Set topic ARN
     *
     * @param string $topicArn
     */
    public function setTopicArn($topicArn)
    {
        $this->_config->setConfigData(self::XML_PATH_GENERAL_TOPIC_ARN, $topicArn);
    }

    /**
     * Get AWS key
     *
     * @return string
     */
    public function getAwsKey()
    {
        return $this->_config->getConfigData(self::XML_PATH_CREDENTIALS_AWS_KEY);
    }

    /**
     * Get AWS secret
     *
     * @return string
     */
    public function getAwsSecret()
    {
        return $this->_config->getConfigData(self::XML_PATH_CREDENTIALS_AWS_SECRET);
    }

    /**
     * Get SNS client config
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [
            'version' => $this->getVersion(),
            'region'  => $this->getRegion(),
            'credentials' => [
                'key'     => $this->getAwsKey(),
                'secret'  => $this->getAwsSecret()
            ]
        ];

        return $config;
    }

    /**
     * Get SNS endpoint
     *
     * @return string
     */
    public function getEndpoint()
    {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        return $baseUrl . self::ENDPOINT_PATH;
    }

    /**
     * Verify SNS message signature
     *
     * @return boolean
     */
    public function verifyMessageSignature()
    {
        $message = Message::fromRawPostData();
        return $this->_messageValidator->isValid($message);
    }

    /**
     * Process SNS message
     *
     * @param string $body
     * @return boolean|void
     */
    public function processMessage($body)
    {
        if (!$this->verifyMessageSignature()) {
            return false;
        }

        $data = json_decode($body, true);

        if (isset($data['Type'])) {
            return false;
        }

        switch ($data['Type']) {
            case self::MESSAGE_TYPE_SUBSCRIPTION_CONFIRMATION:
                $this->confirmSubscription($data['Token']);
                break;
            case self::MESSAGE_TYPE_NOTIFICATION:
                $this->_eventManager->dispatch(
                    self::SNS_EVENT_NOTIFICATION,
                    [
                        'notification' => json_decode($data['Message'], true)
                    ]
                );
                break;
        }
    }

    /**
     * Create SNS topic
     *
     * @return \Guzzle\Service\Resource\Model
     */
    public function createTopic()
    {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        $topicName = substr($baseUrl, strpos($baseUrl, '://') + 3, strlen($baseUrl));
        $topicName = str_replace('/', '_', rtrim($topicName, '/'));
        $topicName = str_replace('.', '_', $topicName);

        $result = $this->_sns->createTopic([
            'Name' => $topicName
        ]);

        if ($topicArn = $result->get('TopicArn')) {
            $this->setTopicArn($topicArn);
        }

        return $result;
    }

    /**
     * Subscribe to SNS topic
     *
     * @return \Guzzle\Service\Resource\Model
     */
    public function subscribe()
    {
        $result = $this->_sns->subscribe([
            'TopicArn' => $this->getTopicArn(),
            'Protocol' => $this->getProtocol(),
            'Endpoint' => $this->getEndpoint()
        ]);

        return $result;
    }

    /**
     * Confirm SNS topic subscription
     *
     * @param string $token
     * @return \Guzzle\Service\Resource\Model
     */
    public function confirmSubscription($token)
    {
        $result = $this->_sns->confirmSubscription([
            'AuthenticateOnUnsubscribe' => self::AUTHENTICATE_ON_SUBSCRIBE,
            'Token'    => $token,
            'TopicArn' => $this->getTopicArn()
        ]);

        return $result;
    }
}
