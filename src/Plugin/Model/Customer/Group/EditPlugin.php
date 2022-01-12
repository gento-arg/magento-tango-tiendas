<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Plugin\Model\Customer\Group;

use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Block\Adminhtml\Group\Edit\Form;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Customer\Model\GroupRegistry;
use Magento\Framework\Data\Form as DataForm;
use Magento\Framework\Registry;

class EditPlugin
{
    public function __construct(
        GroupRegistry $groupRegistry,
        Registry $registry,
        GroupInterfaceFactory $groupDataFactory
    ) {
        $this->groupRegistry = $groupRegistry;
        $this->registry = $registry;
        $this->groupDataFactory = $groupDataFactory;
    }

    public function afterSetForm(
        Form $subject,
        Form $result,
        DataForm $form
    ) {
        $groupId = $this->registry->registry(RegistryConstants::CURRENT_GROUP_ID);
        /** @var \Magento\Customer\Api\Data\GroupInterface $customerGroup */
        $tangoId = null;
        if ($groupId === null) {
            $customerGroup = $this->groupDataFactory->create();
        } else {
            $customerGroup = $this->groupRegistry->retrieve($groupId);
            $tangoId = $customerGroup->getData('tango_id');
        }

        $form->getElement('base_fieldset')->addField(
            'tango_id',
            'text',
            [
                'name' => 'tango_id',
                'label' => __('Tango ID'),
                'title' => __('Tango ID'),
            ]
        );

        $form->addValues(['tango_id' => $tangoId]);
    }
}
