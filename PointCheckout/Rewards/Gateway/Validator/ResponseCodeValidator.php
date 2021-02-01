<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace PointCheckout\Rewards\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use PointCheckout\Rewards\Gateway\Http\Client\ClientMock;

class ResponseCodeValidator extends AbstractValidator
{

    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        return $this->createResult(
            true,
            []
        );
    }
}
