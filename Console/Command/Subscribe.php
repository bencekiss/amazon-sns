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
        $this->setName('amazon-sns:subscribe')
            ->setDescription('Subscribe to SNS topic command');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_state->setAreaCode('adminhtml');

        $result = $this->_snsFactory->create()->subscribe();

        $subscriptionArn = $result->get('SubscriptionArn');

        $result = $subscriptionArn
            ? "Topic has been subscribed to successfully! ({$subscriptionArn})"
            : "Could not subscribe to topic!";

        $output->writeln('<info>' . $result . '</info>');
    }
}
