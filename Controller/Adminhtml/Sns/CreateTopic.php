<?php
/**
 *
 * Copyright © 2015 ShopGo. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShopGo\AmazonSns\Controller\Adminhtml\Sns;

use Magento\Framework\Controller\Result\JsonFactory;

class CreateTopic extends \Magento\Backend\App\Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \ShopGo\AmazonSns\Model\Sns
     */
    protected $_sns;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \ShopGo\AmazonSns\Model\Sns $sns
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \ShopGo\AmazonSns\Model\Sns $sns
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_sns = $sns;
    }

    /**
     * Create SNS topic
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result   = $this->_sns->createTopic();
        $topicArn = $result->get('TopicArn');

        if ($topicArn) {
            $result = [
                'status'  => 1,
                'message' => __('SNS Topic has been successfully created. (Or, is already created)')
            ];
        } else {
            $result   = [
                'status'  => 0,
                'message' => __('Could not create SNS topic.')
            ];
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }
}
