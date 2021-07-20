<?php

namespace Corals\Modules\Payment\Braintree\Message;

use Corals\Modules\Payment\Common\Message\ResponseInterface;

/**
 * Authorize Request
 *
 * @method Response send()
 */
class UpdateMerchantAccountRequest extends AbstractMerchantAccountRequest
{
    public function getData()
    {
        return array(
            'merchantData' => $this->getBusinessData() + $this->getFundingData() + $this->getIndividualData(),
            'merchantAccountId' => $this->getMerchantAccountId(),
        );
    }

    /**
     * Send the request with specified data
     *
     * @param  mixed $data The data to send
     * @return ResponseInterface
     */
    public function sendData($data)
    {
        $response = $this->braintree->merchantAccount()->update($data['merchantAccountId'], $data['merchantData']);

        return $this->response = new Response($this, $response);
    }
}
