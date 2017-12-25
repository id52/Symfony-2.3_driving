<?php

namespace My\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    /**
     * Возвращает пользователей, у которых день рождения в $day.$month
     *
     * @param $day
     * @param $month
     *
     * @return array
     */
    public function findByUserBirthday($day, $month)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('DAY(u.birthday) = :day')->setParameter('day', $day)
            ->andWhere('MONTH(u.birthday) = :month')->setParameter('month', $month)
            ->getQuery()->getArrayResult();
    }

    /**
     * Возвращает пользователей, у которых день рождения сегодня
     *
     * @return array
     */
    public function findByUserBirthdayToday()
    {
        $currentDate = new \DateTime('today');
        return $this->findByUserBirthday($currentDate->format('d'), $currentDate->format('m'));
    }

    public function getSupportStatistics(\DateTime $from, \DateTime $to)
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.support_messages', 'm')
            ->leftJoin('m.dialog', 'd')
            ->groupBy('u')
            ->select('u as obj')
            ->addSelect('count(m) as messagesCount')
            ->andWhere('m.created_at >= :from')->setParameter('from', $from)
            ->andWhere('m.created_at <= :to')->setParameter('to', $to)
            ->andWhere('d.user != u')
            ->getQuery()->getArrayResult();
    }

    public function findByRole($role)
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"'.$role.'"%')
            ->getQuery()->getResult();
    }

    public function removeTriedsAndReservists($user, $notRezerv = false)
    {
        $reservists = false;

        $trieds = $this->getEntityManager()->createQueryBuilder()
            ->delete('AppBundle:TriedEnters', 'te')
            ->andWhere('te.user = :user')->setParameter('user', $user)
            ->getQuery()->getResult();

        if (!$notRezerv) {
            $reservists = $this->getEntityManager()->createQueryBuilder()
                ->delete('PaymentBundle:Log', 'pl')
                ->andWhere('pl.paid = :lfp')->setParameter('lfp', false)
                ->andWhere('pl.user = :pluser')->setParameter('pluser', $user)
                ->getQuery()->getResult();
        }
        if ($trieds || $reservists) {
            return true;
        }

        return false;
    }
}
