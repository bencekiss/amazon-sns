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
     * @var \ShopGo\AmazonSns\Model\TopicFactory
     */
    private $_topicFactory;

    /**
     * @param State $state
     * @param \ShopGo\AmazonSns\Model\TopicFactory $topicFactory
     */
    public function __construct(
        State $state,
        \ShopGo\AmazonSns\Model\SnsFactory $topicFactory
    ) {
        $this->_state = $state;
        $this->_topicFactory = $topicFactory;
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
        $result = $this->_topicFactory->create()->createTopic($topicName, true);

        $topicArn = $result->get('TopicArn');

        $result = $topicArn
            ? "Topic ({$topicArn}) has been successfully created! (Or, is already created!)"
            : "Could not create topic!";

        $output->writeln('<info>' . $result . '</info>');
    }
}
