<?php
/**
 * Copyright © 2015 ShopGo. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShopGo\AmazonSns\Model;

use Aws\Sns\SnsClient;
use Aws\Sns\MessageValidator\Message;
use Aws\Sns\MessageValidator\MessageValidator;
use Topic as TopicModel;

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
    protected $_snsClient;

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
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param MessageValidator $messageValidator
     * @param Config $config
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param TopicFactory $topic
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        MessageValidator $messageValidator,
        Config $config,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        TopicFactory $topicFactory
    ) {
        parent::__construct($context, $registry);
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
        if (!$this->_snsClient) {
            $this->_snsClient = SnsClient::factory($this->getConfig());
        }

        return $this->_snsClient;
    }

    /**
     * Check whether an object is Guzzle service resource model
     *
     * @param mixed $object
     * @return bool
     */
    public function isGuzzleResourceModel($object)
    {
        return gettype($object) == 'object'
            && $object instanceof \Guzzle\Service\Resource\Model;
    }

    /**
     * Get SNS client result
     *
     * @param mixed $result
     * @param string $param
     * @return string
     */
    public function getSnsResult($result, $param)
    {
        return $this->isGuzzleResourceModel($result)
            ? $result->get($param) : '';
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
     * Load topic model
     *
     * @param int|string|TopicModel $topic
     * @param int|string|null $id
     * @return mixed
     */
    public function loadTopicModel($topic = '', $id = null)
    {
        switch (gettype($topic)) {
            case 'integer':
            case 'string':
                $topic = $this->_topicFactory->create()->load($topic);
                break;
            case 'object':
                if ($topic instanceof TopicModel && !$topic->getTopicId() && $id) {
                    $topic->load($id);
                    break;
                }
            default:
                $topic = $this->_topicFactory->create();
                if ($id) {
                    $topic->load($id);
                }
        }

        return $topic;
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
                $this->confirmSubscription($data['Token'], $data['TopicArn']);
                break;
            case self::MESSAGE_TYPE_NOTIFICATION:
                $topic = $this->loadTopicModel($data['TopicArn']);
                if ($topic->getIsActive()) {
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

    /**
     * Create SNS topic
     *
     * @param string $name
     * @param bool $subscribe
     * @param int|string|TopicModel $topicModel
     * @return \Guzzle\Service\Resource\Model
     */
    public function createTopic($name, $subscribe = false, $topicModel = 0)
    {
        $result = $this->getSnsClient()->createTopic([
            'Name' => $name
        ]);

        $topicArn = $result->get('TopicArn');

        if ($subscribe && $topicArn) {
            $this->subscribe($topicArn);
        }

        if ($topicModel && $topicArn) {
            try {
                $topic = $this->loadTopicModel($topicModel);
                $topic->setName($name)
                    ->setArn($topicArn)
                    ->save();
            } catch (\Exception $e) {}
        }

        return $result;
    }

    /**
     * Delete SNS topic
     *
     * @param string $arn
     * @param string $subscriptionArn
     * @param int|string|TopicModel $topicModel
     * @return \Guzzle\Service\Resource\Model
     */
    public function deleteTopic($arn, $subscriptionArn = '', $topicModel = 0)
    {
        if ($subscriptionArn) {
            $this->unsubscribe($subscriptionArn);
        }

        $result = $this->getSnsClient()->deleteTopic([
            'TopicArn' => $arn
        ]);

        if ($topicModel) {
            try {
                if (gettype($topicModel) == 'object' && $topicModel->getTopicId()) {
                    //Do nothing.
                } else {
                    $topicModel = $arn;
                }

                $topic = $this->loadTopicModel($topicModel);
                $topic->delete();
            } catch (\Exception $e) {}
        }

        return $result;
    }

    /**
     * Subscribe to SNS topic
     *
     * @param string $topicArn
     * @return \Guzzle\Service\Resource\Model
     */
    public function subscribe($topicArn)
    {
        $result = $this->getSnsClient()->subscribe([
            'TopicArn' => $topicArn,
            'Protocol' => $this->getProtocol(),
            'Endpoint' => $this->getEndpoint()
        ]);

        return $result;
    }

    /**
     * Unsubscribe from SNS topic
     *
     * @param string $subscriptionArn
     * @param int|string|TopicModel $topicModel
     * @return \Guzzle\Service\Resource\Model
     */
    public function unsubscribe($subscriptionArn, $topicModel = 0)
    {
        $result = $this->getSnsClient()->unsubscribe([
            'SubscriptionArn' => $subscriptionArn
        ]);

        if ($topicModel) {
            try {
                $topicArn = substr($subscriptionArn, 0, strrpos($subscriptionArn, ':'));
                $topic = $this->loadTopicModel($topicArn);
                $topic->setSubscriptionArn('')->save();
            } catch (\Exception $e) {}
        }

        return $result;
    }

    /**
     * Confirm SNS topic subscription
     *
     * @param string $token
     * @param string $topicArn
     * @param string $authenticateOnUnsubscribe
     * @return \Guzzle\Service\Resource\Model
     */
    public function confirmSubscription($token, $topicArn, $authenticateOnUnsubscribe = 'true')
    {
        $result = $this->getSnsClient()->confirmSubscription([
            'Token'    => $token,
            'TopicArn' => $topicArn,
            'AuthenticateOnUnsubscribe' => $authenticateOnUnsubscribe
        ]);

        try {
            $topic = $this->loadTopicModel($topicArn);
            $subscriptionArn = $result->get('SubscriptionArn');

            if ($topic->getTopicId() && $subscriptionArn) {
                $topic->setSubscriptionArn($subscriptionArn)->save();
            }
        } catch (\Exception $e) {}

        return $result;
    }
}
