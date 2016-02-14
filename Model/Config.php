<?php
/**
 * Copyright © 2015 ShopGo. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShopGo\AmazonSns\Model;

/**
 * Config model
 */
class Config extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var Config\System
     */
    public $systemConfig;

    /**
     * @param Config\System $systemConfig
     */
    public function __construct(
        Config\System $systemConfig
    ) {
       $this->systemConfig = $systemConfig;
    }

    /**
     * Get config data value
     *
     * @param string $path
     * @return string
     */
    public function getConfigData($path)
    {
        return $this->systemConfig->getConfigData($path);
    }

    /**
     * Set config data
     *
     * @param array $configData
     */
    public function setConfigData($configData = [])
    {
        $this->systemConfig->setConfigData($configData);
    }
}
