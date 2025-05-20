<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Genre;

#[ORM\Entity]
class Anime
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

        #[ORM\ManyToOne(targetEntity: Genre::class, inversedBy: "animes")]
    #[ORM\JoinColumn(name: 'genre_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private $genre_id;

    #[ORM\Column(type: "string", length: 50)]
    private $name;

    #[ORM\Column(type: "string", length: 250)]
    private $descrition;

    #[ORM\Column(type: "string", length: 20)]
    private $statut;

    #[ORM\Column(type: "string", length: 250)]
    private $trailerurl;

    #[ORM\Column(type: "string", length: 250)]
    private $image;

    #[ORM\Column(type: "string", length: 50)]
    private $age;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getGenre_id()
    {
        return $this->genre_id;
    }

    public function setGenre_id($value)
    {
        $this->genre_id = $value;
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

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }

    public function getTrailerurl()
    {
        return $this->trailerurl;
    }

    public function setTrailerurl($value)
    {
        $this->trailerurl = $value;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($value)
    {
        $this->image = $value;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function setAge($value)
    {
        $this->age = $value;
    }
}
