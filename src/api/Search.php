<?php

namespace api;

use exceptions\InvalidDataException;
use exceptions\MissingDataException;
use models\CategoryQuery;
use models\CityQuery;
use models\DistrictQuery;

class Search extends APICall
{
    protected const MAX_RESULTS = 4;

    public function get()
    {
        try {
            $this->logger->info('SEARCH_REQUEST', $_GET);
            if (empty($_GET)) {
                throw new MissingDataException();
            }

            if (!empty($_GET['what'])) {
                $data = $this->getWhat($_GET['what']);
            } elseif (!empty($_GET['where'])) {
                $data = $this->getWhere($_GET['where']);
            } else {
                throw new InvalidDataException();
            }

            $this->logger->info('SEARCH_REQUEST_SUCCEEDED', $data);
            $this->success($data);
        } catch (InvalidDataException $th) {
            $this->logger->error('SEARCH_REQUEST_FAILED', ['e' => $th->getMessage()]);
            $this->fail();
        } catch (MissingDataException $th) {
            $this->logger->error('SEARCH_REQUEST_FAILED', ['e' => $th->getMessage()]);
            $this->fail();
        } catch (\Exception $e) {
            $this->logger->critical('SEARCH_REQUEST_FAILED', ['e' => $e->getMessage()]);
            $this->fail(500);
        }
    }

    /**
     * @param String $what
     * @return array
     */
    protected function getWhat(String $what): array
    {
        $data = [];

        //  Search for matching categories
        $categories = CategoryQuery::create()
            ->search($what)
            ->orderBySlug()
            ->limit(self::MAX_RESULTS)
            ->find();
        foreach ($categories as $cat) {
            $data[] = [
                'name' => $cat->getName(), 'slug' => $cat->getSlug(), 'type' => 'category'
            ];
        }

        return $data;
    }

    /**
     * @param String $where
     * @return array
     */
    protected function getWhere(String $where): array
    {
        $data = [];

        $districts = DistrictQuery::create()->search($where)->limit(self::MAX_RESULTS)->find();
        foreach ($districts as $district) {
            $data[] = [
                'name' => $district->getName(),
                'slug' => $district->getSlug(),
                'abbreviation' => $district->getAbbreviation(),
                'type' => 'district'
            ];
        }

        $cities = CityQuery::create()->search($where)->limit(self::MAX_RESULTS)->find();
        foreach ($cities as $city) {
            $data[] = [
                'name' => $city->getName(),
                'slug' => $city->getSlug(),
                'district' => $city->getDistrict()->getAbbreviation(),
                'type' => 'city'
            ];
        }

        $chunks = array_chunk($data, 4);
        return $chunks[0] ?? [];
    }
}
