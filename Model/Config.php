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
     * @var Config\File
     */
    public $fileConfig;

    /**
     * @param Config\System $systemConfig
     * @param Config\File $fileConfig
     */
    public function __construct(
        Config\System $systemConfig,
        Config\File $fileConfig
    ) {
        $this->systemConfig = $systemConfig;
        $this->fileConfig   = $fileConfig;
    }

    /**
     * Get config data value
     *
     * @param string $path
     * @return string
     */
    public function getConfigData($path)
    {
        $config = $this->fileConfig->getConfigElementValue($path);
        return !$config ? $this->systemConfig->getConfigData($path) : $config;
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
