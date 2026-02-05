<?php

declare(strict_types=1);

namespace App\Board\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'board_columns')]
class Column
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Board::class, inversedBy: 'columns')]
    #[ORM\JoinColumn(name: 'board_id', nullable: false)]
    private ?Board $board = null;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 5)]
    private string $position;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $taskCount = 0;

    #[ORM\Column(type: 'jsonb', options: ['jsonb' => true], nullable: true)]
    private ?array $settings = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(string $name, string $position = '0')
    {
        $this->name = $name;
        $this->position = $position;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBoard(): ?Board
    {
        return $this->board;
    }

    public function setBoard(?Board $board): void
    {
        $this->board = $board;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setPosition(string $position): void
    {
        $this->position = $position;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getTaskCount(): int
    {
        return $this->taskCount;
    }

    public function setTaskCount(int $taskCount): void
    {
        $this->taskCount = $taskCount;
    }

    public function getSettings(): ?array
    {
        return $this->settings;
    }

    public function setSettings(?array $settings): void
    {
        $this->settings = $settings;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
