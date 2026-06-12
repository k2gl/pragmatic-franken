<?php

declare(strict_types=1);

namespace App\Context\Task\Entity;

use App\Context\Task\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;

/**
 * Reference aggregate (ADR-0012): entities live at context level so multiple
 * feature slices (CreateTask, ListTasks, CompleteTask) act on one model.
 */
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
class Task
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(string $title)
    {
        $this->id = Uuid::v7();
        $this->title = $title;
        $this->createdAt = new DateTimeImmutable;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** Idempotent: completing a completed task keeps the original timestamp. */
    public function complete(): void
    {
        $this->completedAt ??= new DateTimeImmutable;
    }
}
