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
 * Create SNS topic command
 */
class CreateTopic extends Command
{
    /**
     * Topic name argument
     */
    const TOPIC_NAME_ARGUMENT = 'topic_name';

    /**
     * @var State
     */
    private $_state;

    /**
     * @var \ShopGo\AmazonSns\Model\SnsFactory
     */
    private $_snsFactory;

    /**
     * @param State $state
     * @param \ShopGo\AmazonSns\Model\SnsFactory $snsFactory
     */
    public function __construct(
        State $state,
        \ShopGo\AmazonSns\Model\SnsFactory $snsFactory
    ) {
        $this->_state = $state;
        $this->_snsFactory = $snsFactory;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('amazon-sns:create-topic')
            ->setDescription('Create SNS topic command')
            ->setDefinition([
                new InputArgument(
                    self::TOPIC_NAME_ARGUMENT,
                    InputArgument::REQUIRED,
                    'Topic name'
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

        $topicName = $input->getArgument(self::TOPIC_NAME_ARGUMENT);
        $sns = $this->_snsFactory->create();

        $result = $sns->createTopic($topicName, true, 1);
        $topicArn = $sns->getSnsResult($result, 'TopicArn');

        $result = $topicArn
            ? "Topic ({$topicArn}) has been successfully created!"
            : "Could not create topic!";

        $output->writeln('<info>' . $result . '</info>');
    }
}
