<?php

namespace App\Repository\Reference;

use App\Entity\Reference\EducationTopic;
use Doctrine\Persistence\ManagerRegistry;

class EducationTopicRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EducationTopic::class);
    }
}
