<?php

namespace api;

use claviska\SimpleImage;
use Exception;
use exceptions\InvalidDataException;
use exceptions\MissingDataException;
use models\Pin;
use models\PinImage as ModelsPinImage;
use models\PinImageQuery;
use models\PinQuery;

class PinImage extends APICall
{
    /**
     * In pixels.
     */
    const IMG_WIDTH = 1024;

    /**
     * Valid values from 0 to 100.
     */
    const JPG_QUALITY = 100;

    public function index(): void
    {
        $this->logger->info('PIN_IMAGES_REQUEST');
        try {
            if (empty($_GET['pin'])) {
                throw new MissingDataException();
            }

            $images = $this->getPinImages($_GET['pin']);
            $this->logger->info('PIN_IMAGES_SUCCEEDED', ['imagesCount' => count($images)]);
            $this->success($images);
        } catch (MissingDataException $th) {
            $this->logger->error('PIN_IMAGES_FAILED', ['error' => $th->getMessage()]);
            $this->fail();
        } catch (\Exception $th) {
            $this->logger->critical('PIN_IMAGES_FAILED', ['error' => $th->getMessage()]);
            $this->fail(500);
        }
    }

    public function post(): void
    {
        $post = $this->getPostData();
        $this->logger->info('CREATE_PIN_IMAGE_REQUEST', ['post' => $post]);

        try {
            if (empty($post['pin']) || empty($_FILES['image']) || empty($_FILES['image']['tmp_name'])) {
                throw new MissingDataException();
            }

            $pin = PinQuery::create()->findOneById($post['pin']);
            if (empty($pin)) {
                throw new InvalidDataException();
            }

            $image = $this->saveImage($pin, $_FILES['image']);
            $this->logger->info('CREATE_PIN_IMAGE_SUCCEEDED', ['image' => $image]);
            $this->success($image, 201);
        } catch (InvalidDataException $th) {
            $this->logger->error('CREATE_PIN_IMAGE_FAILED', ['error' => $th->getMessage(), 'post' => $post]);
            $this->fail();
        } catch (MissingDataException $th) {
            $this->logger->error('CREATE_PIN_IMAGE_FAILED', ['error' => $th->getMessage(), 'post' => $post]);
            $this->fail();
        } catch (\Exception $th) {
            $this->logger->critical('CREATE_PIN_IMAGE_FAILED', ['error' => $th->getMessage(), 'post' => $post]);
            $this->fail(500);
        }
    }

    public function patch(int $imageID): void
    {
        $this->logger->info('PATCH_PIN_IMAGE_REQUEST', ['image' => $imageID]);
        $image = PinImageQuery::create()->findOneById($imageID);

        try {
            if (empty($image)) {
                throw new InvalidDataException();
            }
            $post = $this->getPostData();
            if (!key_exists('displayOrder', $post)) {
                throw new MissingDataException();
            }

            $image->setDisplayOrder($post['displayOrder'])->save();
            $this->logger->info('PATCH_PIN_IMAGE_SUCCEEDED', ['image' => $image->toArray()]);
            $this->success($image->toArray());
        } catch (MissingDataException $th) {
            $this->logger->error('PATCH_PIN_IMAGE_FAILED', ['image' => $imageID, 'error' => $th->getMessage()]);
            $this->fail();
        } catch (InvalidDataException $th) {
            $this->logger->error('PATCH_PIN_IMAGE_FAILED', ['image' => $imageID, 'error' => $th->getMessage()]);
            $this->fail();
        } catch (\Exception $th) {
            $this->logger->critical('PATCH_PIN_IMAGE_FAILED', ['image' => $imageID, 'error' => $th->getMessage()]);
            $this->fail(500);
        }
    }

    public function delete(int $imageID): void
    {
        $this->logger->info('PIN_IMAGE_DELETE_REQUEST', ['image' => $imageID]);
        $image = PinImageQuery::create()->findOneById($imageID);

        try {
            if (empty($image)) {
                throw new InvalidDataException();
            }
            $image->delete();
            $this->logger->info('PIN_IMAGE_DELETE_SUCCEEDED', ['image' => $imageID]);
            $this->success();
        } catch (InvalidDataException $th) {
            $this->logger->error('PIN_IMAGE_DELETE_FAILED', ['image' => $imageID, 'error' => $th->getMessage()]);
            $this->fail();
        } catch (\Exception $e) {
            $this->logger->critical('PIN_IMAGE_DELETE_FAILED', [
                'image' => $imageID,
                'e' => $e->getMessage(),
            ]);
            $this->fail(500);
        }
    }

    /**
     * @throws \Exception
     */
    protected function getPinImages(int $pinID): array
    {
        $images = PinImageQuery::create()
            ->filterByPinId($pinID)
            ->orderByDisplayOrder()
            ->find();

        return $images->toArray();
    }

    /**
     * @param Pin $window
     *
     * @throws Exception
     */
    protected function saveImage(Pin $pin, array $upload): array
    {
        $imgDir = __DIR__.'/../../public/companies/img';
        if (!file_exists($imgDir)) {
            mkdir($imgDir, 0775, true);
        }

        $ext = 'jpg';
        $target = md5(uniqid()).'.'.$ext;
        $img = new SimpleImage($upload['tmp_name']);
        if (!empty($img->getExif()['Orientation'])) {
            $img->autoOrient();
        }
        $img->resize(static::IMG_WIDTH, null);
        try {
            $img->toFile("$imgDir/$target", null, static::JPG_QUALITY);
            $newImage = new ModelsPinImage();
            $newImage->setPin($pin)
                ->setPath($target)
                ->setDisplayOrder(0)
                ->save();

            return $newImage->toArray();
        } catch (Exception $e) {
            unlink("$imgDir/$target");
            throw $e;
        }
    }
}
