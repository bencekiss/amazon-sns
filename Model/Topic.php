<?php
/**
 * Copyright © 2015 ShopGo. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShopGo\AmazonSns\Model;

/**
 * Topic model
 */
class Topic extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var Sns
     */
    protected $_sns;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Sns $sns
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Sns $sns
    ) {
        parent::__construct($context, $registry);
        $this->_sns = $sns;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ShopGo\AmazonSns\Model\ResourceModel\Topic');
    }

    /**
     * Create SNS topic
     *
     * @param string $name
     * @param bool $subscribe
     * @return \Guzzle\Service\Resource\Model
     */
    public function createTopic($name, $subscribe = false)
    {
        $result = $this->_sns->getSnsClient()->createTopic([
            'Name' => $name
        ]);

        $topicArn = $result->get('TopicArn');

        if ($subscribe && $topicArn) {
            $this->subscribe($topicArn);
        }

        return $result;
    }

    /**
     * Delete SNS topic
     *
     * @param string $arn
     * @param string $subscriptionArn
     * @return \Guzzle\Service\Resource\Model
     */
    public function deleteTopic($arn, $subscriptionArn = '')
    {
        if ($subscriptionArn) {
            $this->unsubscribe($subscriptionArn);
        }

        $result = $this->_sns->deleteTopic([
            'TopicArn' => $arn
        ]);

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
        $result = $this->_sns->getSnsClient()->subscribe([
            'TopicArn' => $topicArn,
            'Protocol' => $this->_sns->getProtocol(),
            'Endpoint' => $this->_sns->getEndpoint()
        ]);

        return $result;
    }

    /**
     * Unsubscribe from SNS topic
     *
     * @param string $subscriptionArn
     * @return \Guzzle\Service\Resource\Model
     */
    public function unsubscribe($subscriptionArn)
    {
        $result = $this->_sns->unsubscribe([
            'SubscriptionArn' => $subscriptionArn
        ]);

        return $result;
    }

    /**
     * Confirm SNS topic subscription
     *
     * @param string $token
     * @param string $topicArn
     * @param bool $authenticateOnSubscribe
     * @return \Guzzle\Service\Resource\Model
     */
    public function confirmSubscription($token, $topicArn, $authenticateOnSubscribe = true)
    {
        $result = $this->_sns->getSnsClient()->confirmSubscription([
            'AuthenticateOnUnsubscribe' => $authenticateOnSubscribe,
            'Token'    => $token,
            'TopicArn' => $topicArn
        ]);

        return $result;
    }
}
