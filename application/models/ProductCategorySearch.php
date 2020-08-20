<?php

class ProductCategorySearch extends SearchBase
{

    private $langId;

    public function __construct($langId = 0, $isActive = true, $isDeleted = true, $isOrderByCatCode = true, $doNotLimitRecords = true, $doNotCalculateRecords = true)
    {
        parent::__construct(ProductCategory::DB_TBL, 'c');
        $this->langId = FatUtility::int($langId);

        if ($this->langId > 0) {
            $this->joinTable(
                    ProductCategory::DB_TBL_LANG,
                    'LEFT OUTER JOIN',
                    'prodcatlang_prodcat_id = c.prodcat_id
			AND prodcatlang_lang_id = ' . $langId,
                    'c_l'
            );
        }

        if ($isActive) {
            $this->addCondition('c.prodcat_active', '=', applicationConstants::ACTIVE);
        }

        if ($isDeleted) {
            $this->addCondition('c.prodcat_deleted', '=', applicationConstants::NO);
        }

        if ($isOrderByCatCode) {
            $this->addOrder('c.prodcat_ordercode');
        }

        if ($doNotLimitRecords) {
            $this->doNotLimitRecords();
        }

        if ($doNotCalculateRecords) {
            $this->doNotCalculateRecords();
        }
    }

    public function setParent($parentId = 0)
    {
        $this->addCondition('prodcat_parent', '=', $parentId);
    }

    public function addProductsCountField()
    {
        $prodSrchObj = new ProductSearch($this->langId);
        $prodSrchObj->setDefinedCriteria();
        $prodSrchObj->joinProductToCategory();
        $prodSrchObj->doNotCalculateRecords();
        $prodSrchObj->doNotLimitRecords();
        $prodSrchObj->addGroupBy('c.prodcat_id');
        $prodSrchObj->addMultipleFields(array('count(selprod_id) as productCounts', 'c.prodcat_id as qryProducts_prodcat_id'));
        $prodSrchObj->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $this->joinTable('(' . $prodSrchObj->getQuery() . ')', 'LEFT OUTER JOIN', 'qryProducts.qryProducts_prodcat_id = c.prodcat_id', 'qryProducts');
        $this->addFld(array('IFNULL(productCounts, 0) as productCounts'));
        return $this;
    }

}
