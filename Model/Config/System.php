<?php
/**
 * Copyright © 2015 ShopGo. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShopGo\AmazonSns\Model\Config;

/**
 * System config model
 */
class System extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Config\Model\Config\Factory
     */
    protected $_configFactory;

    /**
     * @param \Magento\Config\Model\Config\Factory $configFactory
     */
    public function __construct(\Magento\Config\Model\Config\Factory $configFactory)
    {
        $this->_configFactory = $configFactory;
    }

    /**
     * Get config model
     *
     * @param array $configData
     * @return \Magento\Config\Model\Config
     */
    protected function _getConfigModel($configData = [])
    {
        /** @var \Magento\Config\Model\Config $configModel  */
        $configModel = $this->_configFactory->create(['data' => $configData]);
        return $configModel;
    }

    /**
     * Get config data value
     *
     * @param string $path
     * @return string
     */
    public function getConfigData($path)
    {
        return $this->_getConfigModel()->getConfigDataValue($path);
    }

    /**
     * Set config data
     *
     * @param array $configData
     */
    public function setConfigData($configData = [])
    {
        $this->_getConfigModel($configData)->save();
    }
}
