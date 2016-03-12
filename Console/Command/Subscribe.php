<?php
/**
 * Copyright © 2015 ShopGo. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShopGo\AmazonSns\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;

/**
 * Subscribe to SNS topic command
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
    private $_fileConfig;

    /**
     * @param State $state
     * @param \ShopGo\AmazonSns\Model\SnsFactory $snsFactory
     * @param \ShopGo\AmazonSns\Model\TopicFactory $topicFactory
     * @param \ShopGo\AmazonSns\Model\Config\File $fileConfig
     */
    public function __construct(
        State $state,
        \ShopGo\AmazonSns\Model\SnsFactory $snsFactory,
        \ShopGo\AmazonSns\Model\TopicFactory $topicFactory,
        \ShopGo\AmazonSns\Model\Config\File $fileConfig
    ) {
        $this->_state = $state;
        $this->_snsFactory = $snsFactory;
        $this->_topicFactory = $topicFactory;
        $this->_fileConfig = $fileConfig;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('amazon-sns:subscribe')
            ->setDescription('Subscribe to SNS topic command')
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

        $subscriptionArn = '';
        $topic = $this->_topicFactory->create();
        $topicArn = '';
        $isTopicActive = false;

        $topicConfigData = [
            'topic' => [],
            'item' => ['attributes' => [$topicAttribute => $topicAttributeValue]]
        ];

        $configElement = $this->_fileConfig->getConfigElement($topicConfigData);

        if ($configElement) {
            $topicArn = $configElement->getAttribute('arn');
            $isTopicActive = $configElement->getAttribute('is_active');
        }

        if ($isTopicActive || $isTopicActive == '') {
            if (!$topicArn) {
                $topicArn = $topic->getCollection()
                    ->addFieldToSelect('arn')
                    ->addFieldToFilter($topicAttribute, $topicAttributeValue)
                    ->getItem();

                $topicArn = isset($topicArn[0]) ? $topicArn[0]->getArn() : '';
            }

            if ($topicArn) {
                $sns = $this->_snsFactory->create();
                $result = $sns->subscribe($topicArn);
                $subscriptionArn = $sns->getSnsResult($result, 'SubscriptionArn');
            }
        }

        $result = $subscriptionArn
            ? 'Topic has been subscribed to successfully!'
            : 'Could not subscribe to topic!';

        $output->writeln('<info>' . $result . '</info>');
    }
}
