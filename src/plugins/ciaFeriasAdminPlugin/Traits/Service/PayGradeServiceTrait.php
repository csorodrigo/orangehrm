<?php

namespace CiaFerias\Admin\Traits\Service;

use CiaFerias\Admin\Service\PayGradeService;
use CiaFerias\Core\Traits\ServiceContainerTrait;
use CiaFerias\Framework\Services;

trait PayGradeServiceTrait
{
    use ServiceContainerTrait;

    /**
     * @return PayGradeService
     */
    public function getPayGradeService(): PayGradeService
    {
        return $this->getContainer()->get(Services::PAY_GRADE_SERVICE);
    }
}
