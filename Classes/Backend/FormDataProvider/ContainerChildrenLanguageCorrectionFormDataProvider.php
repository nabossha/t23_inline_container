<?php

namespace Team23\T23InlineContainer\Backend\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContainerChildrenLanguageCorrectionFormDataProvider implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        if (empty($result["processedTca"]["columns"]["tx_t23inlinecontainer_elements"])) {
            return $result;
        }

        $registeredCTypes = GeneralUtility::makeInstance(\B13\Container\Tca\Registry::class)->getRegisteredCTypes();
        $cType = $result['databaseRow']['CType'][0] ?? '';
        if (!in_array($cType, $registeredCTypes, true)) {
            return $result;
        }

        $parentLanguageUid = (int)$result['databaseRow']['sys_language_uid'] ?? 0;

        $children =& $result['processedTca']['columns']['tx_t23inlinecontainer_elements']['children'];
        $translationsExistFor = [];

        // store ids of original language elements that have a translation
        foreach ($children as &$child) {
            $lang = (int)$child['databaseRow']['sys_language_uid'] ?? 0;
            $origUid = (int)($child['databaseRow']['l18n_parent'][0] ?? 0);
            if ($parentLanguageUid === 0 && $lang > 0) {
                continue;
            }
            if ($parentLanguageUid > 0 && $lang === $parentLanguageUid && $origUid > 0) {
                $translationsExistFor[] = $origUid;
            }
            // if child language differs from parents language, grey out child in backend
            $child['isInlineDefaultLanguageRecordInLocalizedParentContext'] = ($lang !== $parentLanguageUid);
        }

        $filteredChildren = [];

        foreach ($children as $i => &$child) {
            $lang = (int)$child['databaseRow']['sys_language_uid'] ?? 0;
            //$child['defaultLanguageDiffRow'] = null;

            // hide translated elements from default language view
            if ($parentLanguageUid === 0 && $lang > 0) {
                continue;
            }

            // hide children from other translation languages (not default, not current language)
            if ($parentLanguageUid > 0 && $lang > 0 && $lang !== $parentLanguageUid) {
                continue;
            }

            // hide original language children when they have a translated element
            $childUid = (int)($child['databaseRow']['uid'] ?? 0);
            if (in_array($childUid, $translationsExistFor, true)) {
                continue;
            }

            $filteredChildren[] = $child;
        }
        $children = array_values($filteredChildren);

        return $result;
    }
}
