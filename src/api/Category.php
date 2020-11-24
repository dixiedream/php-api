<?php

namespace api;

use models\Category as ModelsCategory;
use models\CategoryQuery;

class Category extends APICall
{
    public function index()
    {
        try {
            $this->logger->info('CATEGORIES_REQUEST', $_GET);
            if (!empty($_GET['q'])) {
                $categories = CategoryQuery::create()->search($_GET['q'])->find();
            } else {
                $categories = CategoryQuery::create()->find();
            }
            $toReturn = [];
            foreach ($categories as $cat) {
                $toReturn[] = $this->toJSON($cat);
            }

            $this->logger->info('CATEGORIES_REQUEST_SUCCEEDED');
            $this->success($toReturn);
        } catch (\Exception $e) {
            $this->logger->critical('CATEGORIES_REQUEST_FAILED', ['e' => $e]);
            $this->fail(500);
        }
    }

    /**
     * @param ModelsCategory $category
     * @return array
     */
    protected function toJSON(ModelsCategory $category): array
    {
        $macro = $category->getMacroCategory();
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'macroCategory' => [
                'id' => $macro->getId(),
                'name' => $macro->getName()
            ]
        ];
    }
}
