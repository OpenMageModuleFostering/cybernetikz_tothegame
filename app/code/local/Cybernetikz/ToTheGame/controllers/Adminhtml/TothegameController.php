<?php
/**
*	Author		: 	Cybernetikz
*	Author Email:   info@cybernetikz.com
*	Blog		: 	http://blog.cybernetikz.com
*	Website		: 	http://www.cybernetikz.com
*/

class Cybernetikz_ToTheGame_Adminhtml_TothegameController extends Mage_Adminhtml_Controller_Action
{
    
	protected $_startTime;
	protected $gameCount = 0;
	
	/**
     * Init actions
     *
     * @return Mage_Adminhtml_ToTheGameController
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('catalog/system_tothegame')
            ->_addBreadcrumb(
                Mage::helper('catalog')->__('Catalog'),
                Mage::helper('catalog')->__('Catalog'))
            ->_addBreadcrumb(
                Mage::helper('cybernetikz_tothegame')->__('ToTheGame Feed Import'),
                Mage::helper('cybernetikz_tothegame')->__('ToTheGame Import'))
        ;
        return $this;
    }
	
    /*
		Game Import Manage
	*/
	public function gameimportAction()
    {
        Mage::helper('cybernetikz_tothegame')->getCheckConfig(); // Check Configuration
		
		$this->_title($this->__('Catalog'))->_title($this->__('ToTheGame Feed Import'));
		
		$this->_initAction()->renderLayout();
    }
	
	/*
		Single ToTheGame Feed Import
	*/
	public function singlegameimportAction()
    {
		
		$this->_startTime = microtime(true);
		
		set_time_limit(0);

		$req = array('game_id' => 'array');
		$sent = array();
		$error = false;
		
		if (!$this->_ValidateData($_GET, $req, $sent) || $_GET['game_id'][0] == ""){
			echo "<span style='color:red;'>Error: You need to provide ToTheGame Game ID for the game that needs to be imported.</span><br>";
			$error = true;
		}

		if(!$error){
			$gamesids=explode(",",$sent['game_id'][0]);
			//print_r($gamesids);exit;
			
			foreach($gamesids as $gameid){
				echo 'Getting more info for GameId: ', $gameid, "<br>";
				$this->importGame($gameid);
				++$this->gameCount;
			}
		}
		
		$this->_PhpShutdown();
    }
	
