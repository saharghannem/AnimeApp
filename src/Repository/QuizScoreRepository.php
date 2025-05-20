<?php

namespace App\Repository;

use App\Entity\QuizScore;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuizScore>
 */
class QuizScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizScore::class);
    }

    /**
     * Trouve les meilleurs scores globaux
     */
    public function findTopScores(int $limit = 10, ?string $quizType = null)
    {
        $qb = $this->createQueryBuilder('qs')
            ->leftJoin('qs.user', 'u')
            ->select('qs', 'u');
        
        if ($quizType && $quizType !== 'all') {
            $qb->where('qs.quiz_type = :type')
               ->setParameter('type', $quizType);
        }
        
        return $qb->orderBy('qs.score', 'DESC')
                 ->addOrderBy('qs.date', 'DESC')
                 ->setMaxResults($limit)
                 ->getQuery()
                 ->getResult();
    }
    
    /**
     * Trouve les meilleurs scores d'un utilisateur spécifique
     */
    public function findUserBestScores(User $user, int $limit = 5, ?string $quizType = null)
    {
        $qb = $this->createQueryBuilder('qs')
            ->where('qs.user = :user')
            ->setParameter('user', $user);
        
        if ($quizType && $quizType !== 'all') {
            $qb->andWhere('qs.quiz_type = :type')
               ->setParameter('type', $quizType);
        }
        
        return $qb->orderBy('qs.score', 'DESC')
                 ->addOrderBy('qs.date', 'DESC')
                 ->setMaxResults($limit)
                 ->getQuery()
                 ->getResult();
    }
}
