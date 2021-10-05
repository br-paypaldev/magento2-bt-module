<?php
declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;

class CustomerAttributes implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * CustomerAttributes constructor.
     */
    public function __construct
    (
        CollectionFactory $attributeCollectionFactory
    )
    {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }


    /**
     * Return array of options from customer attributes
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['label' => __('-- Select --'), 'value' => ''];

        $model = $this->attributeCollectionFactory->create();
        $model->setEntityTypeFilter(1);

        foreach ($model->getData() as $item) {
            if ($item['frontend_label']) {
                $options[] = [
                    'label' => __('%1 (Customer)', $item['frontend_label']),
                    'value' => 'customer_' . $item['attribute_code']
                ];
            }
        }

        $maxStreetLines = 4;
        foreach(range(1, $maxStreetLines) as $line){
            $options[] = ['label' => __('Street Line %1 (Billing Address)', $line), 'value' => 'address_street_' . (int)$line];
        }

        $model = $this->attributeCollectionFactory->create();
        $model->setEntityTypeFilter(2);

        foreach ($model->getData() as $item) {
            if ($item['frontend_label'] && $item['attribute_code'] != 'street') {
                $options[] = [
                    'label' => __('%1 (Billing Address)', $item['frontend_label']),
                    'value' => 'address_' . $item['attribute_code']
                ];
            }
        }

        return $options;
    }
}
