<?php

namespace ph\sen\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Activity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $uuid;

    /**
     * @ORM\Column(type="integer")
     */
    private $uid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $class;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $exchange;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $outcome;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $tradeId;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $data;

    public function getId()
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getUid(): ?int
    {
        return $this->uid;
    }

    public function setUid(int $uid): self
    {
        $this->uid = $uid;

        return $this;
    }

    public function getClass(): ?string
    {
      return $this->class;
    }

    public function setClass(string $class): self
    {
      $this->class = $class;

      return $this;
    }

    public function getExchange(): ?string
    {
      return $this->exchange;
    }

    public function setExchange(string $exchange): self
    {
      $this->exchange = $exchange;

      return $this;
    }

    public function getOutcome(): ?string
    {
        return $this->outcome;
    }

    public function setOutcome(string $outcome): self
    {
        $this->outcome = $outcome;

        return $this;
    }

    public function getTradeId(): ?string
    {
      return $this->tradeId;
    }

    public function setTradeId(string $tradeId): self
    {
      $this->tradeId = $tradeId;

      return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): self
    {
        $this->data = $data;

        return $this;
    }
}
