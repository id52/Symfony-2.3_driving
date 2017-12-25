<?php

namespace My\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use My\AppBundle\Exception\PromoException;

class Promo
{
    protected $em;
    protected $symbols = '2456789QWRYUSDFGJLZVN';

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function generatePromoKeyHashes($count, $length = 8)
    {
        if ($length > 32) {
            new PromoException('Can\'t generate hash with length more than 32.');
        }

        $hashes = array();
        $leftToGenerate = $count;
        //generate new hashes untill we generate all of asked to generate
        while ($leftToGenerate) {
            //some new hashes - it could be so that we already have some of needed
            $preHashes = array();
            for ($i = 0; $i < $leftToGenerate; $i++) {
//            $hash = md5(rand(0, 100).time());
//            $hash = substr($hash, (-1)*$length);
                $hash = '';
                for ($j = 0; $j < $length; $j++) {
                    $hash .= $this->symbols[rand(0, strlen($this->symbols) - 1)];
                }
                //do we already have that one? That's possible =). Then we iterate one more time
                if (in_array($hash, $hashes) || in_array($hash, $preHashes)) {
                    $i--;
                } else {
                    $preHashes['h'.count($preHashes)] = $hash;
                }
            }
            //Get hashes that are already in DB
            $found = $this->em->createQuery('
                SELECT pk.hash
                FROM AppBundle:PromoKey pk
                WHERE pk.hash = :h'.implode(' OR pk.hash=:h', range(0, count($preHashes) - 1)))
                ->setParameters($preHashes)
                ->getResult();
            //delete those we have
            foreach ($found as $num => $hash) {
                $found[$num] = $hash['hash'];
            }

            $preHashes = array_diff($preHashes, $found);

            //update global array of hashes
            $hashes = array_merge($hashes, $preHashes);
            $leftToGenerate = $count - count($hashes);
        }

        return $hashes;
    }
}
