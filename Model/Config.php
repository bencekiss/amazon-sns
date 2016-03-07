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
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @param Config\System $systemConfig
     * @param \ShopGo\AmazonSns\Model\Config\File $fileConfig
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     */
    public function __construct(
        Config\System $systemConfig,
        \ShopGo\AmazonSns\Model\Config\File $fileConfig,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
        $this->systemConfig   = $systemConfig;
        $this->fileConfig     = $fileConfig;
        $this->_cacheTypeList = $cacheTypeList;
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
     * @param string $path
     * @param string $value
     * @return bool
     */
    public function setConfigData($path, $value)
    {
        $result = false;

        try {
            $path = explode('/', $path);

            $group = [
                $path[1] => [
                    'fields' => [
                        $path[2] => [
                            'value' => $value
                        ]
                    ]
                ]
            ];

            $configData = [
                'section' => $path[0],
                'website' => null,
                'store'   => null,
                'groups'  => $group
            ];

            $this->systemConfig->setConfigData($configData);
            $this->_cacheTypeList->cleanType('config');

            $result = true;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
        } catch (\Exception $e) {}

        return $result;
    }
}
