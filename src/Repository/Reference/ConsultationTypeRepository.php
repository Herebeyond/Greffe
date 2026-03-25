<?php

namespace App\Repository\Reference;

use App\Entity\Reference\ConsultationType;
use Doctrine\Persistence\ManagerRegistry;

class ConsultationTypeRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConsultationType::class);
    }
}
