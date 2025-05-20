<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quiz_score')]
class QuizScore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'integer')]
    private int $score = 0;

    #[ORM\Column(type: 'integer')]
    private int $total_questions = 0;
    
    #[ORM\Column(type: 'string', length: 20)]
    private string $quiz_type = 'genres';

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getTotalQuestions(): int
    {
        return $this->total_questions;
    }

    public function setTotalQuestions(int $totalQuestions): self
    {
        $this->total_questions = $totalQuestions;
        return $this;
    }
    
    public function getQuizType(): string
    {
        return $this->quiz_type;
    }
    
    public function setQuizType(string $quizType): self
    {
        $this->quiz_type = $quizType;
        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }
    
    public function getPercentage(): float
    {
        if ($this->total_questions > 0) {
            return round(($this->score / $this->total_questions) * 100, 1);
        }
        return 0;
    }
}
