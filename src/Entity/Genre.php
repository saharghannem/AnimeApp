<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Anime;

#[ORM\Entity]
class Genre
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 250)]
    private $name;

    #[ORM\Column(type: "string", length: 250)]
    private $descrition;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    public function getDescrition()
    {
        return $this->descrition;
    }

    public function setDescrition($value)
    {
        $this->descrition = $value;
    }

    #[ORM\OneToMany(mappedBy: "genre_id", targetEntity: Anime::class)]
    private Collection $animes;

        public function getAnimes(): Collection
        {
            return $this->animes;
        }
    
        public function addAnime(Anime $anime): self
        {
            if (!$this->animes->contains($anime)) {
                $this->animes[] = $anime;
                $anime->setGenre_id($this);
            }
    
            return $this;
        }
    
        public function removeAnime(Anime $anime): self
        {
            if ($this->animes->removeElement($anime)) {
                // set the owning side to null (unless already changed)
                if ($anime->getGenre_id() === $this) {
                    $anime->setGenre_id(null);
                }
            }
    
            return $this;
        }
}
