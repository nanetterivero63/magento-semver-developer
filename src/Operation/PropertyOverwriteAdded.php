<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SemanticVersionChecker\Operation;

use PHPSemVerChecker\Operation\PropertyOperationUnary;
use PHPSemVerChecker\SemanticVersioning\Level;

class PropertyOverwriteAdded extends PropertyOperationUnary
{
    /**
     * @var array
     */
    protected $code = [
        'class' => ['M019', 'M020', 'M026'],
    ];

    /**
     * @var string
     */
    protected $reason = 'Property overwrite has been added.';

    /**
     * @var array
     */
    private $mapping = [
        'M019' => Level::PATCH,
        'M020' => Level::PATCH,
        'M026' => Level::PATCH,
    ];

    /**
     * Returns level of error.
     *
     * @return mixed
     */
    public function getLevel()
    {
        return $this->mapping[$this->getCode()];
    }
}
