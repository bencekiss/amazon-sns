<?php
/**
 *
 * Copyright © 2015 ShopGo. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShopGo\AmazonSns\Controller\Adminhtml\Sns\Topic;

class Save extends \ShopGo\AmazonSns\Controller\Adminhtml\Sns\Topic
{
    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data  = $this->getRequest()->getPostValue();

        if ($data) {
            $topicId = ['topic_id' => $this->getRequest()->getParam('topic_id')];

            try {
                $model = $this->_objectManager->create('ShopGo\AmazonSns\Model\Topic');

                if (empty($topicId['topic_id'])) {
                    $isTopic = $model->getCollection()
                        ->addFieldToFilter('name', $data['name'])
                        ->getSize();

                    if ($isTopic) {
                        $this->messageManager->addError(
                            __('There is already an SNS topic with the same name. Please, use another one.')
                        );
                        $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData($data);

                        return $resultRedirect->setPath('*/*/edit');
                    }

                    $topic = $model->createTopic($data['name'], true);
                    $data['arn'] = $topic->get('TopicArn');

                    if (!$data['arn']) {
                        $this->messageManager->addError(__('Could not save the SNS topic.'));
                        $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData($data);

                        return $resultRedirect->setPath('*/*/edit');
                    }
                }

                $model->setData($data);
                $model->save();

                $this->messageManager->addSuccess(__('You saved the SNS topic.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData($data);

                return $resultRedirect->setPath('*/*/edit', $topicId);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
