<?php
/**
 *
 * Copyright © 2015 ShopGo. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShopGo\AmazonSns\Controller\Adminhtml\Sns\Topic;

class Delete extends \ShopGo\AmazonSns\Controller\Adminhtml\Sns\Topic
{
    /**
     * SNS Topic model
     *
     * @var \ShopGo\AmazonSns\Model\Topic
     */
    protected $_snsTopic;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \ShopGo\AmazonSns\Model\Topic $snsTopic
    ) {
        parent::__construct($context, $coreRegistry);
        $this->_snsTopic = $snsTopic;
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('topic_id');

        if ($id) {
            try {
                $model = $this->_objectManager->create('ShopGo\AmazonSns\Model\Topic');
                $model->load($id);

                $this->_snsTopic->deleteTopic($model->getArn(), $model->getSubscriptionArn());

                $model->delete();

                $this->messageManager->addSuccess(__('You deleted the SNS topic.'));

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());

                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->messageManager->addError(__('We cannot find an SNS topic to delete.'));

        return $resultRedirect->setPath('*/*/');
    }
}
