<?php

class ProductCategory extends MyAppModel
{

    public const DB_TBL = 'tbl_product_categories';
    public const DB_TBL_PREFIX = 'prodcat_';
    public const DB_TBL_LANG = 'tbl_product_categories_lang';
    public const DB_TBL_LANG_PREFIX = 'prodcatlang_';
    public const REWRITE_URL_PREFIX = 'category/view/';
    public const REMOVED_OLD_IMAGE_TIME = 4;

    private $db;
    private $categoryTreeArr = array();

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
        $this->db = FatApp::getDb();
    }

    public static function getSearchObject($includeChildCount = false, $langId = 0, $prodcat_active = true)
    {
        $langId = FatUtility::int($langId);
        $srch = new SearchBase(static::DB_TBL, 'm');
        $srch->addOrder('m.prodcat_active', 'DESC');

        if ($includeChildCount) {
            $childSrchbase = new SearchBase(static::DB_TBL);
            $childSrchbase->addCondition('prodcat_deleted', '=', 0);
            $childSrchbase->doNotCalculateRecords();
            $childSrchbase->doNotLimitRecords();
            $srch->joinTable('(' . $childSrchbase->getQuery() . ')', 'LEFT OUTER JOIN', 's.prodcat_parent = m.prodcat_id', 's');
            $srch->addGroupBy('m.prodcat_id');
            $srch->addFld('COUNT(s.prodcat_id) AS child_count');
        }

        if ($langId > 0) {
            $srch->joinTable(
                    static::DB_TBL_LANG,
                    'LEFT OUTER JOIN',
                    'pc_l.' . static::DB_TBL_LANG_PREFIX . 'prodcat_id = m.' . static::tblFld('id') . ' and
			pc_l.' . static::DB_TBL_LANG_PREFIX . 'lang_id = ' . $langId,
                    'pc_l'
            );
        }

        if ($prodcat_active) {
            $srch->addCondition('m.prodcat_active', '=', applicationConstants::ACTIVE);
        }

        return $srch;
    }

    public static function requiredFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'prodcat_id'
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'prodcat_identifier',
                'prodcat_name',
            )
        );
    }

    public static function validateFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function requiredMediaFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'prodcat_id'
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'prodcat_identifier',
                'afile_physical_path',
                'afile_name',
                'afile_type',
            )
        );
    }

    public static function validateMediaFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredMediaFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public function updateCatCode()
    {
        $categoryId = $this->mainTableRecordId;
        if (1 > $categoryId) {
            return false;
        }

        $categoryArray = array($categoryId);
        $parentCatData = ProductCategory::getAttributesById($categoryId, array('prodcat_parent'));
        if (array_key_exists('prodcat_parent', $parentCatData) && $parentCatData['prodcat_parent'] > 0) {
            array_push($categoryArray, $parentCatData['prodcat_parent']);
        }

        foreach ($categoryArray as $categoryId) {
            $srch = ProductCategory::getSearchObject();
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $srch->addMultipleFields(array('prodcat_id', 'GETCATCODE(`prodcat_id`) as prodcat_code', 'GETCATORDERCODE(`prodcat_id`) as prodcat_ordercode'));
            $srch->addCondition('GETCATCODE(`prodcat_id`)', 'LIKE', '%' . str_pad($categoryId, 6, '0', STR_PAD_LEFT) . '%', 'AND', true);
            $rs = $srch->getResultSet();
            $catCode = FatApp::getDb()->fetchAll($rs);
            foreach ($catCode as $row) {
                $record = new ProductCategory($row['prodcat_id']);
                $data = array('prodcat_code' => $row['prodcat_code'], 'prodcat_ordercode' => $row['prodcat_ordercode']);
                $record->assignValues($data);
                if (!$record->save()) {
                    Message::addErrorMessage($record->getError());
                    return false;
                }
            }
        }
        return true;
    }

    public static function updateCatOrderCode($prodCatId = 0)
    {
        $prodCatId = FatUtility::int($prodCatId);

        $srch = ProductCategory::getSearchObject(false, 0, false);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('prodcat_id', 'GETCATORDERCODE(`prodcat_id`) as prodcat_ordercode'));
        if ($prodCatId) {
            $srch->addCondition('prodcat_id', '=', $prodCatId);
        }

        $rs = $srch->getResultSet();
        $orderCode = FatApp::getDb()->fetchAll($rs);
        foreach ($orderCode as $row) {
            $record = new ProductCategory($row['prodcat_id']);
            $data = array('prodcat_ordercode' => $row['prodcat_ordercode']);
            $record->assignValues($data);
            if (!$record->save()) {
                Message::addErrorMessage($record->getError());
                return false;
            }
        }
    }

    public function getMaxOrder($parent = 0)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addFld("MAX(" . static::DB_TBL_PREFIX . "display_order) as max_order");
        if ($parent > 0) {
            $srch->addCondition(static::DB_TBL_PREFIX . 'parent', '=', $parent);
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $record = FatApp::getDb()->fetch($rs);
        if (!empty($record)) {
            return $record['max_order'] + 1;
        }
        return 1;
    }

    public function haveProducts()
    {
        $prodSrchObj = new ProductSearch();
        $prodSrchObj->setDefinedCriteria();
        $prodSrchObj->joinProductToCategory();
        $prodSrchObj->doNotCalculateRecords();
        $prodSrchObj->setPageSize(1);

        $prodSrchObj->addMultipleFields(array('substr(prodcat_code,1,6) AS prodrootcat_code', 'count(selprod_id) as productCounts', 'prodcat_id'));

        if (0 < $this->mainTableRecordId) {
            $prodSrchObj->addHaving('prodrootcat_code', 'LIKE', '%' . str_pad($this->mainTableRecordId, 6, '0', STR_PAD_LEFT) . '%', 'AND', true);
        }
        $prodSrchObj->addHaving('productCounts', '>', 0);
        $rs = $prodSrchObj->getResultSet();
        $productRows = FatApp::getDb()->fetch($rs);
        if (!empty($productRows) && $productRows['productCounts'] > 0) {
            return true;
        }
        return false;
    }

    public function rewriteUrl($keyword, $suffixWithId = true, $parentId = 0)
    {
        if ($this->mainTableRecordId < 1) {
            return false;
        }

        $parentId = FatUtility::int($parentId);

        $originalUrl = ProductCategory::REWRITE_URL_PREFIX . $this->mainTableRecordId;

        $keyword = preg_replace('/-' . $this->mainTableRecordId . '$/', '', $keyword);
        $seoUrl = CommonHelper::seoUrl($keyword);
        if ($suffixWithId) {
            $seoUrl = $seoUrl . '-' . $this->mainTableRecordId;
        }

        $customUrl = UrlRewrite::getValidSeoUrl($seoUrl, $originalUrl, $this->mainTableRecordId);
        return UrlRewrite::update($originalUrl, $customUrl);
    }

    public static function setImageUpdatedOn($userId, $date = '')
    {
        $date = empty($date) ? date('Y-m-d  H:i:s') : $date;
        $where = array('smt' => 'prodcat_id = ?', 'vals' => array($userId));
        FatApp::getDb()->updateFromArray(static::DB_TBL, array('prodcat_img_updated_on' => date('Y-m-d  H:i:s')), $where);
    }

    public function saveCategoryData($post)
    {
        $parentCatId = FatUtility::int($post['prodcat_parent']);
        $prodCatId = FatUtility::int($post['prodcat_id']);
        unset($post['prodcat_id']);
        $autoUpdateOtherLangsData = 0;
        if (isset($post['auto_update_other_langs_data'])) {
            $autoUpdateOtherLangsData = FatUtility::int($post['auto_update_other_langs_data']);
        }
        $siteDefaultLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
        $post['prodcat_identifier'] = $post['prodcat_name'][$siteDefaultLangId];
        if ($this->mainTableRecordId == 0) {
            $post['prodcat_display_order'] = $this->getMaxOrder($parentCatId);
        }

        if ($post['prodcat_parent'] == $this->mainTableRecordId) {
            $post['prodcat_parent'] = 0;
        }

        $this->assignValues($post);
        if ($this->save()) {
            $this->updateCatCode();
            $this->rewriteUrl($post['prodcat_identifier'], true, $parentCatId);
            Product::updateMinPrices();
        } else {
            $categoryId = self::getDeletedProductCategoryByIdentifier($post['prodcat_identifier']);
            if (!$categoryId) {
                $this->error = $this->getError();
                return false;
            }

            $record = new ProductCategory($categoryId);
            $data = $post;
            $data['prodcat_deleted'] = applicationConstants::NO;
            $record->assignValues($data);
            if (!$record->save()) {
                $this->error = $record->getError();
                return false;
            }
            $this->mainTableRecordId = $record->getMainTableRecordId();
            $this->updateCatCode();
        }

        $this->saveLangData($siteDefaultLangId, $post['prodcat_name'][$siteDefaultLangId]); // For site default language
        $catNameArr = $post['prodcat_name'];
        unset($catNameArr[$siteDefaultLangId]);
        foreach ($catNameArr as $langId => $catName) {
            if (empty($catName) && $autoUpdateOtherLangsData > 0) {
                $this->saveTranslatedLangData($langId);
            } elseif (!empty($catName)) {
                $this->saveLangData($langId, $catName);
            }
        }

        if ($prodCatId == 0) {
            $this->updateMedia($post['cat_icon_image_id']);
            $this->updateMedia($post['cat_banner_image_id']);
        }
        return true;
    }

    public function saveLangData($langId, $prodCatName)
    {
        $langId = FatUtility::int($langId);
        if ($this->mainTableRecordId < 1 || $langId < 1) {
            $this->error = Labels::getLabel('ERR_Invalid_Request', $this->commonLangId);
            return false;
        }

        $data = array(
            'prodcatlang_prodcat_id' => $this->mainTableRecordId,
            'prodcatlang_lang_id' => $langId,
            'prodcat_name' => $prodCatName,
        );
        if (!$this->updateLangData($langId, $data)) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    public function saveTranslatedLangData($langId)
    {
        $langId = FatUtility::int($langId);
        if ($this->mainTableRecordId < 1 || $langId < 1) {
            $this->error = Labels::getLabel('ERR_Invalid_Request', $this->commonLangId);
            return false;
        }

        $translateLangobj = new TranslateLangData(static::DB_TBL_LANG);
        if (false === $translateLangobj->updateTranslatedData($this->mainTableRecordId, 0, $langId)) {
            $this->error = $translateLangobj->getError();
            return false;
        }
        return true;
    }

    public function updateMedia($ImageIds)
    {
        if (count($ImageIds) == 0) {
            return false;
        }
        foreach ($ImageIds as $imageId) {
            if ($imageId > 0) {
                $data = array('afile_record_id' => $this->mainTableRecordId);
                $where = array('smt' => 'afile_id = ?', 'vals' => array($imageId));
                FatApp::getDb()->updateFromArray(AttachedFile::DB_TBL, $data, $where);
            }
        }
        return true;
    }

}
