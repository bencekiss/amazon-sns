<?php
/**
 * Copyright © 2015 ShopGo. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShopGo\AmazonSns\Console\Command\Topic;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;

/**
 * Subscribe to an SNS topic command
 */
class Subscribe extends Command
{
    /**
     * Topic attribute argument
     */
    const TOPIC_ATTRIBUTE_ARGUMENT = 'topic_attribute';

    /**
     * Topic attribute value argument
     */
    const TOPIC_ATTRIBUTE_VALUE_ARGUMENT = 'topic_attribute_value';

    /**
     * Endpoint protocol argument
     */
    const PROTOCOL_ARGUMENT = 'protocol';

    /**
     * Endpoint argument
     */
    const ENDPOINT_ARGUMENT = 'endpoint';

    /**
     * @var State
     */
    private $_state;

    /**
     * @var \ShopGo\AmazonSns\Model\SnsFactory
     */
    private $_snsFactory;

    /**
     * @var \ShopGo\AmazonSns\Model\TopicFactory
     */
    private $_topicFactory;

    /**
     * @var \Magento\Framework\Config\ReaderInterface
     */
    private $_topicFileConfig;

    /**
     * @param State $state
     * @param \ShopGo\AmazonSns\Model\SnsFactory $snsFactory
     * @param \ShopGo\AmazonSns\Model\TopicFactory $topicFactory
     * @param \Magento\Framework\Config\ReaderInterface $topicFileConfig
     */
    public function __construct(
        State $state,
        \ShopGo\AmazonSns\Model\SnsFactory $snsFactory,
        \ShopGo\AmazonSns\Model\TopicFactory $topicFactory,
        \Magento\Framework\Config\ReaderInterface $topicFileConfig
    ) {
        $this->_state = $state;
        $this->_snsFactory = $snsFactory;
        $this->_topicFactory = $topicFactory;
        $this->_topicFileConfig = $topicFileConfig;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('amazon-sns:topic:subscribe')
            ->setDescription('Subscribe to an SNS topic command')
            ->setDefinition([
                new InputArgument(
                    self::TOPIC_ATTRIBUTE_ARGUMENT,
                    InputArgument::REQUIRED,
                    'Topic attribute'
                ),
                new InputArgument(
                    self::TOPIC_ATTRIBUTE_VALUE_ARGUMENT,
                    InputArgument::REQUIRED,
                    'Topic attribute value'
                ),
                new InputArgument(
                    self::PROTOCOL_ARGUMENT,
                    InputArgument::OPTIONAL,
                    'Endpoint protocol'
                ),
                new InputArgument(
                    self::ENDPOINT_ARGUMENT,
                    InputArgument::OPTIONAL,
                    'Endpoint'
                )
            ]);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_state->setAreaCode('adminhtml');

        $topicAttribute = $input->getArgument(self::TOPIC_ATTRIBUTE_ARGUMENT);
        $topicAttributeValue = $input->getArgument(self::TOPIC_ATTRIBUTE_VALUE_ARGUMENT);
        $protocol = $input->getArgument(self::PROTOCOL_ARGUMENT);
        $endpoint = $input->getArgument(self::ENDPOINT_ARGUMENT);

        $subscriptionArn = '';
        $topic = $this->_topicFactory->create();
        $topicArn = '';
        $isTopicActive = false;

        $topicConfigData = [
            'topic' => [],
            'item' => ['attributes' => [$topicAttribute => $topicAttributeValue]]
        ];

        $configElement = $this->_topicFileConfig->getConfigElement($topicConfigData)->item(0);

        if ($configElement) {
            $topicArn = $configElement->getAttribute('arn');
            $isTopicActive = $configElement->getAttribute('is_active');
        }

        if ($isTopicActive || $isTopicActive == '') {
            if (!$topicArn) {
                $topicArn = $topic->getCollection()
                    ->addFieldToSelect('arn')
                    ->addFieldToFilter($topicAttribute, $topicAttributeValue)
                    ->getItems();

                $topicArn = reset($topicArn);
                $topicArn = $topicArn ? $topicArn->getArn() : '';

                if ($topicArn) {
                    $sns = $this->_snsFactory->create();
                    $result = $sns->subscribe($topicArn, $protocol, $endpoint);
                    $subscriptionArn = $sns->getHelper()->getAwsClientResult($result, 'SubscriptionArn');
                }
            }
        }

        $result = $subscriptionArn
            ? 'Topic has been subscribed successfully!'
            : 'Could not subscribe to topic!';

        $output->writeln('<info>' . $result . '</info>');
    }
}
