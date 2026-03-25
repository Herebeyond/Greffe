<?php

namespace App\Repository\Reference;

use App\Entity\Reference\BloodGroup;
use Doctrine\Persistence\ManagerRegistry;

class BloodGroupRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BloodGroup::class);
    }
}