	/*
		Import Single Game Process
	*/
	protected function importGame($gameId) {
		
		$SUBSCRIBER_ID = Mage::helper('cybernetikz_tothegame')->getSubscriberId(); // Subscriber Id
		$gameProducttId = "";
		
		$gameUrl = 'http://export2.tothegame.com/gamefeeder/v3/gamelookup.aspx?subscriberid='.$SUBSCRIBER_ID.'&gameid='.$gameId;
		
		$xml = new DOMDocument(); // Create new DOM object
		
		$_product = Mage::getModel('catalog/product')->loadByAttribute('game_id', $gameId);
		//print_r($_product);exit;
		
		if(!empty($_product)){
			$gameProducttId = (int)$_product->getId();
		}		
		//echo $gameProducttId; exit;
				
		try {
			
			// Load XML Feed
			echo "-- Loading game data from publisher<br>";
			$xml->load($gameUrl);
			$xml->saveXML();
			
			// Check Product Availability
			if(!$this->_getElementValue($xml, 'OfficialTitle')){
				echo "<span style='color:red;'>--Error: Game Id not available. Please check Game Id and try again later.</span><br>";
				return;
			}
			
			$_productData = array(
				'name' 				=>	$this->_getElementValue($xml, 'OfficialTitle'),
				'description'		=>	'',												// Factsheet in the XML, description of the game in english
				'GameId' 			=>	$gameId,
				'LastUpdated'		=>	$this->_getElementValue($xml, 'LastUpdated'),
				'EAN'				=> 	array(),
				'PlatformId'		=> 	$this->_getElementValue($xml, 'PlatformId'),
				'Platform'			=> 	$this->_getElementValue($xml, 'Platform'),
				'LogoUrl'			=> 	$this->_getElementValue($xml, 'LogoUrl'),
				'LocalizedTitle' 	=> 	'',
				'InGameLanguage' 	=> 	array(),
				'Releases' 			=> 	array(),
				'Packshots' 		=> 	array(),
				'PegiRating' 		=> 	$this->_getElementValue($xml, 'PegiRating'),
				'PegiRatingId' 		=> 	$this->_getElementValue($xml, 'PegiRatingId'),
				'Publisher' 		=> 	array(),
				'DeveloperId' 		=> 	$this->_getElementValue($xml, 'DeveloperId'),
				'DeveloperName' 	=> 	$this->_getElementValue($xml, 'DeveloperName'),
				'DeveloperHomepage' => 	$this->_getElementValue($xml, 'DeveloperHomepage'),
				'MainGame' 			=> 	$this->_getElementValue($xml, 'MainGame'),
				'AlsoAvailableOn' 	=> 	array(),											// expansion pack IDs
				'Editions' 			=> 	array(),											// IDs of the game editions
				'Videos' 			=> 	array(),
				'Screenshots' 		=> 	array(),
				'Keywords' 			=> 	$this->_getElementValue($xml, 'Keywords'),
				'child_category' 	=> 	$this->_getElementValue($xml, 'Category')
				
			);
		
			//EANs
			$temp = $xml->getElementsByTagName('EAN');
	
			if ($temp->length) {
				foreach ($temp->item(0)->getElementsByTagName('string') as $i => $el)
					$_productData['EAN'][] = $el->nodeValue;
			}
	
			//LocalizedTitle
			$temp = $xml->getElementsByTagName('LocalizedTitles');
	
			if ($temp->length) {
				foreach ($temp->item(0)->childNodes as $i => $el) {
					if ($el->nodeType == XML_ELEMENT_NODE && strtolower($el->getElementsByTagName('Territory')->item(0)->nodeValue) == 'uk') {
						$_productData['LocalizedTitle'] = $el->getElementsByTagName('Title')->item(0)->nodeValue;
						break;
					}
				}
			}
	
			//InGameLanguage
			$temp = $xml->getElementsByTagName('InGameLanguage');
	
			if ($temp->length) {
				foreach ($temp->item(0)->getElementsByTagName('string') as $i => $el)
					$_productData['InGameLanguage'][] = $el->nodeValue;
			}
	
			//Releases
			$temp = $xml->getElementsByTagName('Releases');
	
			if ($temp->length) {
				foreach ($temp->item(0)->getElementsByTagName('Release') as $i => $el) {
					if (strtolower($el->getElementsByTagName('Territory')->item(0)->nodeValue) == 'uk') {
						$_productData['Releases'] = array(
							'ReleaseStatus' => $el->getElementsByTagName('ReleaseStatus')->item(0)->nodeValue,
							'ReleaseDate' => $el->getElementsByTagName('ReleaseDate')->item(0)->nodeValue
						);
						$_productData['ReleaseDate'] = $_productData['Releases']['ReleaseDate'];
						break;
					}
				}
			}
	
			//PackShots
			$temp = $xml->getElementsByTagName('Packshots');
	
			if ($temp->length) {
					
				foreach ($temp->item(0)->getElementsByTagName('Packshot') as $i => $el) {
					if (strtolower($el->getElementsByTagName('Territory')->item(0)->nodeValue) == 'uk') {
						
						$_productData['Packshots']['UrlMedium'] = $el->getElementsByTagName('UrlMedium')->item(0)->nodeValue;
						
						$elem = $el->getElementsByTagName('UrlHiRez');	
						if ($elem->length) {
							$_productData['Packshots']['UrlHiRez'] = $elem->item(0)->nodeValue;
						}
	
						$elem = $el->getElementsByTagName('UrlThumb');	
						if ($elem->length) {
							$_productData['Packshots']['UrlThumb'] = $elem->item(0)->nodeValue;
						}
	
						break;
					}
				}
			}
	
			//Factsheets
			$temp = $xml->getElementsByTagName('Factsheets');
	
			if ($temp->length) {
				foreach ($temp->item(0)->getElementsByTagName('LocalizedDescription') as $i => $el) {
					if (strtolower($el->getElementsByTagName('Territory')->item(0)->nodeValue) == 'uk') {
						$_productData['description'] = $el->getElementsByTagName('Description')->item(0)->nodeValue;
						break;
					}
				}
			}
	
			//Publisher
			$temp = $xml->getElementsByTagName('Publishers');
	
			if ($temp->length) {
				foreach ($temp->item(0)->getElementsByTagName('Publisher') as $i => $el) {
					if (strtolower($el->getElementsByTagName('Territory')->item(0)->nodeValue) == 'uk') {
						$_productData['Publisher'] = array(
							'PublisherId' => $el->getElementsByTagName('PublisherId')->item(0)->nodeValue,
							'PublisherName' => $el->getElementsByTagName('PublisherName')->item(0)->nodeValue,
							'PublisherHomepage' => $el->getElementsByTagName('PublisherHomepage')->item(0)->nodeValue
						);
						break;
					}
				}
			}
	
			//AlsoAvailableOn
			//TODO maybe find and prepare the products that are related for easier fetching later?
			$temp = $xml->getElementsByTagName('AlsoAvailableOn');
	
			if ($temp->length) {
				foreach ($temp->item(0)->getElementsByTagName('int') as $i => $el)
					$_productData['AlsoAvailableOn'][] = $el->nodeValue;
			}
	
			//Editions
			$temp = $xml->getElementsByTagName('Editions');
	
			if ($temp->length) {
				foreach ($temp->item(0)->getElementsByTagName('int') as $i => $el)
					$_productData['Editions'][] = $el->nodeValue;
			}
	
			//Videos
			$temp = $xml->getElementsByTagName('Videos');
	
			if ($temp->length) {
					
				foreach ($temp->item(0)->getElementsByTagName('Video') as $i => $el) {
					$v = array(
						'VideoId' => $el->getElementsByTagName('VideoId')->item(0)->nodeValue,
						'Url' => $el->getElementsByTagName('Url')->item(0)->nodeValue,
						'Title' => $el->getElementsByTagName('Title')->item(0)->nodeValue,
						'AgeWarning' => $el->getElementsByTagName('AgeWarning')->item(0)->nodeValue,
						'Timestamp' => $el->getElementsByTagName('Timestamp')->item(0)->nodeValue,
						'PreviewFrameUrl' => $el->getElementsByTagName('PreviewFrameUrl')->item(0)->nodeValue
					);
						
					$_productData['Videos'][] = $v;
				}
			}
	
			//Screenshots
			$temp = $xml->getElementsByTagName('Screenshots');
	
			if ($temp->length) {
					
				foreach ($temp->item(0)->getElementsByTagName('Screenshot') as $i => $el) {
					$s = array(
						'UrlLarge' => $el->getElementsByTagName('UrlLarge')->item(0)->nodeValue,
						'UrlThumb' => $el->getElementsByTagName('UrlThumb')->item(0)->nodeValue
					);
						
					$_productData['Screenshots'][] = $s;
				}
			}
			
			/*------ Start Edition Game Info -------*/
			$edition_temp = $xml->getElementsByTagName('Edition');
			if($edition_temp->length){
				$edition_temp = $edition_temp->item(0);
				
				// Update Edition Title
				$temp = $edition_temp->getElementsByTagName('Title');
				
				if ($temp->length) {
					$_productData['name']=$this->_getElementValue($edition_temp, 'Title');
				}
				
				// Update Edition Releases
				$temp = $edition_temp->getElementsByTagName('Releases');
		
				if ($temp->length) {
					foreach ($temp->item(0)->getElementsByTagName('Release') as $i => $el) {
						if (strtolower($el->getElementsByTagName('Territory')->item(0)->nodeValue) == 'uk') {
							$_productData['Releases'] = array(
								'ReleaseStatus' => $el->getElementsByTagName('ReleaseStatus')->item(0)->nodeValue,
								'ReleaseDate' => $el->getElementsByTagName('ReleaseDate')->item(0)->nodeValue
							);
							$_productData['ReleaseDate'] = $_productData['Releases']['ReleaseDate'];
							break;
						}
					}
				}
				
				//Update Edition Description
				$temp = $edition_temp->getElementsByTagName('Extra');
				
				if ($temp->length) {
					foreach ($temp->item(0)->getElementsByTagName('LocalizedDescription') as $i => $el) {
						if (strtolower($el->getElementsByTagName('Territory')->item(0)->nodeValue) == 'uk') {
							$_productData['description'] = $el->getElementsByTagName('Description')->item(0)->nodeValue;
							break;
						}
					}
				}
				
				//Update Edition Publisher
				$temp = $edition_temp->getElementsByTagName('Publishers');	
				if ($temp->length) {
					foreach ($temp->item(0)->getElementsByTagName('Publisher') as $i => $el) {
						if (strtolower($el->getElementsByTagName('Territory')->item(0)->nodeValue) == 'uk') {
							$_productData['Publisher'] = array(
								'PublisherId' => $el->getElementsByTagName('PublisherId')->item(0)->nodeValue,
								'PublisherName' => $el->getElementsByTagName('PublisherName')->item(0)->nodeValue,
								'PublisherHomepage' => $el->getElementsByTagName('PublisherHomepage')->item(0)->nodeValue
							);
							break;
						}
					}
				}
				
				//Update Edition PackShots
				$temp = $edition_temp->getElementsByTagName('Packshots');
		
				if ($temp->length) {
							
					foreach ($temp->item(0)->getElementsByTagName('Packshot') as $i => $el) {
						if (strtolower($el->getElementsByTagName('Territory')->item(0)->nodeValue) == 'uk') {
							
							$_productData['Packshots']['UrlMedium'] = $el->getElementsByTagName('UrlMedium')->item(0)->nodeValue;
									
							$elem = $el->getElementsByTagName('UrlHiRez');		
							if ($elem->length) {
								$_productData['Packshots']['UrlHiRez'] = $elem->item(0)->nodeValue;
							}
		
							$elem = $el->getElementsByTagName('UrlThumb');		
							if ($elem->length) {
								$_productData['Packshots']['UrlThumb'] = $elem->item(0)->nodeValue;
							}
		
							break;
						}
					}
				}		
				
			}
						
			//print_r($_productData); exit;
			
			$genre_val = $this->_addAttributeValue("genre",$_productData['child_category']);
			
			if(count($_productData['Publisher'])>0){
				$publisher_val = $this->_addAttributeValue("publishers",$_productData['Publisher']['PublisherName']);
			}
			$shortDescription = substr(strip_tags($_productData['description']),0,200);

			$parts= explode(" ",$_productData['PegiRating']);
			$age_val = NULL;
			if(is_int($parts[1])){
				$age_val = $this->_addAttributeValue("age",$parts[1]);
			}
			
			$_productData['EAN'] = implode(",",$_productData['EAN']);
			$_productData['AlsoAvailableOn'] = implode(',',$_productData['AlsoAvailableOn']);
			
			if($_productData['LocalizedTitle'])
				$_productData['LocalizedTitle'] = serialize($_productData['LocalizedTitle']);
			
			if($_productData['Releases'])
				$_productData['Releases'] = serialize($_productData['Releases']);
			
			if($_productData['Publisher'])
				$_productData['Publisher'] = serialize($_productData['Publisher']);	
			
			if($_productData['Editions'])
				$_productData['Editions'] = serialize($_productData['Editions']);
			
			if($_productData['Videos'])
				$_productData['Videos'] = serialize($_productData['Videos']);

			if($_productData['Keywords'])
				$_productData['Keywords'] = implode(',',$_productData['Keywords']);
			
			
			// Start Add Or Update Product
			if ($gameProducttId) {
				
				// Start Update Product Here
				echo "-- updating ", $_productData['name'], " --productdata<br>";
				$_product = Mage::getModel('catalog/product')->load($gameProducttId);
				
				/*
					Start Remove Old and Add New Images
				*/
				$mediaApi = Mage::getModel("catalog/product_attribute_media_api");
				$mediaApiItems = $mediaApi->items($_product->getId());
				foreach($mediaApiItems as $item) {
					$datatemp=$mediaApi->remove($_product->getId(), $item['file']);
					echo "-- rem. ", $item['file'], " from Local Server<br>";
				}
				$_product->save(); //before adding need to save product				
				$_product = Mage::getModel('catalog/product')->load($gameProducttId);
				
				// Assign Packsthot Image as Product Images
				foreach($_productData['Packshots'] as $type => $PackshotImage){
					// Thumb image skip
					if($type == "UrlThumb"){
						continue;
					}else{
						$importDir = Mage::getBaseDir('media') . DS . 'import';
						$image_name = $gameId."-packshot"."-".$type.".jpg";
						$packImageUrl = $importDir. DS .$image_name;
						// Copy Image from totheGame server to local server
						if(copy($PackshotImage, $packImageUrl)){
							echo "-- upl. ", $PackshotImage, " to Local Server<br>";
							// Add three image sizes to media gallery
							$_product->addImageToMediaGallery($packImageUrl, array('image','thumbnail','small_image'), false, false); // Assigning packshot image, thumb and small image to media gallery
							break;
							
							unlink($packImageUrl); // Remove Image after add to product image
						}
					}
				}
				
				// Assign Screenshot Image as Product Images
				foreach($_productData['Screenshots'] as $type => $screenshotImages){
					$screenshotImage = $screenshotImages['UrlLarge'];
					$importDir = Mage::getBaseDir('media') . DS . 'import';
					$image_name = $gameId."-screenshot"."-".$type.".jpg";
					$screenshotImageUrl = $importDir. DS .$image_name;
					// Copy Image from totheGame server to local server
					if(copy($screenshotImage, $screenshotImageUrl)){
						echo "-- upl. ", $screenshotImage, " to Local Server<br>";
						// Add three image sizes to media gallery
						$_product->addImageToMediaGallery($screenshotImageUrl, false, false, false); // Assigning screenshot image to media gallery
						unlink($screenshotImageUrl); // Remove Image after add to product image
					}
				}
				
				/*
					End Remove Old and Add New Images
				*/
				
				$_product->setName($_productData['name']);
				$_product->setDescription($_productData['description']);
				$_product->setShortDescription($shortDescription);
				$_product->setGenre($genre_val);				
				if(count($_productData['Publisher'])>0){
					$_product->setPublishers($publisher_val);
				}
				if($age_val)
					$_product->setAge($age_val);
				
				$_product->setGameId($gameId);
				$_product->setLastUpdated($_productData['LastUpdated']);
				$_product->setEan($_productData['EAN']);
				$_product->setPlatformId($_productData['PlatformId']);
				$_product->setPlatform($_productData['Platform']);
				$_product->setLogoUrl($_productData['LogoUrl']);
				$_product->setLocalizedTitle($_productData['LocalizedTitle']);
				$_product->setReleases($_productData['Releases']);
				$_product->setPegiRating($_productData['PegiRating']);
				$_product->setPegiRatingId($_productData['PegiRatingId']);
				$_product->setPublisher($_productData['Publisher']);
				$_product->setDeveloperId($_productData['DeveloperId']);
				$_product->setDeveloperName($_productData['DeveloperName']);
				$_product->setDeveloperHomepage($_productData['DeveloperHomepage']);
				$_product->setMainGame($_productData['MainGame']);
				$_product->setAlsoAvailableOn($_productData['AlsoAvailableOn']);
				$_product->setEditions($_productData['Editions']);
				$_product->setVideos($_productData['Videos']);
				$_product->setKeywords($_productData['Keywords']);
								
				try {
					$_product->save();
					echo "-- Updated --<br>";
				}
				catch (Exception $e) {
					echo "--! update failed<br>";
					return;
				}
				
			} else {
				
				// Start Add Product Here
				echo '-- importing ', $_productData['name'], " --productdata<br>";
				
				$websiteids="";
				foreach (Mage::app()->getWebsites() as $website) {
					$websiteids[]=$website->getWebsiteId();
				}
				
				$storeids="";
				$stores=Mage::getModel('core/store')->getCollection()->load()->getAllIds();
				foreach($stores as $estoreid){
					$storeids[]=$estoreid;
				}
				
				$product = new Mage_Catalog_Model_Product();
				
				$product->setSku($gameId);
				$product->setTypeId('simple');
				$product->setPrice(0.00); 
				$product->setWeight(0.00);
				$product->setAttributeSetId(4);
				$product->setWebsiteIDs($websiteids);
				$product->setStatus(2);
				$product->setTaxClassId(0);
				$product->setStockData(array(
					'manage_stock'=>1,
					'use_config_manage_stock'=>0,
					'qty' => 0,
					'is_in_stock'=>0
				));
								
				$product->setName($_productData['name']);
				$product->setDescription($_productData['description']);
				$product->setShortDescription($shortDescription);

				$product->setGenre($genre_val);
				if(count($_productData['Publisher'])>0){
					$product->setPublishers($publisher_val);
				}
				if($age_val)
					$_product->setAge($age_val);
				
				$product->setGameId($gameId);
				$product->setLastUpdated($_productData['LastUpdated']);
				$product->setEan($_productData['EAN']);
				$product->setPlatformId($_productData['PlatformId']);
				$product->setPlatform($_productData['Platform']);
				$product->setLogoUrl($_productData['LogoUrl']);
				$product->setLocalizedTitle($_productData['LocalizedTitle']);
				$product->setReleases($_productData['Releases']);
				$product->setPegiRating($_productData['PegiRating']);
				$product->setPegiRatingId($_productData['PegiRatingId']);
				$product->setPublisher($_productData['Publisher']);
				$product->setDeveloperId($_productData['DeveloperId']);
				$product->setDeveloperName($_productData['DeveloperName']);
				$product->setDeveloperHomepage($_productData['DeveloperHomepage']);
				$product->setMainGame($_productData['MainGame']);
				$product->setAlsoAvailableOn($_productData['AlsoAvailableOn']);
				$product->setEditions($_productData['Editions']);
				$product->setVideos($_productData['Videos']);
				$product->setKeywords($_productData['Keywords']);
								
				// Assign Packsthot Image as Product Images
				foreach($_productData['Packshots'] as $type => $PackshotImage){
					// Thumb image skip
					if($type == "UrlThumb"){
						continue;
					}else{
						$importDir = Mage::getBaseDir('media') . DS . 'import';
						$image_name = $gameId."-packshot"."-".$type.".jpg";
						$packImageUrl = $importDir. DS .$image_name;
						// Copy Image from totheGame server to local server
						if(copy($PackshotImage, $packImageUrl)){
							echo "-- upl. ", $PackshotImage, " to Local Server<br>";
							// Add three image sizes to media gallery
							$product->addImageToMediaGallery($packImageUrl, array('image','thumbnail','small_image'), false, false); // Assigning packshot image, thumb and small image to media gallery
							break;
							
							unlink($packImageUrl); // Remove Image after add to product image
						}
					}
				}
				
				// Assign Screenshot Image as Product Images
				foreach($_productData['Screenshots'] as $type => $screenshotImages){
					$screenshotImage = $screenshotImages['UrlLarge'];
					$importDir = Mage::getBaseDir('media') . DS . 'import';
					$image_name = $gameId."-screenshot"."-".$type.".jpg";
					$screenshotImageUrl = $importDir. DS .$image_name;
					// Copy Image from totheGame server to local server
					if(copy($screenshotImage, $screenshotImageUrl)){
						echo "-- upl. ", $screenshotImage, " to Local Server<br>";
						// Add three image sizes to media gallery
						$product->addImageToMediaGallery($screenshotImageUrl, false, false, false); // Assigning screenshot image to media gallery
						
						unlink($screenshotImageUrl); // Remove Image after add to product image
					}
				}
				
				try {
					$product->save();
					echo "-- Product success added<br>";
				}
				catch (Exception $e) {
					echo "--! import failed<br>";
					return;
				}
			}
	
		} catch (Exception $e) {
			echo '--! exception: ', $e->getMessage(), "<br>";
		}
	
		$xml = null;
	}
	
