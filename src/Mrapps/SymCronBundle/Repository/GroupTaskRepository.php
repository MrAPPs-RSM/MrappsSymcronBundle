<?php

namespace Mrapps\SymCronBundle\Repository;

use Doctrine\ORM\EntityRepository;

class GroupTaskRepository extends \Doctrine\ORM\EntityRepository
{
    public function findAllActiveGroups()
    {
        return $this->getEntityManager()
            ->createQuery("
                select g,t from MrappsSymCronBundle:GroupTask g
                inner join g.tasks t
                where g.enabled=1
                order by g.weight, t.weight
            ")->execute();
    }
}
