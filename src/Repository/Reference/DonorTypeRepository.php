<?php

namespace App\Repository\Reference;

use App\Entity\Reference\DonorType;
use Doctrine\Persistence\ManagerRegistry;

class DonorTypeRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DonorType::class);
    }
}