	protected function _getElementValue(&$el, $nodeName) {
		$ch = $el->getElementsByTagName($nodeName);
	
		if ($ch->length == 0)
			return '';
	
		return $ch->item(0)->nodeValue;
	}
	
	/*
		PHP Shutdown and calculate
	*/
	protected function _PhpShutdown() {
		echo "<br>Import of ", $this->gameCount, ' items finished in ', (microtime(true)-$this->_startTime), 's; memory usage peak ', $this->_FormatBytes(memory_get_usage(true)), "<br>";
	}
	
	/*
		Calculate Usages Memory 
	*/
	protected function _FormatBytes($size){
		$units = array(' B', ' KiB', ' MiB', ' GiB', ' TiB', ' PiB');
	
		for ($i = 0; $size >= 1024 && $i < 6; $i++)
			$size /= 1024;
	
		return round($size, 2).$units[$i];
	}

	/* Validation Data */
	protected function _ValidateData(&$data, $req, &$values, $escape = true) {
		$ok = TRUE;
	
		foreach ($req as $fieldName => $dataType) {
			if (!isset($data[$fieldName])) {
				$values[$fieldName] = '';
				$data[$fieldName] = null;
				$ok = FALSE;
			}
	
			switch ($dataType) {
			case 'plain_text':
				$values[$fieldName] = strip_tags(htmlspecialchars($data[$fieldName]));
	
				if ($values[$fieldName] === '')
					$ok = FALSE;
				// handle character escapipng
				if ($escape)
					$values[$fieldName] = get_magic_quotes_gpc() ? $values[$fieldName] : addslashes($values[$fieldName]);
				else
					$values[$fieldName] = stripslashes($values[$fieldName]);
	
				break;
	
			case 'rich_text':
				$values[$fieldName] = $data[$fieldName];
				if ($values[$fieldName] === '')
					$ok = FALSE;
				// handle character escapipng
				if ($escape)
					$values[$fieldName] = get_magic_quotes_gpc() ? $values[$fieldName] : addslashes($values[$fieldName]);
				else
					$values[$fieldName] = stripslashes($values[$fieldName]);
	
				break;
	
			case 'integer':
				if (!is_numeric($data[$fieldName])) {
					$ok = FALSE;
					$values[$fieldName] = '';
				} else
					$values[$fieldName] = (int)$data[$fieldName];
	
				break;
	
			case 'float':
				if (!is_numeric($data[$fieldName])) {
					$ok = FALSE;
					$values[$fieldName] = '';
				} else
					$values[$fieldName] = (float)$data[$fieldName];
	
				break;
	
			case 'array':
				if (!is_array($data[$fieldName])) {
					$ok = FALSE;
					$values[$fieldName] = array();
				} else
					$values[$fieldName] = $data[$fieldName];
	
				break;
	
			case 'date':
				$values[$fieldName] = strip_tags($data[$fieldName]);
				$values[$fieldName] = $this->_cncValidateDate($values[$fieldName], '.');
	
				if (!$values[$fieldName]) {
					$values[$fieldName] = '';
					$ok = FALSE;
					continue;
				}
	
				break;
			}
		}
		return $ok;
	}	
	
