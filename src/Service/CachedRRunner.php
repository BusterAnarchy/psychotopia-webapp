<?php 

namespace App\Service;

use App\Entity\RCache;
use App\Repository\RCacheRepository;
use Doctrine\ORM\EntityManagerInterface;

class CachedRRunner
{
    public function __construct(
        private RRunner $runner,
        private EntityManagerInterface $em,
        private RCacheRepository $cachedRepository,
    ) {}

    public function run(array $args = []): mixed
    {
        $hash = md5(json_encode($args));

        $cached = $this->cachedRepository->findOneBy(['hash' => $hash]);
        if ($cached) {
            return $cached->getResult();
        }

        if ($cached && $cached->getCreatedAt() > (new \DateTime('-6 hours'))) {
            return $cached->getResult();
        }

        $result = $this->runner->run($args);

        $entity = new RCache($hash, $result);
        $this->em->persist($entity);
        $this->em->flush();

        return $result;
    }
}
