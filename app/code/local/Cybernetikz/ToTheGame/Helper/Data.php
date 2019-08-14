<?php
/**
*	Author		: 	Cybernetikz
*	Author Email:   info@cybernetikz.com
*	Blog		: 	http://blog.cybernetikz.com
*	Website		: 	http://www.cybernetikz.com
*/

class Cybernetikz_ToTheGame_Helper_Data extends Mage_Core_Helper_Abstract
{
    /* ToTheGame Settings */
	const XML_SUBSCRIBER_ID	= 'tothegame/setting/subscriberid';
	const XML_IMPORT_CACHE_TTL	= 'tothegame/setting/importcachettl';
	
	public function getSubscriberId($store = null)
    {
		return Mage::getStoreConfig(self::XML_SUBSCRIBER_ID, $store);
    }
	
	public function getImportCacheTTL($store = null)
    {
        return Mage::getStoreConfig(self::XML_IMPORT_CACHE_TTL, $store);
    }
	
	public function getExtensionName(){
		return "ToTheGame Feeder";
	}
	
	public function getCheckConfig(){
		
		$extension_name = $this->getExtensionName();
		
		if($this->getSubscriberId()=="" || $this->getImportCacheTTL()==""){
			Mage::getSingleton('adminhtml/session')->addError($extension_name." is not configured. You need to configure before use the extension.");			
			$this->setRedirectUrl('adminhtml/system_config/edit/section/tothegame'); //Redirect to Extension System Configuration
		}				
		
		return;
	}
	
	public function setRedirectUrl($redirect_url){
		$url = Mage::helper("adminhtml")->getUrl($redirect_url);
		$response = Mage::app()->getFrontController()->getResponse();
		$response->setRedirect($url);
		$response->sendResponse();
		exit;
	}
	
}