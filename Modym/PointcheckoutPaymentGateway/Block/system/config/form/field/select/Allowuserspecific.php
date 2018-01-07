<?php

namespace Modym\PointcheckoutPaymentGateway\Block\System\Config\Form\Field\Select;

class Allowuserspecific extends \Magento\Framework\Data\Form\Element\Select
{
    /**
     * Add additional Javascript code
     *
     * @return string
     */
    public function getAfterElementHtml()
    {
        $elementId = $this->getHtmlId();
        $usergroupListId = $this->_getSpecificusergroupElementId();
        $useDefaultElementId = $usergroupListId . '_inherit';

        $elementJavaScript = <<<HTML
<script type="text/javascript">
//<![CDATA[
document.getElementById('{$elementId}').addEventListener('change', function(event) {
    var isusergroupSpecific = event.target.value == 1,
        specificCountriesElement = document.getElementById('{$usergroupListId}'),
        // 'Use Default' checkbox of the related county list UI element
        useDefaultElement = document.getElementById('{$useDefaultElementId}');

    if (isusergroupSpecific) {
        // enable related usergroup select only if its 'Use Default' checkbox is absent or is unchecked
        specificCountriesElement.disabled = useDefaultElement ? useDefaultElement.checked : false;
    } else {
        // disable related usergroup select if all countries are used
        specificCountriesElement.disabled = true;
    }
});
//]]>
</script>
HTML;

        return $elementJavaScript . parent::getAfterElementHtml();
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        if (!$this->getValue() || 1 != $this->getValue()) {
            $element = $this->getForm()->getElement($this->_getSpecificusergroupElementId());
            $element->setDisabled('disabled');
        }
        return parent::getHtml();
    }

    /**
     * @return string
     */
    protected function _getSpecificusergroupElementId()
    {
        return substr($this->getId(), 0, strrpos($this->getId(), 'allowuserspecific')) . 'specificusergroup';
    }
}
