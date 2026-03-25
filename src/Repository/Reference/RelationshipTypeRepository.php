<?php

namespace App\Repository\Reference;

use App\Entity\Reference\RelationshipType;
use Doctrine\Persistence\ManagerRegistry;

class RelationshipTypeRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RelationshipType::class);
    }
}
