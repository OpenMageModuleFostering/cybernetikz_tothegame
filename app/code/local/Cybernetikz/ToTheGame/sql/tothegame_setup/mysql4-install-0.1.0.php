<?php

/**
 * @category     CyberNetikz
 * @package      CyberNetikz_ToTheGame
 * @author       CyberNetikz
 * @author_url   http://www.cybernetikz.com/
 */

/** @var $installer Cybernetikz_ToTheGame_Model_Resource_Mysql4_Setup */
$installer = $this;
$installer->startSetup(); 

$select_attributes=array('genre','publishers','age','ean','platform_id','platform','release_date','publisher','editions','last_update','also_available_on','keywords','localized_title','main_game','company_logo_url','developer_homepage','developer_id','developer_name','pegi_rating','pegi_rating_id','in_game_language',/*'packshots','screenshots',*/'videos');
foreach($select_attributes as $attribute_label=>$attribute_code){
	$type_model = Mage::getModel('eav/entity_type')->loadByCode('catalog_product');
	$model = Mage::getModel('catalog/entity_attribute');
	$model->loadByCode($type_model->getId(), $attribute_code);
	if($model->getId()) {
		$model->delete();
	}
}

$attribute_set_group='Game Information';

$select_attributes=array('Genre'=>'genre','Publishers'=>'publishers');
$ap=0;
foreach($select_attributes as $attribute_label=>$attribute_code){
	$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute_code, array(
		'group'         => $attribute_set_group,
		'backend'       => '',
		'frontend'      => '',
		'label'         => $attribute_label,
		'type' 			=> 'int',
		'input'         => 'select',
		'source'        => 'eav/entity_attribute_source_table',
		'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
		'visible'       => true,
		'required'      => false,
		'user_defined'  => true,
		'default'       => '',
		'searchable'    => false,
		'filterable'    => true,
		'comparable'    => false,
		'configurable'	=> false,
		'visible_on_front' => false,
		'position' 		=> $ap
	));
	$ap+=10;
}

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'age', array(
	'group'         => $attribute_set_group,
	'backend'       => 'eav/entity_attribute_backend_array',
	'frontend'      => '',
	'label'         => 'Age',
	'type' 			=> 'varchar',
	'input'         => 'multiselect',
	'source'        => '',
	'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
	'visible'       => true,
	'required'      => false,
	'user_defined'  => true,
	'default'       => '',
	'searchable'    => false,
	'filterable'    => true,
	'comparable'    => false,
	'configurable'	=> false,
	'visible_on_front' => false,
	'position' 		=> 20
));

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'game_id', array(
	'group'         => $attribute_set_group,
	'backend'       => '',
	'frontend'      => '',
	'label'         => 'Game Id',
	'type' 			=> 'varchar',
	'input'         => 'text',
	'source'        => '',
	'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
	'visible'       => true,
	'required'      => false,
	'user_defined'  => true,
	'default'       => '',
	'unique'		=> true,
	'searchable'    => false,
	'filterable'    => false,
	'comparable'    => false,
	'configurable'	=> false,
	'visible_on_front' => false,
	'position' 		=> 30
));

$text_attributes = array('EAN'=>'ean','Platform Id'=>'platform_id','Platform'=>'platform','Release Date'=>'release_date','Publisher'=>'publisher','Editions'=>'editions','Last Update'=>'last_update','Also Available On'=>'also_available_on','Keywords'=>'keywords','Localized Title'=>'localized_title','Main Game'=>'main_game','Company Logo URL'=>'company_logo_url','Developer Homepage'=>'developer_homepage','Developer Id'=>'developer_id','Developer Name'=>'developer_name','Pegi Rating'=>'pegi_rating','Pegi Rating Id'=>'pegi_rating_id','In Game Language'=>'in_game_language');
$ap=40;
foreach($text_attributes as $attribute_label=>$attribute_code){
	$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute_code, array(
		'group'         => $attribute_set_group,
		'backend'       => '',
		'frontend'      => '',
		'label'         => $attribute_label,
		'type' 			=> 'varchar',
		'input'         => 'text',
		'source'        => '',
		'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
		'visible'       => true,
		'required'      => false,
		'user_defined'  => true,
		'default'       => '',
		'searchable'    => false,
		'filterable'    => false,
		'comparable'    => false,
		'configurable'	=> false,
		'visible_on_front' => false,
		'position' 		=> $ap
	));
	$ap+=10;
}

$textarea_attributes = array(/*'Packshots'=>'packshots','Screenshots'=>'screenshots',*/'Videos'=>'videos');
foreach($textarea_attributes as $attribute_label=>$attribute_code){
	$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute_code, array(
		'group'         => $attribute_set_group,
		'backend'       => '',
		'frontend'      => '',
		'label'         => $attribute_label,
		'type' 			=> 'text',
		'input'         => 'textarea',
		'source'        => '',
		'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
		'visible'       => true,
		'required'      => false,
		'user_defined'  => true,
		'default'       => '',
		'searchable'    => false,
		'filterable'    => false,
		'comparable'    => false,
		'configurable'	=> false,
		'visible_on_front' => false,
		'position' 		=> $ap
	));
	$ap+=10;
}

$installer->endSetup();