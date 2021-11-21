<?php

namespace VerteraDev\TranslationLoader\Yii2\Writer;

use Yii;
use yii\db\Exception;
use yii\db\Query;
use VerteraDev\TranslationLoader\Data\TranslationGroup;
use VerteraDev\TranslationLoader\Writer\TranslationWriterAbstract;
use VerteraDev\TranslationLoader\Exception\TranslationLoaderException;

class DbWriter extends TranslationWriterAbstract
{
    /**
     * @param TranslationGroup $translationGroup
     * @return bool
     * @throws Exception
     * @throws TranslationLoaderException
     */
    public function write(TranslationGroup $translationGroup): bool
    {
        $phraseId = $this->getPhraseIdInDatabase($translationGroup);
        if (!$phraseId) {
            $this->createPhraseInDatabase($translationGroup);
            $phraseId = $this->getPhraseIdInDatabase($translationGroup);
        }
        $this->updatePhraseTranslationsInDatabase($phraseId, $translationGroup);

        return true;
    }

    public function finalize(): void
    {
    }

    /**
     * @param TranslationGroup $translationGroup
     * @return int|null
     */
    protected function getPhraseIdInDatabase(TranslationGroup $translationGroup): ?int
    {
        $query = (new Query())
            ->from('translate_source')
            ->select('id')
            ->where([
                'AND',
                ['=', 'category', $translationGroup->category],
                ['=', 'message', $translationGroup->code]
            ])
            ->limit(1);

        return $query->scalar() ?: null;
    }

    /**
     * @param TranslationGroup $translationGroup
     * @throws Exception
     * @throws TranslationLoaderException
     */
    protected function createPhraseInDatabase(TranslationGroup $translationGroup): void
    {
        $query = Yii::$app->db->createCommand()->insert('translate_source', [
            'category' => $translationGroup->category,
            'message' => $translationGroup->code
        ]);

        if (!$query->execute()) {
            throw new TranslationLoaderException('An error occurred while saving a new phrase!', [
                'category' => $translationGroup->category,
                'code' => $translationGroup->code
            ]);
        }
    }

    /**
     * @param int $id
     * @param TranslationGroup $translationGroup
     * @throws Exception
     * @throws TranslationLoaderException
     */
    protected function updatePhraseTranslationsInDatabase(int $id, TranslationGroup $translationGroup): void
    {
        $activeLanguages = $this->manager->getLanguages();
        foreach ($translationGroup->items as $item) {
            if (!in_array($item->language, $activeLanguages)) {
                continue;
            }

            $query = Yii::$app->db->createCommand()->upsert('translate_translation', [
                'source_id' => $id,
                'language_id' => $item->language,
                'translation' => $item->content
            ], ['translation' => $item->content]);

            if (!$query->execute()) {
                throw new TranslationLoaderException('An error occurred while saving the phrase translation variant!', [
                    'source_id' => $id,
                    'language_id' => $item->language,
                    'translation' => $item->content
                ]);
            }
        }
    }
}
