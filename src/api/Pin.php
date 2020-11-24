<?php

namespace api;

use exceptions\InvalidDataException;
use exceptions\MissingDataException;
use models\OpeningHour;
use models\OpeningHourQuery;
use models\PaymentMethodQuery;
use models\Pin as ModelsPin;
use models\PinCategory;
use models\PinCategoryQuery;
use models\PinQuery;
use constants\Paths;

class Pin extends APICall
{
    /**
     * The number of results for page
     * @var int
     */
    protected const PAGE_SIZE = 10;

    /**
     * @param int $id
     */
    public function get(int $id = null)
    {
        if (!empty($id)) {
            $this->read($id);
        } else {
            $this->index($_GET);
        }
    }

    /**
     * @param string $slug
     */
    public function slugGet(string $slug)
    {
        $pinId = PinQuery::create()->select('Id')->findOneBySlug($slug);
        $this->get($pinId ?? null);
    }

    public function post()
    {
        try {
            $this->logger->info('CREATE_PIN_REQUEST', $_POST);
            $post = $this->getPostData();
            $this->checkCreateFields($post);

            $mail = filter_var($post['mail'], FILTER_SANITIZE_EMAIL);
            if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidDataException();
            }

            $pin = new ModelsPin();
            $pin->setTitle($post['title'])
                ->setAddress($post['address'])
                ->setLatitude($post['latitude'])
                ->setLongitude($post['longitude'])
                ->setMail($mail)
                ->setTelephone($post['telephone'])
                ->setDescription($post['description']);

            if (!empty($post['h24'])) {
                $pin->setH24(true);
            }

            $pin->save();

            $this->logger->info('CREATE_PIN_REQUEST_SUCCEEDED', ['id' => $pin->getId()]);
            $this->success(['id' => $pin->getId()], 201);
        } catch (MissingDataException $th) {
            $this->logger->error('CREATE_PIN_REQUEST_FAILED', ['e' => $th->getMessage()]);
            $this->fail();
        } catch (InvalidDataException $th) {
            $this->logger->error('CREATE_PIN_REQUEST_FAILED', ['e' => $th->getMessage()]);
            $this->fail();
        } catch (\Exception $th) {
            $this->logger->critical('CREATE_PIN_REQUEST_FAILED', ['e' => $th->getMessage()]);
            $this->fail(500);
        }
    }

    /**
     * @param int $id
     */
    public function patch(int $id)
    {
        try {
            $this->logger->info('PATCH_PIN_REQUEST', ['id' => $id]);
            $pin = PinQuery::create()->findOneById();
            if (empty($pin)) {
                throw new InvalidDataException();
            }

            $post = $this->getPostData();
            if (!empty($post['categories'])) {
                $this->addCategories($post['categories'], $pin);
            } elseif (!empty($post['openingHours'])) {
                $this->addOpeningHours($post['openingHours'], $pin);
            } else {
                throw new MissingDataException();
            }

            $this->logger->info('PATCH_PIN_REQUEST_SUCCEEDED', ['id' => $id]);
            $this->success();
        } catch (InvalidDataException $th) {
            $this->logger->error('PATCH_PIN_REQUEST_FAILED', ['e' => $th->getMessage()]);
            $this->fail();
        } catch (MissingDataException $th) {
            $this->logger->error('PATCH_PIN_REQUEST_FAILED', ['e' => $th->getMessage()]);
            $this->fail();
        } catch (\Exception $th) {
            $this->logger->critical('PATCH_PIN_REQUEST_FAILED', ['e' => $th->getMessage()]);
            $this->fail(500);
        }
    }

    /**
     * @param array $openingHours
     * @param ModelsPin $pin
     */
    protected function addOpeningHours(array $openingHours, ModelsPin $pin)
    {
        OpeningHourQuery::create()->filterByPin($pin)->delete();
        foreach ($openingHours as $hour) {
            $newHour = new OpeningHour();
            $newHour->setPin($pin)
                ->setDay($hour['value'] ?? $hour['day'])
                ->setMorningOpening($hour['morning']['opening'])
                ->setMorningClosing($hour['morning']['closing']);

            if (!empty($hour['evening'])) {
                $newHour->setEveningOpening($hour['evening']['opening'])
                    ->setEveningClosing($hour['evening']['closing']);
            }

            $newHour->save();
        }
    }

    /**
     * @param array $categories
     * @param ModelsPin $pin
     */
    protected function addCategories(array $categories, ModelsPin $pin)
    {
        PinCategoryQuery::create()->filterByPinId($pin->getId())->delete();
        foreach ($categories as $category) {
            $newCategory = new PinCategory();
            $newCategory->setPin($pin)
                    ->setCategoryId($category['id']);
            if (!empty($category['main']) || $category['main'] == 1) {
                $newCategory->setMain(true);
            } else {
                $newCategory->setMain(false);
            }

            $newCategory->save();
        }
    }

    /**
     * @param array $post
     * @return bool
     */
    protected function checkCreateFields(array $post): bool
    {
        $reqFields = ['title', 'address', 'city', 'zip', 'description', 'mail', 'telephone', 'latitude', 'longitude'];
        foreach ($reqFields as $field) {
            if (!key_exists($field, $post)) {
                throw new MissingDataException();
            }
        }

        return true;
    }

    /**
     * @param array $get
     */
    protected function index(array $get = [])
    {
        try {
            $this->logger->info('GET_PINS_REQUEST', $get);

            $currentPage = $get['page'] ?? $get['offset'] ?? 0;

            $pins = PinQuery::create();
            if (!empty($get['q'])) {
                $pins->search($get['q']);
            } elseif (!empty($get['category'])) {
                $pins->usePinCategoryQuery()->useCategoryQuery()->filterBySlug($get['category'])->endUse()->endUse();
            } else {
                throw new MissingDataException();
            }

            if (!empty($get['latitude']) && !empty($get['longitude'])) {
                $pins->near((float)$get['latidue'], (float)$get['longitude']);
            } elseif (!empty($get['city'])) {
                $pins->useCityQuery()->filterBySlug($get['city'])->endUse();
            } elseif (!empty($get['district'])) {
                $pins->useCityQuery()
                    ->useDistrictQuery()
                    ->filterBySlug($get['district'])
                    ->endUse()
                    ->endUse();
            } else {
                throw new MissingDataException();
            }

            $pins->limit(self::PAGE_SIZE)
                ->offset($currentPage * self::PAGE_SIZE)
                ->find();

            $results = $this->getSearchJSON($pins, $get['category'] ?? null);
            $this->logger->info('GET_PINS_REQUEST_SUCCEEDED', ['pins' => $results]);
            $this->success($results);
        } catch (MissingDataException $e) {
            $this->logger->error('GET_PINS_REQUEST_FAILED', ['e' => $e->getMessage()]);
            $this->fail();
        } catch (\Exception $e) {
            $this->logger->critical('GET_PINS_REQUEST_FAILED', ['e' => $e->getMessage()]);
            $this->fail(500);
        }
    }

    protected function read(int $id)
    {
        try {
            $this->logger->info('GET_PIN_REQUEST', ['id' => $id]);

            $pin = PinQuery::create()->findOneById($id);
            if (empty($pin)) {
                throw new InvalidDataException();
            }

            $result = $this->getReadJSON($pin);
            $this->logger->info('GET_PIN_REQUEST_SUCCEEDED', ['pin' => $result]);
            $this->success($result);
        } catch (InvalidDataException $th) {
            $this->logger->error('GET_PIN_REQUEST_FAILED', ['id' => $id, 'e' => $th->getMessage()]);
            $this->fail();
        } catch (\Exception $e) {
            $this->logger->critical('GET_PIN_REQUEST_FAILED', ['id' => $id, 'e' => $e->getMessage()]);
            $this->fail(500);
        }
    }

    /**
     * @param ModelsPin $pin
     * @return array
     * @throws \Exception
     */
    protected function getReadJSON(ModelsPin $pin): array
    {
        $images = [];
        foreach ($pin->getPinImages() as $image) {
            $images[] = $this->basePath . '/' . Paths::PINS_IMAGES . '/' . $image->getPath();
        }

        $offers = [];
        foreach ($pin->getOffers() as $offer) {
            $offers[] = [
                'title' => $offer->getTitle(),
                'description' => $offer->getDescription(),
                'expire' => $offer->getExpireDate()->format('c')
            ];
        }

        $mainCategory = $pin->getMainCategory();
        $otherCategories = [];
        foreach ($pin->getCategories() as $category) {
            if ($category != $mainCategory) {
                $otherCategories[] = [
                    'imgPath' => $this->basePath . '/' . Paths::CATEGORIES_IMAGES . '/' . $category->getImg(),
                    'name' => $category->getName()
                ];
            }
        }

        $products = [];
        foreach ($pin->getProducts() as $product) {
            $products[] = $product->getName();
        }

        $openingHours = [];
        foreach ($pin->getOpeningHours() as $hour) {
            $openingHours[] = [
                'day' => $hour->getDay(),
                'morningOpening' => $hour->getMorningOpening()->format('c'),
                'morningClosing' => $hour->getMorningClosing()->format('c'),
                'eveningOpening' => $hour->getEveningOpening() ? $hour->getEveningOpening()->format('c') : null,
                'eveningClosing' => $hour->getEveningClosing() ? $hour->getEveningClosing()->format('c') : null
            ];
        }

        $allPayments = PaymentMethodQuery::create()->find();
        $payments = [];
        foreach ($allPayments as $payment) {
            $payments[] = [
                'imgPath' => $this->basePath . '/' . Paths::PAYMENTS_IMAGES . '/' . $payment->getImg(),
                'name' => $payment->getName(),
                'active' => $pin->getPaymentMethods()->contains($payment)
            ];
        }

        $feedbacks = [];
        foreach ($pin->getFeedbacks() as $feedback) {
            $feedbacks[] = [
                'author' => $feedback->getAuthor(),
                'rate' => $feedback->getRate(),
                'text' => $feedback->getText(),
                'answer' => $feedback->getAnswer()
            ];
        }

        $extraordinaryClosing = [];
        foreach ($pin->getExtraordinaryClosings() as $closing) {
            $extraordinaryClosing[] = [
                'from' => $closing->getFromDate()->format('c'),
                'to' => $closing->getToDate()->format('c')
            ];
        }

        $city = $pin->getCity();

        return [
            'id' => $pin->getId(),
            'title' => $pin->getTitle(),
            'description' => $pin->getDescription(),
            'images' => $images,
            'offers' => $offers,
            'category' => [
                'imgPath' => $this->basePath . '/' . Paths::CATEGORIES_IMAGES . '/' . $mainCategory->getImg(),
                'name' => $mainCategory->getName(),
                'slug' => $mainCategory->getSlug()
            ],
            'otherCategories' => $otherCategories,
            'products' => $products,
            'isOpen' => $pin->isOpen(),
            'openingHours' => $openingHours,
            'paymentMethods' => $payments,
            'feedbacks' => $feedbacks,
            'telephone' => $pin->getTelephone(),
            'socialPage' => $pin->getSocialPage(),
            'site' => $pin->getSite(),
            'email' => $pin->getMail(),
            'address' => [
                'road' => $pin->getAddress(),
                'city' => [
                    'name' => $city->getName(),
                    'slug' => $city->getSlug()
                ],
                'zip' => $city->getZip(),
                'latitude' => $pin->getLatitude(),
                'longitude' => $pin->getLongitude()
            ],
            'filaliscioId' => $pin->getFilaliscioId(),
            'onAppointment' => $pin->isAcceptAppointment(),
            'avgRate' => $pin->getAverageRate(),
            'extraordinaryClosings' => $extraordinaryClosing,
            'video' => $pin->getVideo(),
            'slug' => $pin->getSlug(),
        ];
    }

    /**
     * @param ModelsPin[] $pins
     * @param null|String $category
     * @return array
     */
    protected function getSearchJSON($pins, String $category = null): array
    {
        $json = [];
        foreach ($pins as $pin) {
            $openingHours = [];
            foreach ($pin->getOpeningHours() as $hour) {
                $openingHours[] = [
                    'day' => $hour->getDay(),
                    'morningOpening' => $hour->getMorningOpening()->format('c'),
                    'morningClosing' => $hour->getMorningClosing()->format('c'),
                    'eveningOpening' => $hour->getEveningOpening() ? $hour->getEveningOpening()->format('c') : null,
                    'eveningClosing' => $hour->getEveningClosing() ? $hour->getEveningClosing()->format('c') : null
                ];
            }

            $pinCategory = $pin->getMainCategory();
            $json[] = [
                'id' => $pin->getId(),
                'slug' => $pin->getSlug(),
                'title' => $pin->getTitle(),
                'category' => [
                    'name' => $pinCategory->getName(),
                    'slug' => $pinCategory->getSlug()
                ],
                'latitude' => $pin->getLatitude(),
                'longitude' => $pin->getLongitude(),
                'distance' => $pin->getDistance(),
                'offerCount' => count($pin->getOffers()),
                'avgRate' => $pin->getAverageRate(),
                'rateCount' => count($pin->getFeedbacks()),
                'isOpen' => $pin->isOpen(),
                'openingHours' => $openingHours,
                'onAppointment' => $pin->isAcceptAppointment(),
                'city' => $pin->getCity()->getName(),
                'district' => $pin->getCity()->getDistrict()->getAbbreviation(),
                'image' => $pin->getPinImages()->getFirst() ?? null,
                'isMainCategory' => $pinCategory->getSlug() === $category,
            ];
        }

        return $json;
    }
}