	/*
		Add catalog drowdown attribute new option with check existing option value
	*/
	protected function _addAttributeValue($arg_attribute, $arg_value)
	{
			$attribute_model = Mage::getModel('eav/entity_attribute');
	
			$attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
			$attribute = $attribute_model->load($attribute_code);
	
			if(!$this->_attributeValueExists($arg_attribute, $arg_value))
			{
				$value['option'] = array($arg_value,$arg_value);
				$result = array('value' => $value);
				$attribute->setData('option',$result);
				$attribute->save();
			}
	
			$attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;
			$attribute_table = $attribute_options_model->setAttribute($attribute);
			$options = $attribute_options_model->getAllOptions(false);
	
			foreach($options as $option)
			{
				if ($option['label'] == $arg_value)
				{
					return $option['value'];
				}
			}
		   return false;
	}
	
	/*
		Add check attribute existing option value
	*/
	protected function _attributeValueExists($arg_attribute, $arg_value)
		{
			$attribute_model = Mage::getModel('eav/entity_attribute');
			$attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;
	
			$attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
			$attribute = $attribute_model->load($attribute_code);
	
			$attribute_table = $attribute_options_model->setAttribute($attribute);
			$options = $attribute_options_model->getAllOptions(false);
	
			foreach($options as $option)
			{
				if ($option['label'] == $arg_value)
				{
					return $option['value'];
				}
			}
	
			return false;
	}
	
}