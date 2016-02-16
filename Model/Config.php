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
     * @param string $path
     * @param string $value
     * @return boolean
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

            $result = true;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
        } catch (\Exception $e) {}

        return $result;
    }
}
