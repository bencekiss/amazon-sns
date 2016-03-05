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
     * SNS standard endpoint path
     */
    const STANDARD_ENDPOINT_PATH = 'amazon/sns/endpoint';

    /**
     * SNS API endpoint path
     *
     * @TODO This is currently not supported,
     * due to an issue that is described in the following link:
     * https://forums.aws.amazon.com/thread.jspa?messageID=418160
     * Once they fix it, this option will be used.
     * You can read more about it here:
     * http://docs.aws.amazon.com/sns/latest/dg/SendMessageToHttp.html
     */
    const API_ENDPOINT_PATH = 'rest/default/V1/amazon/sns/endpoint';

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
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var TopicFactory
     */
    protected $_topicFactory;

    /**
     * @param MessageValidator $messageValidator
     * @param Config $config
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param TopicFactory $topic
     */
    public function __construct(
        MessageValidator $messageValidator,
        Config $config,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        TopicFactory $topicFactory
    ) {
        $this->_config = $config;
        $this->_messageValidator = $messageValidator;
        $this->_eventManager = $eventManager;
        $this->_storeManager = $storeManager;
        $this->_topicFactory = $topicFactory;
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
        return $baseUrl . self::STANDARD_ENDPOINT_PATH;
    }

    /**
     * Get SNS Client
     *
     * @return SnsClient
     */
    public function getSnsClient()
    {
        if (!$this->_sns) {
            $this->_sns = SnsClient::factory($this->getConfig());
        }

        return $this->_sns;
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

        switch ($data['Type']) {
            case self::MESSAGE_TYPE_SUBSCRIPTION_CONFIRMATION:
                $this->_topicFactory->create()->confirmSubscription($data['Token'], $data['TopicArn']);
                break;
            case self::MESSAGE_TYPE_NOTIFICATION:
                $topic = $this->_topicFactory->create()->load($data['TopicArn']);
                if ($topic && $topic->getIsActive()) {
                    $this->_eventManager->dispatch(
                        self::SNS_EVENT_NOTIFICATION,
                        [
                            'notification' => json_decode($data['Message'], true)
                        ]
                    );
                }
                break;
        }
    }
}
