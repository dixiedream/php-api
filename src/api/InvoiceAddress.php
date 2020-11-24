<?php

namespace api;

use exceptions\InvalidDataException;
use exceptions\MissingDataException;
use models\InvoiceAddress as ModelsInvoiceAddress;
use models\PinQuery;

class InvoiceAddress extends APICall
{
    public function post()
    {
        try {
            $this->logger->info('CREATE_INVOICE_ADDRESS_REQUEST', $_POST);
            $post = $this->getPostData();
            $this->checkCreateFields($post);

            $pin = PinQuery::create()->findOneById($post['pinId']);
            if (empty($pin)) {
                throw new InvalidDataException();
            }

            $pec = null;
            if (!empty($post['pec'])) {
                $pec = filter_var($post['pec'], FILTER_SANITIZE_EMAIL);
                if (!filter_var($pec, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidDataException();
                }
            }

            $invoiceAddress = new ModelsInvoiceAddress();
            $invoiceAddress->addPin($pin)
                ->setBossName($post['boss']['name'])
                ->setBossSurname($post['boss']['surname'])
                ->setRag($post['rag'])
                ->setType($post['type'])
                ->setPIva($post['pIva'])
                ->setCFis($post['cFis'])
                ->setReceiverCode($post['receiverCode'])
                ->setPec($pec)
                ->setAddress($post['invoiceAddress'])
                ->setCity($post['invoiceAddress']['city'])
                ->setZip($post['invoiceAddress']['zip'])
                ->setDistrict($post['invoiceAddress']['district'])
                ->save();

            $this->logger->info('CREATE_INVOICE_ADDRESS_REQUEST_SUCCEEDED', ['id' => $invoiceAddress->getId()]);
            $this->success(['id' => $invoiceAddress->getId()], 201);
        } catch (MissingDataException $th) {
            $this->logger->error('CREATE_INVOICE_ADDRESS_REQUEST_FAILED', ['e' => $th->getMessage()]);
            $this->fail();
        } catch (InvalidDataException $th) {
            $this->logger->error('CREATE_INVOICE_ADDRESS_REQUEST_FAILED', ['e' => $th->getMessage()]);
            $this->fail();
        } catch (\Exception $th) {
            $this->logger->critical('CREATE_INVOICE_ADDRESS_REQUEST_FAILED', ['e' => $th->getMessage()]);
            $this->fail(500);
        }
    }

    /**
     * @param array $post
     * @return bool
     */
    protected function checkCreateFields(array $post): bool
    {
        $reqFields = ['rag', 'type', 'boss', 'pIva', 'cFis', 'receiverCode', 'pec', 'invoiceAddress', 'pinId'];
        foreach ($reqFields as $field) {
            if (!key_exists($field, $post)) {
                throw new MissingDataException();
            }
        }

        return true;
    }
}
