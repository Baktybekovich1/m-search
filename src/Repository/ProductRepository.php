<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Search products whose title or description contains any of the keywords
     * extracted from the AI-generated text description.
     *
     * @param string $text  Free-form description text from Gemini
     * @return Product[]
     */
    public function searchByText(string $text): array
    {
        // Extract meaningful words (>= 3 chars) and deduplicate
        $words = array_unique(
            array_filter(
                preg_split('/\W+/', strtolower($text)),
                fn(string $w) => strlen($w) >= 3
            )
        );

        if (empty($words)) {
            return [];
        }

        $qb = $this->createQueryBuilder('p');

        $conditions = [];
        foreach ($words as $i => $word) {
            $param = 'w' . $i;
            $conditions[] = $qb->expr()->orX(
                $qb->expr()->like('LOWER(p.title)', ':' . $param),
                $qb->expr()->like('LOWER(p.description)', ':' . $param)
            );
            $qb->setParameter($param, '%' . $word . '%');
        }

        $qb->where($qb->expr()->orX(...$conditions))
           ->orderBy('p.id', 'DESC')
           ->setMaxResults(20);

        return $qb->getQuery()->getResult();
    }
}
