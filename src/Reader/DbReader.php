<?php

namespace VerteraDev\TranslationLoader\Yii2\Reader;

use Generator;
use yii\db\Query;
use VerteraDev\TranslationLoader\Data\TranslationGroup;
use VerteraDev\TranslationLoader\Data\TranslationItem;
use VerteraDev\TranslationLoader\Reader\TranslationReaderAbstract;

class DbReader extends TranslationReaderAbstract
{
    public function read(): Generator
    {
        $sourceQuery = (new Query())->from('translate_source');

        $activeLanguages = $this->manager->getLanguages();
        foreach ($sourceQuery->each() as $sourceData) {
            $translationOptionQuery = (new Query())
                ->from('translate_translation')
                ->where(['=', 'source_id', $sourceData['id']]);

            $translationGroup = new TranslationGroup();
            $translationGroup->category = $sourceData['category'];
            $translationGroup->code = $sourceData['message'];

            foreach ($translationOptionQuery->each() as $translationOptionData) {
                if (!in_array($translationOptionData['language_id'], $activeLanguages)) {
                    continue;
                }

                $translationItem = new TranslationItem();
                $translationItem->language = $translationOptionData['language_id'];
                $translationItem->content = $translationOptionData['translation'];

                $translationGroup->items[] = $translationItem;
            }

            yield $translationGroup;
        }
    }
}
