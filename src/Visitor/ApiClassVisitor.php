<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SemanticVersionCheckr\Visitor;

use PhpParser\Node;

class ApiClassVisitor extends AbstractApiVisitor
{
    /** @var string */
    protected $nodeType = '\PhpParser\Node\Stmt\Class_';

    public function add(Node $node)
    {
        $this->registry->addClass($node);
    }
}
