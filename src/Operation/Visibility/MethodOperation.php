<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SemanticVersionChecker\Operation\Visibility;

use Magento\SemanticVersionChecker\Operation\VisibilityOperation;

/**
 * Abstract Class for visibility compare operation for Methods
 */
class MethodOperation extends VisibilityOperation
{
    /**
     * @inheritDoc
     */
    protected function getMemberName($member)
    {
        return $member->name ?? '';
    }
}
