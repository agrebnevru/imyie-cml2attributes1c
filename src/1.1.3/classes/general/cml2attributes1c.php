<?php

IncludeModuleLangFile(__FILE__);

class CIMYIECML2Attr
{
    public static function OnAfterIBlockElementAddUpdateHandler(&$arFields)
    {
        if (CModule::IncludeModule('iblock') && CModule::IncludeModule('catalog')) {
            $ELEMENT_ID = 0;
            $IBLOCK_ID = $arFields["IBLOCK_ID"];
            $CML2_PROP_CODE = "CML2_ATTRIBUTES";
            if (IntVal($arFields["ID"]) > 0) {
                $ELEMENT_ID = $arFields["ID"];
            } elseif (IntVal($arFields["RESULT"]) > 1) {
                $ELEMENT_ID = $arFields["RESULT"];
            }
            $arCatalog = CCatalog::GetByID($IBLOCK_ID);
            if ($arCatalog && is_array($arCatalog) && IntVal($arCatalog["PRODUCT_IBLOCK_ID"]) > 0 && IntVal(
                    $ELEMENT_ID
                ) > 0) {
                $dbRes = CIBlockProperty::GetList(
                    array(),
                    array(
                        "ACTIVE" => "Y",
                        "IBLOCK_ID" => $IBLOCK_ID,
                        "CODE" => $CML2_PROP_CODE
                    )
                );
                if ($propFields = $dbRes->GetNext()) {
                    $PROPERTY_ID = $propFields["ID"];
                    $CML2_PROPS = $arFields["PROPERTY_VALUES"][$PROPERTY_ID];
                    $arPropertiesUpdate = array();
                    foreach ($CML2_PROPS as $valID => $arValue) {
                        $pID = self::CheckProperty($IBLOCK_ID, $arValue["DESCRIPTION"]);
                        if (IntVal($pID) > 0) {
                            CIBlockElement::SetPropertyValues($ELEMENT_ID, $IBLOCK_ID, $arValue["VALUE"], $pID);
                        }
                    }
                    // I can't use because of a problem with static variable in this function
                    //CIBlockElement::SetPropertyValuesEx($ELEMENT_ID,$IBLOCK_ID,$arPropertiesUpdate);
                }
            }
        }
    }

    public static function CheckProperty($IBLOCK_ID, $PROPERTY_NAME)
    {
        $return = 0;

        // translit name
        $arTransParams = array("max_len" => 25, "change_case" => "U");
        $PROPERTY_CODE = "IMYIE_CML2ATTR_" . CUtil::translit($PROPERTY_NAME, "ru", $arTransParams);

        // check property
        $dbProperties = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $IBLOCK_ID, "CODE" => $PROPERTY_CODE));
        if (!$arFields = $dbProperties->GetNext()) {
            // add property
            $arFields = array(
                "IBLOCK_ID" => $IBLOCK_ID,
                "NAME" => $PROPERTY_NAME,
                "ACTIVE" => "Y",
                "SORT" => "10",
                "CODE" => $PROPERTY_CODE,
                "PROPERTY_TYPE" => "S",
                "MULTIPLE" => "N",
            );

            $ibp = new CIBlockProperty;
            $PropID = $ibp->Add($arFields);
            if (IntVal($PropID)) {
                $return = $PropID;
            }
        } else {
            $return = $arFields["ID"];
        }

        return $return;
    }
}
