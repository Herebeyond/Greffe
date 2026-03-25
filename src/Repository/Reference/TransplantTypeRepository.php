<?php

namespace App\Repository\Reference;

use App\Entity\Reference\TransplantType;
use Doctrine\Persistence\ManagerRegistry;

class TransplantTypeRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransplantType::class);
    }
}
